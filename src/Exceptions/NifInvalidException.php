<?php

namespace Aecil\Verifactu\Exceptions;

/**
 * Excepción para cuando el NIF del emisor o destinatario no es válido según Verifactu.
 *
 * Ejemplo: "Cabecera emisor: El NIF debe tener exactamente 9 caracteres"
 */
class NifInvalidException extends ApiErrorException
{
}
