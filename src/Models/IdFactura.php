<?php

namespace Aecil\Verifactu\Models;

/**
 * Contiene la información que identifica una factura de forma única
 */
class IdFactura
{
    public string $idEmisorFactura;

    public string $numSerieFactura;

    public \DateTime $fechaExpedicionFactura;

    public function __construct(string $idEmisorFactura, string $numSerieFactura, \DateTime $fechaExpedicionFactura)
    {
        $this->idEmisorFactura = $idEmisorFactura;
        $this->numSerieFactura = $numSerieFactura;
        $this->fechaExpedicionFactura = $fechaExpedicionFactura;
    }

    /**
     * Valida los datos del identificador de factura
     *
     * @param  string  $prefix  Prefijo para los mensajes de error
     * @return array Lista de errores encontrados
     */
    public function validate(string $prefix = ''): array
    {
        $errors = [];
        if (! $this->idEmisorFactura) {
            $errors[] = $prefix.'El ID del emisor es obligatorio';
        } elseif (strlen($this->idEmisorFactura) !== 9) {
            $errors[] = $prefix.'El ID del emisor debe tener 9 caracteres';
        }
        if (! $this->numSerieFactura) {
            $errors[] = $prefix.'El número de serie es obligatorio';
        } elseif (strlen($this->numSerieFactura) > 60) {
            $errors[] = $prefix.'El número de serie no puede exceder 60 caracteres';
        }
        if (! $this->fechaExpedicionFactura) {
            $errors[] = $prefix.'La fecha de expedición es obligatoria';
        } elseif (! ($this->fechaExpedicionFactura instanceof \DateTime)) {
            $errors[] = $prefix.'La fecha debe ser una instancia válida de DateTime';
        }

        return $errors;
    }

    /**
     * Convierte el identificador a formato array
     */
    public function toArray($isAnulada = false): array
    {
        if ($isAnulada) {
            return $this->toArrayAnulada();
        }

        return [
            'IDEmisorFactura' => $this->idEmisorFactura,
            'NumSerieFactura' => $this->numSerieFactura,
            'FechaExpedicionFactura' => $this->fechaExpedicionFactura->format('d-m-Y'),
        ];
    }

    public function toArrayAnulada(): array
    {
        return [
            'IDEmisorFacturaAnulada' => $this->idEmisorFactura,
            'NumSerieFacturaAnulada' => $this->numSerieFactura,
            'FechaExpedicionFacturaAnulada' => $this->fechaExpedicionFactura->format('d-m-Y'),
        ];
    }
}
