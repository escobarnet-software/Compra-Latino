<?php

namespace App\Jobs;

use App\Exceptions\YAuctionCommunicationException;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class ProcessYAuctionPurchase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Order $order
    ) {
        $this->onQueue('compras-yauctions');
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(): void
    {
        try {
            DB::transaction(function () {
                $orden = Order::whereKey($this->order->id)->lockForUpdate()->firstOrFail();

                if (! $orden->puedeSincronizarse()) {
                    throw YAuctionCommunicationException::porReglaDeNegocio(
                        "La orden #{$orden->id} ya fue procesada (estado: {$orden->estado}).",
                        ['orden_id' => $orden->id]
                    );
                }

                $orden->forceFill(['estado' => Order::ESTADO_EN_PROCESO])->save();
                $orden->registrarAuditoria('inicio_sincronizacion', [
                    'plataforma' => $orden->plataforma,
                    'producto'   => $orden->product_id,
                ], $this->attempts() ?: 1);
            });
        } catch (YAuctionCommunicationException $e) {
            $this->auditarFalla($e);
            throw $e;
        }

        try {
            $ordenActual = Order::whereKey($this->order->id)->firstOrFail();

            if ($ordenActual->plataforma !== 'yauctions') {
                DB::transaction(function () use ($ordenActual) {
                    $o = Order::whereKey($ordenActual->id)->firstOrFail();
                    $o->forceFill([
                        'estado'             => Order::ESTADO_COMPLETADA,
                        'external_reference' => 'LOCAL-' . $o->id,
                        'sincronizado_en'    => now(),
                    ])->save();
                    $o->registrarAuditoria('venta_local_confirmada', [
                        'plataforma' => $o->plataforma,
                        'nota'       => 'Sin llamada externa: inventario propio.',
                    ], $this->attempts() ?: 1);
                });

                return;
            }

            $payload = $this->construirPayload();

            try {
                $baseUrl = (string) config('services.yauctions.url');
                $esSimuladorLocal = str_contains($baseUrl, '127.0.0.1:8000')
                    || str_contains($baseUrl, 'localhost:8000');

                if ($esSimuladorLocal) {
                    $respuesta = Http::baseUrl($baseUrl)
                        ->timeout(15)
                        ->post('/simulador-yauctions/comprar', $payload);
                } else {
                    $respuesta = Http::baseUrl($baseUrl)
                        ->withToken(config('services.yauctions.token'))
                        ->timeout(15)
                        ->post('/api/v1/purchases', $payload);
                }
            } catch (ConnectionException $conexion) {
                throw YAuctionCommunicationException::porTimeout($payload, $conexion);
            } catch (Throwable $conexion) {
                if ($this->esFalloDeConexion($conexion)) {
                    throw YAuctionCommunicationException::porTimeout($payload, $conexion);
                }
                throw $conexion;
            }

            if ($respuesta->failed()) {
                throw YAuctionCommunicationException::porErrorHttp(
                    $respuesta->status(),
                    $respuesta->json(),
                    $payload
                );
            }

            $referenciaExterna = $respuesta->json('purchase_id')
                ?? $respuesta->json('external_reference');

            DB::transaction(function () use ($referenciaExterna, $payload, $respuesta) {
                $orden = Order::whereKey($this->order->id)->firstOrFail();

                $orden->forceFill([
                    'estado'             => Order::ESTADO_COMPLETADA,
                    'external_reference' => (string) $referenciaExterna,
                    'sincronizado_en'    => now(),
                ])->save();

                $orden->registrarAuditoria('sincronizacion_exitosa', [
                    'enviado'   => $payload,
                    'respuesta' => $respuesta->json(),
                ], $this->attempts() ?: 1);
            });
        } catch (YAuctionCommunicationException $e) {
            $this->auditarFalla($e);
            throw $e;
        } catch (Throwable $e) {
            Log::critical('Fallo técnico inesperado en sincronización YAuctions', [
                'orden_id' => $this->order->id,
                'error'    => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        DB::transaction(function () use ($exception) {
            $orden = Order::find($this->order->id);
            if (! $orden) {
                return;
            }

            $orden->forceFill(['estado' => Order::ESTADO_FALLIDA])->save();
            $orden->registrarAuditoria('fallo_terminal', [
                'mensaje'                => $exception?->getMessage() ?? 'Desconocido',
                'intentos'               => $this->tries,
                'requiere_revisacion'    => true,
            ], $this->tries);
        });

        Log::error('Orden marcada como FALLIDA tras agotar reintentos', [
            'orden_id' => $this->order->id,
        ]);
    }

    private function construirPayload(): array
    {
        return [
            'external_product_id' => $this->order->product?->external_id,
            'quantity'            => $this->order->cantidad,
            'unit_price'          => (float) $this->order->precio_unitario,
            'currency'            => $this->order->moneda,
            'buyer_reference'     => "CL-{$this->order->user_id}-{$this->order->id}",
        ];
    }

    private function esFalloDeConexion(Throwable $e): bool
    {
        $clase = get_class($e);

        return str_contains($clase, 'ConnectException')
            || str_contains($clase, 'ConnectionException')
            || str_contains($clase, 'TimeoutException')
            || str_contains($clase, 'TransferException');
    }

    private function auditarFalla(YAuctionCommunicationException $e): void
    {
        $orden = Order::find($this->order->id);
        if ($orden) {
            $orden->registrarAuditoria('fallo_comunicacion', [
                'mensaje'  => $e->getMessage(),
                'contexto' => $e->contexto(),
                'intento'  => $this->attempts() ?: 1,
            ], $this->attempts() ?: 1);
        }

        Log::warning('Fallo de comunicación con YAuctions auditado', [
            'orden_id' => $this->order->id,
            'contexto' => $e->contexto(),
        ]);
    }
}
