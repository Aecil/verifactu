<?php

namespace Aecil\Verifactu\Models;

/**
 * Almacena la información de un registro anterior relacionado con la factura
 */
class RegistroAnterior
{
    public string $idEmisorFactura;

    public string $numSerieFactura;

    public \DateTime $fechaExpedicionFactura;

    public string $huella;

    public function __construct(string $idEmisorFactura, string $numSerieFactura, \DateTime $fechaExpedicionFactura, string $huella)
    {
        $this->idEmisorFactura = $idEmisorFactura;
        $this->numSerieFactura = $numSerieFactura;
        $this->fechaExpedicionFactura = $fechaExpedicionFactura;
        $this->huella = $huella;
    }

    /**
     * Convierte el registro anterior a formato array
     */
    public function toArray(): array
    {
        return [
            'IDEmisorFactura' => $this->idEmisorFactura,
            'NumSerieFactura' => $this->numSerieFactura,
            'FechaExpedicionFactura' => $this->fechaExpedicionFactura->format('d-m-Y'),
            'Huella' => $this->huella,
        ];
    }
}
