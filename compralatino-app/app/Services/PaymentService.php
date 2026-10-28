<?php

namespace App\Services;

use App\Exceptions\PaymentDeclinedException;

class PaymentService
{
    
    public function cobrar(array $tarjeta, float $monto, string $moneda, int $ordenId): array
    {
        $numero = preg_replace('/\D/', '', $tarjeta['numero']);

        if (! $this->pasaLuhn($numero)) {
            throw PaymentDeclinedException::tarjetaInvalida();
        }

        if (! $this->vencimientoVigente($tarjeta['vencimiento'])) {
            throw PaymentDeclinedException::tarjetaVencida();
        }

        if (! preg_match('/^\d{3,4}$/', $tarjeta['cvv'])) {
            throw PaymentDeclinedException::cvvInvalido();
        }

        usleep(300000);

        if ($numero === '4000000000000002') {
            throw PaymentDeclinedException::fondosInsuficientes($monto, $moneda);
        }

        $marca = $this->detectarMarca($numero);

        return [
            'marca'     => $marca,
            'ultimos4'  => substr($numero, -4),
            'referencia' => 'PAY-' . $ordenId . '-' . strtoupper(substr(md5($numero . $ordenId . microtime()), 0, 8)),
        ];
    }

    public function pasaLuhn(string $numero): bool
    {
        if (strlen($numero) < 13 || strlen($numero) > 19 || ! ctype_digit($numero)) {
            return false;
        }

        $suma = 0;
        $doblar = false;

        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $digito = (int) $numero[$i];

            if ($doblar) {
                $digito *= 2;
                if ($digito > 9) {
                    $digito -= 9;
                }
            }

            $suma += $digito;
            $doblar = ! $doblar;
        }

        return $suma % 10 === 0;
    }

    public function detectarMarca(string $numero): string
    {
        if (str_starts_with($numero, '4')) {
            return 'visa';
        }

        $prefijo2 = (int) substr($numero, 0, 2);
        $prefijo4 = (int) substr($numero, 0, 4);

        if (($prefijo2 >= 51 && $prefijo2 <= 55) || ($prefijo4 >= 2221 && $prefijo4 <= 2720)) {
            return 'mastercard';
        }

        if (str_starts_with($numero, '34') || str_starts_with($numero, '37')) {
            return 'amex';
        }

        return 'desconocida';
    }

    private function vencimientoVigente(string $vencimiento): bool
    {
        if (! preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $vencimiento, $m)) {
            return false;
        }

        $finDeMes = \DateTime::createFromFormat('y-m-d H:i:s', $m[2] . '-' . $m[1] . '-01 00:00:00');
        if (! $finDeMes) {
            return false;
        }
        $finDeMes->modify('last day of this month')->setTime(23, 59, 59);

        return $finDeMes > new \DateTime();
    }
}
