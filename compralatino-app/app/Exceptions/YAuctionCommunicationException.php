<?php

/*
 * Excepcion que lanzo cuando falla la comunicacion con YAuctions, para
 * diferenciarla de cualquier otro error. Lleva el contexto del fallo para
 * poder guardarlo en la auditoria de la orden.
 */

namespace App\Exceptions;

use Exception;
use Throwable;

class YAuctionCommunicationException extends Exception
{
    /**
     * Contexto completo del fallo: qué se envió, qué se recibió, error base.
     * Se registrará en orders.datos_sincronizacion (bitácora de auditoría).
     */
    protected array $contexto = [];

    /**
     * @param string           $mensaje  Descripción legible del fallo de negocio.
     * @param array            $contexto Payload/respuesta involucrados.
     * @param int              $codigo   Estado HTTP o código interno.
     * @param Throwable|null   $anterior Excepción técnica original (encadenamiento).
     */
    public function __construct(
        string $mensaje = 'No fue posible comunicarse con la tienda externa YAuctions.',
        array $contexto = [],
        int $codigo = 0,
        ?Throwable $anterior = null
    ) {
        parent::__construct($mensaje, $codigo, $anterior);
        $this->contexto = $contexto;
    }

    /** Accesor de la data de auditoría que usará el Job para persistirla. */
    public function contexto(): array
    {
        return $this->contexto;
    }

    /**
     * Fábrica estática: mejora la legibilidad en el punto de lanzamiento
     * (self-documenting code):
     *   throw YAuctionCommunicationException::porTimeout($payload, $e);
     */
    public static function porTimeout(array $payload, ?Throwable $anterior = null): self
    {
        return new self(
            'Tienda YAuctions no responde dentro del tiempo límite.',
            ['tipo' => 'timeout', 'payload' => $payload],
            408,
            $anterior
        );
    }

    public static function porErrorHttp(int $statusCode, ?array $respuesta, array $payload): self
    {
        return new self(
            "YAuctions respondió con el estado HTTP {$statusCode}.",
            ['tipo' => 'http_error', 'status' => $statusCode, 'respuesta' => $respuesta ?? '(cuerpo no JSON)', 'payload' => $payload],
            $statusCode
        );
    }

    public static function porReglaDeNegocio(string $motivo, array $payload = []): self
    {
        return new self(
            "Regla de negocio violada: {$motivo}",
            ['tipo' => 'regla_negocio', 'motivo' => $motivo, 'payload' => $payload],
            422
        );
    }
}
