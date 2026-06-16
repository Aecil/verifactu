<?php

namespace Aecil\Verifactu\Enums;

/**
 * Regímenes especiales de IVA aplicables según normativa de la AEAT.
 */
enum TipoRegimen: string
{
    case C01 = '01'; // Régimen general de IVA
    case C02 = '02'; // Exportación
    case C03 = '03'; // Bienes usados, arte y antigüedades
    case C04 = '04'; // Oro de inversión
    case C05 = '05'; // Agencias de viajes
    case C06 = '06'; // Grupo de entidades (nivel avanzado)
    case C07 = '07'; // Criterio de caja
    case C08 = '08'; // Operaciones sujetas a IPSI o IGIC
    case C09 = '09'; // Facturación de servicios de agencias de viaje como mediadoras
    case C10 = '10'; // Cobros realizados en nombre de terceros
    case C11 = '11'; // Arrendamiento de locales de negocio
    case C14 = '14'; // IVA pendiente de devengo para Administración Pública
    case C15 = '15'; // IVA pendiente de devengo en operaciones de tracto sucesivo
    case C17 = '17'; // Operaciones acogidas a regímenes OSS e IOSS
    case C18 = '18'; // Recargo de equivalencia
    case C19 = '19'; // Operaciones incluidas en el REAGYP
    case C20 = '20'; // Régimen simplificado
}
