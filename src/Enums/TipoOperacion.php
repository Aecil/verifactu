<?php

namespace Aecil\Verifactu\Enums;

/**
 * Enumeración de tipos de operación según la normativa de IVA
 */
enum TipoOperacion: string
{
    /** Operación sujeta sin inversión del sujeto pasivo */
    const Subject = 'S1';

    /** Operación sujeta con inversión del sujeto pasivo */
    const PassiveSubject = 'S2';

    /** Operación no sujeta según artículos 7 y 14 */
    const NonSubject = 'N1';

    /** Operación no sujeta por criterios de localización */
    const NonSubjectByLocation = 'N2';

    /** Operación exenta según artículo 20 */
    const ExemptByArticle20 = 'E1';

    /** Operación exenta según artículo 21 */
    const ExemptByArticle21 = 'E2';

    /** Operación exenta según artículo 22 */
    const ExemptByArticle22 = 'E3';

    /** Operación exenta según artículos 23 y 24 */
    const ExemptByArticles23And24 = 'E4';

    /** Operación exenta según artículo 25 */
    const ExemptByArticle25 = 'E5';

    /** Operación exenta por otros motivos */
    const ExemptByOther = 'E6';
}
