<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class YAuctionCommunicationException extends Exception
{
    
    protected array $contexto = [];

    public function __construct(
        string $mensaje = 'No fue posible comunicarse con la tienda externa YAuctions.',
        array $contexto = [],
        int $codigo = 0,
        ?Throwable $anterior = null
    ) {
        parent::__construct($mensaje, $codigo, $anterior);
        $this->contexto = $contexto;
    }

    public function contexto(): array
    {
        return $this->contexto;
    }

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
