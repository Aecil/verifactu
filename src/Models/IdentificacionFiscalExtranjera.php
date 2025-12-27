<?php

namespace Aecil\Verifactu\Models;

use Aecil\Verifactu\Enums\TipoIdentificacionExtranjera;

/**
 * Representa un identificador fiscal extranjero
 *
 * @field Cabecera/ObligadoEmision
 * @field Cabecera/Representante
 * @field RegistroAlta/Tercero
 * @field IDDestinatario
 */
class IdentificacionFiscalExtranjera
{
    /**
     * Razón social o nombre completo
     *
     * @field NombreRazon
     */
    public string $nombreRazon;

    /**
     * Código ISO del país (formato de 2 letras)
     *
     * @field IDOtro/CodigoPais
     */
    public string $codigoPais;

    /**
     * Tipo de identificación según el país
     *
     * @field IDOtro/IDType
     *
     * @var string
     */
    public TipoIdentificacionExtranjera $tipoIdentificacionExtranjera;

    /**
     * Valor del identificador fiscal extranjero
     *
     * @field IDOtro/ID
     */
    public string $numeroIdentificacion;

    /**
     * Crea una nueva instancia de identificador fiscal extranjero
     *
     * @param  string  $nombreRazon  Nombre o razón social
     * @param  string  $codigoPais  Código de país ISO 3166-1 alpha-2
     * @param  string  $type  Tipo de identificación
     * @param  string  $numeroIdentificacion  Número de identificación
     */
    public function __construct(string $nombreRazon, string $codigoPais, TipoIdentificacionExtranjera $tipoIdentificacionExtranjera, string $numeroIdentificacion)
    {
        $this->nombreRazon = $nombreRazon;
        $this->codigoPais = $codigoPais;
        $this->tipoIdentificacionExtranjera = $tipoIdentificacionExtranjera;
        $this->numeroIdentificacion = $numeroIdentificacion;
    }

    /**
     * Valida los datos del identificador fiscal extranjero
     *
     * @param  string  $prefix  Prefijo para los mensajes de error
     * @return array Lista de errores encontrados
     */
    public function validate(string $prefix = ''): array
    {
        $errors = [];
        if (! $this->nombreRazon || trim($this->nombreRazon) === '') {
            $errors[] = $prefix.'El nombre es obligatorio';
        }
        if (strlen($this->nombreRazon) > 120) {
            $errors[] = $prefix.'El nombre no puede exceder 120 caracteres';
        }
        if (! $this->codigoPais || ! preg_match('/^[A-Z]{2}$/', $this->codigoPais)) {
            $errors[] = $prefix.'El código de país debe ser de 2 letras mayúsculas (ISO 3166-1)';
        }
        if ($this->codigoPais === 'ES') {
            $errors[] = $prefix.'El código de país no puede ser "ES", use IdentificacionFiscal para identificadores españoles';
        }
        if (! $this->tipoIdentificacionExtranjera || trim($this->tipoIdentificacionExtranjera) === '') {
            $errors[] = $prefix.'El tipo de identificación es obligatorio';
        }
        if (! $this->numeroIdentificacion || trim($this->numeroIdentificacion) === '') {
            $errors[] = $prefix.'El número de identificación es obligatorio';
        }
        if (strlen($this->numeroIdentificacion) > 20) {
            $errors[] = $prefix.'El número de identificación no puede exceder 20 caracteres';
        }

        return $errors;
    }

    /**
     * Transforma el identificador a formato array
     */
    public function toArray(): array
    {
        return [
            'IDOtro' => [
                'CodigoPais' => $this->codigoPais,
                'IDType' => $this->tipoIdentificacionExtranjera,
                'ID' => $this->numeroIdentificacion,
            ],
        ];
    }
}
