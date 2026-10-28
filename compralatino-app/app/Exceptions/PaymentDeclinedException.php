<?php

namespace App\Exceptions;

use Exception;

class PaymentDeclinedException extends Exception
{
    
    public function __construct(
        string $message = 'El pago con tarjeta fue rechazado.',
        public readonly string $motivo = 'rechazo_generico',
        public readonly array $contexto = []
    ) {
        parent::__construct($message);
    }

    public static function tarjetaInvalida(): self
    {
        return new self(
            'El número de tarjeta no es válido. Verifícalo e inténtalo de nuevo.',
            'numero_invalido'
        );
    }

    public static function tarjetaVencida(): self
    {
        return new self(
            'Tu tarjeta está vencida. Usa otra tarjeta vigente.',
            'tarjeta_vencida'
        );
    }

    public static function cvvInvalido(): self
    {
        return new self(
            'El código de seguridad (CVV) no es válido.',
            'cvv_invalido'
        );
    }

    public static function fondosInsuficientes(float $monto, string $moneda): self
    {
        return new self(
            'Fondos insuficientes para completar el cobro.',
            'fondos_insuficientes',
            ['monto' => $monto, 'moneda' => $moneda]
        );
    }
}
