<?php

namespace Aecil\Verifactu\Enums;

/**
 * Tipos de impuesto aplicables en las facturas.
 */
enum TipoImpuesto: string
{
    case IVA = '01';      // Impuesto sobre el Valor Añadido
    case IPSI = '02';     // Impuesto sobre la Producción, los Servicios y la Importación (Ceuta y Melilla)
    case IGIC = '03';     // Impuesto General Indirecto Canario
    case OTHER = '05';    // Otros

    /** @deprecated usar IPSI */
    public const IPS = self::IPSI;
    /** @deprecated usar IGIC */
    public const IPGIC = self::IGIC;
}
