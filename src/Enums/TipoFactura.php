<?php

namespace Aecil\Verifactu\Enums;

/**
 * Tipos de factura según Verifactu (AEAT).
 */
enum TipoFactura: string
{
    case F1 = 'F1';          // Factura ordinaria
    case F2 = 'F2';          // Simplificada
    case F3 = 'F3';          // Sustitutiva
    case R1 = 'R1';          // Rectificativa (Art 80.1, 80.2 y error fundado en derecho)
    case R2 = 'R2';          // Rectificativa (Art. 80.3)
    case R3 = 'R3';          // Rectificativa (Art. 80.4)
    case R4 = 'R4';          // Rectificativa (Resto)
    case R5 = 'R5';          // Rectificativa en facturas simplificadas

    /** Alias semánticos para mantener compatibilidad */
    public const FACTURA = self::F1;
    public const SIMPLIFICADA = self::F2;
    public const SUSTITUTIVA = self::F3;

    public function isRectificativa(): bool
    {
        return \in_array($this, [self::R1, self::R2, self::R3, self::R4, self::R5], true);
    }
}
