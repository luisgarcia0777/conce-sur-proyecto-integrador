<?php
declare(strict_types=1);

/**
 * Reglas de dominio puras para el registro de ventas de CONCE SUR.
 * Se separan para facilitar pruebas unitarias sin conectarse a MySQL.
 */
final class SalesRules
{
    /**
     * Devuelve el mensaje de validación o null cuando los datos son válidos.
     * Los tipos mixed conservan los resultados de filter_var (int|float|false|null).
     */
    public static function validationError(
        string $cliente,
        mixed $productoId,
        mixed $cantidad,
        string $precioRaw,
        mixed $precio,
        string $formaPago,
        string $contadorRaw,
        mixed $contador
    ): ?string {
        if (
            trim($cliente) === '' ||
            !$productoId ||
            $cantidad === false ||
            $cantidad === null ||
            $cantidad < 1 ||
            $precioRaw === '' ||
            $precio === false ||
            $precio < 0 ||
            !in_array($formaPago, ['Contado', 'Crédito'], true) ||
            ($contadorRaw !== '' && ($contador === false || $contador < 0))
        ) {
            return 'Completa el cliente, producto, cantidad y precio. Revisa también los valores numéricos.';
        }

        return null;
    }

    public static function hasSufficientStock(int $available, int $requested): bool
    {
        return $requested >= 1 && $available >= $requested;
    }

    public static function calculateTotal(int $quantity, float $unitPrice): float
    {
        if ($quantity < 1 || !is_finite($unitPrice) || $unitPrice < 0) {
            throw new InvalidArgumentException('La cantidad y el precio deben ser válidos.');
        }

        return round($quantity * $unitPrice, 2);
    }
}
