<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentDeclinedException;
use App\Jobs\ProcessYAuctionPurchase;
use App\Models\Order;
use App\Models\Product;
use App\Models\UserHistory;
use App\Services\PaymentService;
use App\Services\RecommendationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StoreController extends Controller
{
    
    public function __construct(
        private readonly RecommendationService $recomendador
    ) {
    }

    public function index(Request $request): View
    {
        $productos = Product::query()
            ->visibles()
            ->when($request->filled('q'), function ($query) use ($request) {
                $termino = '%' . str_replace(['%', '_'], '', (string) $request->string('q')) . '%';
                $query->where(function ($sub) use ($termino) {
                    $sub->where('nombre', 'like', $termino)
                        ->orWhere('descripcion', 'like', $termino)
                        ->orWhere('sku', 'like', $termino);
                });
            })
            ->when($request->filled('categoria'), function ($query) use ($request) {
                $query->where('categoria', $request->string('categoria'));
            })
            ->when(in_array($request->string('plataforma')->toString(), ['local', 'yauctions'], true), function ($query) use ($request) {
                $query->where('plataforma', $request->string('plataforma')->toString());
            })
            ->withCount('orders')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $categorias = Product::visibles()
            ->select('categoria')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');

        $recomendaciones = auth()->check()
            ? $this->recomendador->recomendarPara($request->user())
            : collect();

        if (auth()->check() && $recomendaciones->isNotEmpty()) {
            $this->recomendador->registrarRecomendaciones($request->user(), $recomendaciones);
        }

        return view('store.index', compact('productos', 'recomendaciones', 'categorias'));
    }

    public function recommendations(Request $request): View
    {
        $recomendaciones = $this->recomendador->recomendarPara($request->user(), 12);

        if ($recomendaciones->isNotEmpty()) {
            $this->recomendador->registrarRecomendaciones($request->user(), $recomendaciones);
        }

        return view('store.recommendations', ['recomendaciones' => $recomendaciones]);
    }

    public function show(Product $product, Request $request): View
    {
        $esMovil = str_contains($request->userAgent() ?? '', 'Mobile');

        UserHistory::registrar(
            $request->user()->id,
            $product->id,
            UserHistory::EVENTO_VISTA,
            ['dispositivo' => $esMovil ? 'movil' : 'escritorio']
        );

        return view('store.show', ['producto' => $product]);
    }

    public function myOrders(Request $request): View
    {
        $ordenes = $request->user()
            ->orders()
            ->with('product:id,nombre,sku,plataforma')
            ->latest()
            ->paginate(10);

        return view('orders.index', ['ordenes' => $ordenes]);
    }

    public function showOrder(int $ordenId, Request $request): View
    {
        $orden = $request->user()
            ->orders()
            ->with('product:id,nombre,sku,plataforma')
            ->findOrFail($ordenId);

        return view('orders.show', ['orden' => $orden]);
    }

    public function checkout(Request $request): View|RedirectResponse
    {
        $pedido = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'cantidad'   => ['required', 'integer', 'min:1', 'max:10'],
            'plataforma' => ['required', 'in:local,yauctions'],
        ]);

        $producto = Product::whereKey($pedido['product_id'])->firstOrFail();

        if (! $producto->hayStockDisponible($pedido['cantidad'])) {
            return redirect()->route('store.show', $producto)->withErrors([
                'cantidad' => 'No hay stock suficiente para la cantidad solicitada.',
            ]);
        }

        $total = (float) $producto->precio * $pedido['cantidad'];

        return view('store.checkout', [
            'producto'   => $producto,
            'cantidad'   => (int) $pedido['cantidad'],
            'plataforma' => $pedido['plataforma'],
            'total'      => $total,
        ]);
    }

    public function store(\App\Http\Requests\StoreCheckoutRequest $request, PaymentService $pagos): RedirectResponse
    {
        $datos = $request->validated();

        $producto = Product::whereKey($datos['product_id'])->firstOrFail();

        if (! $producto->hayStockDisponible($datos['cantidad'])) {
            return back()->withErrors([
                'cantidad' => 'No hay stock suficiente para la cantidad solicitada.',
            ])->withInput($request->except(['numero', 'cvv']));
        }

        $total = (float) $producto->precio * $datos['cantidad'];

        try {
            $cargo = $pagos->cobrar(
                [
                    'numero'      => $datos['numero'],
                    'titular'     => $datos['titular'],
                    'vencimiento' => $datos['vencimiento'],
                    'cvv'         => $datos['cvv'],
                ],
                $total,
                $producto->moneda,
                (int) (Order::max('id') ?? 0) + 1
            );
        } catch (PaymentDeclinedException $rechazo) {
            return redirect()->route('store.checkout', [
                'product_id' => $producto->id,
                'cantidad'   => $datos['cantidad'],
                'plataforma' => $datos['plataforma'],
            ])->withErrors(['numero' => $rechazo->getMessage()])
              ->withInput($request->except(['numero', 'cvv']));
        }

        $orden = DB::transaction(function () use ($datos, $producto, $request, $total, $cargo) {
            $orden = Order::create([
                'user_id'         => $request->user()->id,
                'product_id'      => $producto->id,
                'cantidad'        => $datos['cantidad'],
                'precio_unitario' => $producto->precio,
                'moneda'          => $producto->moneda,
                'total'           => $total,
                'estado'          => Order::ESTADO_PENDIENTE,
                'plataforma'      => $datos['plataforma'],
                'marca_tarjeta'   => $cargo['marca'],
                'tarjeta_ultimos4' => $cargo['ultimos4'],
                'referencia_pago' => $cargo['referencia'],
                'pagado_en'       => now(),
            ]);

            Product::whereKey($producto->id)
                ->where('stock', '>=', $datos['cantidad'])
                ->decrement('stock', $datos['cantidad']);

            return $orden;
        });

        UserHistory::registrar(
            $request->user()->id,
            $producto->id,
            UserHistory::EVENTO_COMPRA,
            ['orden_id' => $orden->id, 'total' => (float) $orden->total]
        );

        ProcessYAuctionPurchase::dispatch($orden);

        return redirect()
            ->route('orders.show', $orden)
            ->with('status', "¡Compra registrada! La orden #{$orden->id} se está sincronizando con YAuctions.");
    }
}
