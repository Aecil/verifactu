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
     * Crea un RegistroAnterior desde un IdFactura y la huella de la respuesta SOAP.
     */
    public static function fromIdFactura(IdFactura $idFactura, string $huella): self
    {
        return new self(
            $idFactura->idEmisorFactura,
            $idFactura->numSerieFactura,
            $idFactura->fechaExpedicionFactura,
            $huella
        );
    }

    /**
     * Crea un RegistroAnterior desde los datos crudos de una respuesta SOAP exitosa.
     *
     * @param  object  $response  Respuesta SOAP parseada (stdClass)
     */
    public static function fromSoapResponse(object $response): ?self
    {
        $linea = $response->RespuestaLinea ?? null;
        if (! $linea) {
            return null;
        }

        $idFactura = $linea->IDFactura ?? null;
        $huella = $linea->Huella ?? null;

        if (! $idFactura || ! $huella) {
            return null;
        }

        $fecha = \DateTime::createFromFormat('d-m-Y', $idFactura->FechaExpedicionFactura ?? '');

        return new self(
            $idFactura->IDEmisorFactura ?? '',
            $idFactura->NumSerieFactura ?? '',
            $fecha ?: new \DateTime,
            $huella
        );
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
