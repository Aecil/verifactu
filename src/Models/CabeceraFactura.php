<?php

namespace Aecil\Verifactu\Models;

/**
 * Encabezado de la factura con información del emisor y representante
 */
class CabeceraFactura
{
    public IdentificacionFiscal $emisor;

    public ?IdentificacionFiscal $representante = null;

    public function __construct(?IdentificacionFiscal $emisor = null, ?IdentificacionFiscal $representante = null)
    {
        if ($emisor) {
            $this->emisor = $emisor;
        }
        if ($representante) {
            $this->representante = $representante;
        }
    }

    /**
     * Valida los datos de la cabecera
     *
     * @return array Lista de errores encontrados
     */
    public function validate(): array
    {
        $errors = [];
        if ($this->emisor) {
            $errors = $this->emisor->validate('Cabecera emisor: ');
        } else {
            $errors[] = 'Cabecera emisor: El emisor es obligatorio';
        }
        if ($this->representante) {
            $errors = $this->representante->validate('Cabecera representante: ');
        }

        return $errors;
    }

    /**
     * Convierte la cabecera a un array asociativo
     */
    public function toArray(): array
    {
        return [
            'ObligadoEmision' => $this->emisor ? $this->emisor->toArray() : null,
            'Representante' => $this->representante ? $this->representante->toArray() : null,
        ];
    }
}
