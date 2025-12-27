<?php

namespace Aecil\Verifactu\Enums;

/**
 * Define los diferentes tipos de facturas rectificativa aplicables
 */
enum TipoRectificativa: string
{
    /**
     * Por sustitución
     *
     * La factura rectificativa reemplaza por completo a la factura original.
     * La factura original queda anulada y es sustituida íntegramente.
     */
    const SUBSTITUTIVA = 'S';

    /**
     * Por diferencias
     *
     * La factura rectificativa ajusta únicamente importes o datos concretos
     * de la factura original. La factura original sigue siendo válida,
     * y la rectificativa actúa como complemento.
     */
    const DIFERENCIAS = 'I';
}
