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
     * Devuelve un mensaje orientado al usuario final, traducido desde
     * el mensaje técnico de la AEAT cuando es posible.
     */
    public function userMessage(): string
    {
        if ($this->codigoError && isset(self::USER_MESSAGES[$this->codigoError])) {
            return self::USER_MESSAGES[$this->codigoError];
        }

        // Limpiar el prefijo técnico tipo "Cabecera emisor: " o "Cuerpo: "
        $cleaned = preg_replace('/^(Cabecera|Cuerpo|Emisor|Destinatario|IdFactura)\S*\s*:\s*/i', '', $this->descripcionError);

        return $cleaned ?: 'Error en el envío a la AEAT. Revise los datos e inténtelo de nuevo.';
    }

    private const USER_MESSAGES = [
        // Fechas
        '1111' => 'La fecha de expedición de la factura no puede ser anterior a la fecha de alta en el sistema.',
        '1112' => 'La fecha de expedición de la factura no puede ser superior a la fecha actual.',
        '1113' => 'La fecha de operación no puede ser posterior a la fecha de expedición.',
        '1114' => 'La fecha de operación no puede ser anterior en más de un año a la fecha de expedición.',

        // NIF
        '1100' => 'El NIF del emisor no es válido o no está dado de alta en la AEAT.',
        '1101' => 'El NIF del destinatario no es válido o no está dado de alta en la AEAT.',
        '1102' => 'El NIF del representante no es válido.',
        '1103' => 'El NIF del declarado no es válido.',

        // Identificador de factura
        '1200' => 'Ya existe una factura con ese número de serie y fecha.',
        '1201' => 'El número de serie de la factura no es válido.',
        '1202' => 'La factura rectificativa debe hacer referencia a una factura anterior existente.',

        // Totales e importes
        '1300' => 'El desglose de impuestos no coincide con los importes declarados.',
        '1301' => 'La cuota total no coincide con la suma de las cuotas de las líneas.',
        '1302' => 'La base imponible no puede ser negativa.',
        '1303' => 'El tipo impositivo aplicado no es válido.',

        // Encadenamiento
        '1400' => 'El encadenamiento de facturas no es correcto. Revise la huella del registro anterior.',
        '1401' => 'No se encuentra el registro anterior para realizar el encadenamiento.',

        // Sistema informático
        '1500' => 'Los datos del sistema informático emisor no son válidos.',
        '1501' => 'El NIF del fabricante del software no está dado de alta en la AEAT.',
    ];

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
