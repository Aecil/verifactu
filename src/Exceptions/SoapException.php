<?php

namespace Aecil\Verifactu\Exceptions;

/**
 * Excepción para errores de transporte SOAP (conexión, timeout, etc.).
 */
class SoapException extends VerifactuException
{
    public static function fromFault(\SoapFault $fault): self
    {
        return new self(
            message: $fault->getMessage(),
            code: (int) $fault->getCode(),
            previous: $fault,
        );
    }
}
