<?php

namespace Aecil\Verifactu\Models;

/**
 * Representa un identificador fiscal español (NIF)
 *
 * @field Cabecera/ObligadoEmision
 * @field Cabecera/Representante
 */
class IdentificacionFiscal
{
    /**
     * Razón social o nombre completo
     *
     * @field NombreRazon
     */
    public string $name;

    /**
     * Número de identificación fiscal español
     *
     * @field NIF
     */
    public string $nif;

    /**
     * Crea una nueva instancia de identificador fiscal
     *
     * @param  string  $name  Nombre o razón social (hasta 120 caracteres)
     * @param  string  $nif  NIF de 9 caracteres
     */
    public function __construct(string $name, string $nif)
    {
        $this->name = $name;
        $this->nif = $nif;
    }

    /**
     * Valida los datos del identificador fiscal
     *
     * @param  string  $prefix  Prefijo para los mensajes de error
     * @return array Lista de errores encontrados
     */
    public function validate(string $prefix = ''): array
    {
        $errors = [];
        if (! $this->name || trim($this->name) === '') {
            $errors[] = $prefix.'El nombre es obligatorio';
        } elseif (strlen($this->name) > 120) {
            $errors[] = $prefix.'El nombre no puede exceder 120 caracteres';
        }
        if (! $this->nif || trim($this->nif) === '') {
            $errors[] = $prefix.'El NIF es obligatorio';
        } elseif (strlen($this->nif) !== 9) {
            $errors[] = $prefix.'El NIF debe tener exactamente 9 caracteres';
        }

        return $errors;
    }

    /**
     * Transforma el identificador fiscal a formato array
     */
    public function toArray(): array
    {
        return [
            'NombreRazon' => $this->name,
            'NIF' => $this->nif,
        ];
    }
}
