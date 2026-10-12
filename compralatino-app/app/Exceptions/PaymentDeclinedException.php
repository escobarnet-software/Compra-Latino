<?php

/*
 * Excepcion para cuando el pago con tarjeta se rechaza (fondos, tarjeta
 * vencida, numero invalido, etc.). Es una regla de negocio, no un error del
 * servidor: la capturo aparte y devuelvo al checkout sin crear la orden.
 */

namespace App\Exceptions;

use Exception;

class PaymentDeclinedException extends Exception
{
    /** Motivo machine-readable para la auditoría/BI (fondos, vencida, ...). */
    public function __construct(
        string $message = 'El pago con tarjeta fue rechazado.',
        public readonly string $motivo = 'rechazo_generico',
        public readonly array $contexto = []
    ) {
        parent::__construct($message);
    }

    /** La tarjeta no pasa el checksum Luhn (tipeo erróneo o número ficticio). */
    public static function tarjetaInvalida(): self
    {
        return new self(
            'El número de tarjeta no es válido. Verifícalo e inténtalo de nuevo.',
            'numero_invalido'
        );
    }

    /** El plástico venció (dominio temporal). */
    public static function tarjetaVencida(): self
    {
        return new self(
            'Tu tarjeta está vencida. Usa otra tarjeta vigente.',
            'tarjeta_vencida'
        );
    }

    /** CVV con formato incorrecto (posesión del plástico no verificable). */
    public static function cvvInvalido(): self
    {
        return new self(
            'El código de seguridad (CVV) no es válido.',
            'cvv_invalido'
        );
    }

    /** La pasarela aprobó la tarjeta pero rechazó el cargo por saldo. */
    public static function fondosInsuficientes(float $monto, string $moneda): self
    {
        return new self(
            'Fondos insuficientes para completar el cobro.',
            'fondos_insuficientes',
            ['monto' => $monto, 'moneda' => $moneda]
        );
    }
}
