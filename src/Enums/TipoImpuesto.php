<?php

namespace Aecil\Verifactu\Enums;

/**
 * Define los tipos de impuestos aplicables en las facturas
 */
enum TipoImpuesto: string
{
    const IVA = '01';      // Impuesto sobre el Valor Añadido (IVA)

    const IPS = '02';      // Impuesto sobre la Producción, los Servicios y la Importación (IPSI) de Ceuta y Melilla

    const IPGIC = '03';    // Impuesto General Indirecto Canario (IGIC)

    const OTHER = '05';    // Otros
}
