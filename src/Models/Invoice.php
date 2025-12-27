<?php

namespace Aecil\Verifactu\Models;

/**
 * Representa una factura completa con su cabecera y cuerpo
 */
class Invoice
{
    public CabeceraFactura $cabecera;

    public CuerpoFactura $cuerpo;

    public function __construct(CabeceraFactura $cabecera, CuerpoFactura $cuerpo)
    {
        $this->cabecera = $cabecera;
        $this->cuerpo = $cuerpo;
    }

    /**
     * Valida la factura completa verificando cabecera y cuerpo
     *
     * @return array Lista de errores encontrados
     */
    public function validate(): array
    {
        $errors = [];
        if ($this->cabecera) {
            $errors = $this->cabecera->validate();
        } else {
            $errors[] = 'La cabecera es obligatoria';
        }
        if ($this->cuerpo) {
            $errors = array_merge($errors, $this->cuerpo->validate());
        } else {
            $errors[] = 'El cuerpo es obligatorio';
        }

        return $errors;
    }

    /**
     * Convierte la factura completa a formato array
     */
    public function toArray(): array
    {
        return [
            'Cabecera' => $this->cabecera->toArray(),
            'RegistroFactura' => [
                'RegistroAlta' => $this->cuerpo->toArray(),
            ],
        ];
    }
}
