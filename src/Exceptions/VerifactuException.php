<?php

namespace Aecil\Verifactu\Exceptions;

/**
 * Excepción base para todos los errores del paquete Verifactu.
 */
class VerifactuException extends \RuntimeException
{
    protected array $context = [];

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
