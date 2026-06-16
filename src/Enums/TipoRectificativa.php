<?php

namespace Aecil\Verifactu\Enums;

/**
 * Tipos de factura rectificativa según Verifactu.
 */
enum TipoRectificativa: string
{
    case SUSTITUTIVA = 'S';  // Reemplaza íntegramente a la factura original
    case DIFERENCIAS = 'I';  // Ajusta importes o datos de la factura original sin anularla
}
