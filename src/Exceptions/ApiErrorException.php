<?php

namespace Aecil\Verifactu\Exceptions;

use Aecil\Verifactu\Enums\VerifactuRespuestas;

/**
 * Excepción para errores devueltos por la API de Verifactu (AEAT).
 *
 * El factory fromResponse() analiza la respuesta SOAP y devuelve
 * la subclase más específica según el mensaje de error.
 */
class ApiErrorException extends VerifactuException
{
    public readonly VerifactuRespuestas $estado;
    public readonly string $codigoError;
    public readonly string $descripcionError;
    public readonly ?string $csv;

    public function __construct(
        string $message,
        VerifactuRespuestas $estado,
        string $codigoError = '',
        string $descripcionError = '',
        ?string $csv = null,
        array $context = [],
    ) {
        parent::__construct($message, 0, null, $context);
        $this->estado = $estado;
        $this->codigoError = $codigoError;
        $this->descripcionError = $descripcionError;
        $this->csv = $csv;
    }

    /**
     * Crea la excepción adecuada a partir de la respuesta SOAP de Verifactu.
     *
     * Analiza DescripcionErrorRegistro para instanciar la subclase más concreta.
     */
    public static function fromResponse(object $response, array $context = []): self
    {
        $estadoRaw = strtoupper(str_replace(' ', '', $response->EstadoEnvio ?? ''));
        $estado = VerifactuRespuestas::tryFrom($estadoRaw) ?? VerifactuRespuestas::INCORRECTO;

        $descripcion = '';
        $codigo = '';

        $linea = $response->RespuestaLinea ?? null;
        if (is_array($linea)) {
            $linea = $linea[0] ?? null;
        }
        if ($linea) {
            $descripcion = $linea->DescripcionErrorRegistro ?? '';
            $codigo = $linea->CodigoErrorRegistro ?? '';
        }

        $csv = $response->CSV ?? null;

        // Determinar la excepción más específica según el mensaje
        $exceptionClass = self::resolveExceptionClass($descripcion, $codigo);

        return new $exceptionClass(
            message: $descripcion ?: 'Error desconocido en la respuesta de Verifactu',
            estado: $estado,
            codigoError: $codigo,
            descripcionError: $descripcion,
            csv: $csv,
            context: $context,
        );
    }

    /**
     * Resuelve la clase de excepción más concreta según el mensaje de error.
     */
    private static function resolveExceptionClass(string $descripcion, string $codigo): string
    {
        $normalized = strtolower($descripcion . ' ' . $codigo);

        if (str_contains($normalized, 'nif') && (
            str_contains($normalized, '9 caracteres')
            || str_contains($normalized, 'inválido')
            || str_contains($normalized, 'invalido')
            || str_contains($normalized, 'no válido')
            || str_contains($normalized, 'no valido')
        )) {
            return NifInvalidException::class;
        }

        if (str_contains($normalized, 'ya está registrada')
            || str_contains($normalized, 'ya esta registrada')
            || str_contains($normalized, 'registrada previamente')
            || str_contains($normalized, 'duplicada')
        ) {
            return InvoiceAlreadyRegisteredException::class;
        }

        return self::class;
    }
}
