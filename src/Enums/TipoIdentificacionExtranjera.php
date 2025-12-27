<?php

namespace Aecil\Verifactu\Enums;

enum TipoIdentificacionExtranjera: string
{
    /** Número de identificación a efectos de IVA (NIF-IVA) */
    case VAT = '02';

    /** Documento de identificación emitido como pasaporte */
    case Passport = '03';

    /** Documento oficial de identidad emitido por el país o territorio de residencia */
    case NationalId = '04';

    /** Certificado que acredita la residencia en otro país */
    case Residence = '05';

    /** Cualquier otro documento válido que sirva como prueba de identidad */
    case Other = '06';

    /**
     * Contribuyente no inscrito en el censo de la AEAT.
     *
     * Nota: Al utilizar este valor, será necesario actualizar el registro más adelante
     * para indicar el tipo de identificación correcto.
     */
    case Unregistered = '07';
}
