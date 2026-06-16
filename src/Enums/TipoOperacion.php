<?php

namespace Aecil\Verifactu\Enums;

/**
 * Tipos de operación según la normativa de IVA.
 */
enum TipoOperacion: string
{
    case S1 = 'S1'; // Operación sujeta sin inversión del sujeto pasivo
    case S2 = 'S2'; // Operación sujeta con inversión del sujeto pasivo
    case N1 = 'N1'; // Operación no sujeta según artículos 7 y 14
    case N2 = 'N2'; // Operación no sujeta por criterios de localización
    case E1 = 'E1'; // Operación exenta según artículo 20
    case E2 = 'E2'; // Operación exenta según artículo 21
    case E3 = 'E3'; // Operación exenta según artículo 22
    case E4 = 'E4'; // Operación exenta según artículos 23 y 24
    case E5 = 'E5'; // Operación exenta según artículo 25
    case E6 = 'E6'; // Operación exenta por otros motivos

    /** @deprecated usar S1 */
    public const Subject = self::S1;
    /** @deprecated usar S2 */
    public const PassiveSubject = self::S2;
    /** @deprecated usar N1 */
    public const NonSubject = self::N1;
    /** @deprecated usar N2 */
    public const NonSubjectByLocation = self::N2;
    /** @deprecated usar E1 */
    public const ExemptByArticle20 = self::E1;
    /** @deprecated usar E2 */
    public const ExemptByArticle21 = self::E2;
    /** @deprecated usar E3 */
    public const ExemptByArticle22 = self::E3;
    /** @deprecated usar E4 */
    public const ExemptByArticles23And24 = self::E4;
    /** @deprecated usar E5 */
    public const ExemptByArticle25 = self::E5;
    /** @deprecated usar E6 */
    public const ExemptByOther = self::E6;

    public function isExenta(): bool
    {
        return \in_array($this, [self::E1, self::E2, self::E3, self::E4, self::E5, self::E6], true);
    }
}
