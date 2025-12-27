<?php

namespace Aecil\Verifactu\Models;

class CancelarFactura
{
    public string $idVersion = '1.0';

    public CabeceraFactura $cabecera;

    public IdFactura $idFactura;

    public \DateTime $FechaHoraHusoGenRegistro;

    public string $huella;

    public ?RegistroAnterior $registroAnterior = null;

    public SistemaInformatico $sistemaInformatico;

    public string $tipoHuella = '01';

    public function __construct(CabeceraFactura $cabecera, IdFactura $idFactura, \DateTime $FechaHoraHusoGenRegistro, string $huella, ?RegistroAnterior $registroAnterior = null)
    {
        $this->cabecera = $cabecera;
        $this->idFactura = $idFactura;
        $this->FechaHoraHusoGenRegistro = $FechaHoraHusoGenRegistro;
        $this->huella = $huella;
        $this->registroAnterior = $registroAnterior;
    }

    public function toArray(): array
    {
        $encadenamiento = [];
        if ($this->registroAnterior) {
            $encadenamiento = [
                'RegistroAnterior' => $this->registroAnterior->toArray(),
            ];
        } else {
            $encadenamiento = [
                'PrimerRegistro' => 'S',
            ];
        }

        return [
            'Cabecera' => $this->cabecera->toArray(),
            'RegistroFactura' => [
                'RegistroAnulacion' => [
                    'IDVersion' => $this->idVersion,
                    'IDFactura' => $this->idFactura->toArray(true),
                    'FechaHoraHusoGenRegistro' => $this->FechaHoraHusoGenRegistro->format('c'),
                    'Huella' => $this->huella,
                    'Encadenamiento' => $encadenamiento,
                    'SistemaInformatico' => $this->sistemaInformatico->toArray(),
                    'TipoHuella' => $this->tipoHuella,
                ],
            ],
        ];
    }

    /**
     * Calcula la huella digital de la factura según el algoritmo SHA-256
     *
     * @return string Huella en hexadecimal mayúsculas
     */
    public function calculateHuella(): string
    {
        $payload = 'IDEmisorFacturaAnulada='.$this->idFactura->idEmisorFactura
            .'&NumSerieFacturaAnulada='.$this->idFactura->numSerieFactura
            .'&FechaExpedicionFacturaAnulada='.$this->idFactura->fechaExpedicionFactura->format('d-m-Y')
            .'&Huella='.(! empty($this->huella) ? $this->huella : '')
            .'&FechaHoraHusoGenRegistro='.$this->FechaHoraHusoGenRegistro->format('c');

        return strtoupper(hash('sha256', $payload));
    }

    /**
     * Valida la cancelación de la factura verificando cabecera y registro de anulación
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
        if ($this->idFactura) {
            $errors = array_merge($errors, $this->idFactura->validate());
        } else {
            $errors[] = 'El ID de la factura es obligatorio';
        }
        if (! $this->FechaHoraHusoGenRegistro) {
            $errors[] = 'La fecha y hora de registro es obligatoria';
        }
        if (! $this->huella) {
            $errors[] = 'La huella es obligatoria';
        }

        return $errors;
    }
}
