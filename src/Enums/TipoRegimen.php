<?php

namespace Aecil\Verifactu\Enums;

/**
 * Define los diferentes regímenes especiales de IVA aplicables
 */
enum TipoRegimen: string
{
    /** Régimen general de IVA */
    const C01 = '01';

    /** Exportación */
    const C02 = '02';

    /** Régimen especial para bienes usados, arte y antigüedades */
    const C03 = '03';

    /** Régimen especial para oro de inversión */
    const C04 = '04';

    /** Régimen especial para agencias de viajes */
    const C05 = '05';

    /** Régimen especial de grupo de entidades (nivel avanzado) */
    const C06 = '06';

    /** Régimen especial del criterio de caja */
    const C07 = '07';

    /** Operaciones sujetas a IPSI o IGIC */
    const C08 = '08';

    /** Facturación de servicios de agencias de viaje como mediadoras */
    const C09 = '09';

    /** Cobros realizados en nombre de terceros */
    const C10 = '10';

    /** Arrendamiento de locales de negocio */
    const C11 = '11';

    /** Facturación con IVA pendiente de devengo para Administración Pública */
    const C14 = '14';

    /** Facturación con IVA pendiente de devengo en operaciones de tracto sucesivo */
    const C15 = '15';

    /** Operaciones acogidas a regímenes OSS e IOSS */
    const C17 = '17';

    /** Recargo de equivalencia */
    const C18 = '18';

    /** Operaciones incluidas en el REAGYP */
    const C19 = '19';

    /** Régimen simplificado */
    const C20 = '20';
}
