<?php

namespace Aecil\Verifactu\Exceptions;

/**
 * Excepción para errores de validación local de los modelos antes del envío.
 */
class ValidationException extends VerifactuException
{
    private array $errors;

    public function __construct(array $errors, string $message = 'Error de validación')
    {
        parent::__construct($message . ': ' . implode('; ', $errors));
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
