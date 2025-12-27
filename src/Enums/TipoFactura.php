<?php

namespace Aecil\Verifactu\Enums;

/**
 * Enumeración con los diferentes tipos de factura según Verifactu
 */
enum TipoFactura: string
{
    const FACTURA = 'F1'; // Factura

    const SIMPLIFICADA = 'F2'; // Simplificada

    const SUSTITUTIVA = 'F3'; // Sustitutiva

    const R1 = 'R1'; // Factura rectificativa (Art 80.1 y 80.2 y error fundado en derecho)

    const R2 = 'R2'; // Factura rectificativa (Art. 80.3)

    const R3 = 'R3'; // Factura rectificativa (Art. 80.4)

    const R4 = 'R4'; // Factura rectificativa (Resto)

    const R5 = 'R5'; // Factura rectificativa en facturas simplificadas
}
