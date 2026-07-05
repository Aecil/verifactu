<?php

namespace Aecil\Verifactu\Models;

use Aecil\Verifactu\Enums\TipoFactura;
use Aecil\Verifactu\Enums\TipoRectificativa;

/**
 * Contiene el contenido principal de la factura con todos sus datos
 */
class CuerpoFactura
{
    public string $idVersion = '1.0';

    public IdFactura $idFactura;

    public string $nombreRazonEmisor;

    /** @var TipoFactura */
    public string $tipoFactura;

    public string $descripcionOperacion;

    public ?\DateTime $fechaOperacion = null;

    /** @var IdentificacionFiscal[]|IdentificacionFiscalExtranjera[] */
    public array $destinatarios;

    public string $cuotaTotal; // TOTAL IMPUESTOS FACTURA, STRING 2 DECIMALES

    public string $importeTotal; // TOTAL FACTURA, STRING 2 DECIMALES

    public SistemaInformatico $sistemaInformatico;

    /** @var LineaFactura[] */
    public array $desglose;

    public string $tipoHuella = '01';

    public ?RegistroAnterior $registroAnterior = null;

    /** @var TipoRectificativa */
    public ?string $tipoRectificativa = null;

    /** @var IdFactura[] */
    public ?array $facturasSustituidas = []; // Solo para facturas sustitutivas

    public ?string $baseRectificada = null; // Solo para facturas rectificativas

    public ?string $cuotaRectificada = null; // Solo para facturas rectificativas

    public ?string $cuotaRecargoRectificado = null; // Recargo de equivalencia rectificado (XSD: CuotaRecargoRectificado)

    /**
     * Valida todos los datos del cuerpo de la factura
     *
     * @return array Lista de errores encontrados
     */
    public function validate(): array
    {
        $errors = [];
        if ($this->idFactura) {
            $errors = $this->idFactura->validate('Cuerpo idFactura: ');
        } else {
            $errors[] = 'Cuerpo idFactura: El identificador de factura es obligatorio';
        }
        if (! $this->nombreRazonEmisor) {
            $errors[] = 'Cuerpo nombreRazonEmisor: El nombre o razón social del emisor es obligatorio';
        } elseif (strlen($this->nombreRazonEmisor) > 120) {
            $errors[] = 'Cuerpo nombreRazonEmisor: El nombre no puede exceder 120 caracteres';
        }
        if (! $this->descripcionOperacion) {
            $errors[] = 'Cuerpo descripcionOperacion: La descripción de la operación es obligatoria';
        } elseif (strlen($this->descripcionOperacion) > 500) {
            $errors[] = 'Cuerpo descripcionOperacion: La descripción no puede exceder 500 caracteres';
        }
        if (! $this->tipoFactura) {
            $errors[] = 'Cuerpo tipoFactura: El tipo de factura es obligatorio';
        } elseif (TipoFactura::tryFrom($this->tipoFactura)?->isRectificativa()) {
            if (! $this->tipoRectificativa) {
                $errors[] = 'Cuerpo tipoRectificativa: El tipo de rectificativa es obligatorio para facturas rectificativas';
            }
        }
        if (! $this->destinatarios) {
            $errors[] = 'Cuerpo destinatarios: Debe haber al menos un destinatario';
        } elseif (count($this->destinatarios) === 0) {
            $errors[] = 'Cuerpo destinatarios: Debe haber al menos un destinatario';
        } else {
            $destinatariosErrors = [];
            for ($index = 0; $index < count($this->destinatarios); $index++) {
                $destinatario = $this->destinatarios[$index];
                if (method_exists($destinatario, 'validate')) {
                    $destinatariosErrors = array_merge($destinatariosErrors, $destinatario->validate('Cuerpo destinatarios: '.$index.': '));
                }
            }
            if (count($destinatariosErrors) > 0) {
                $errors = array_merge($errors, $destinatariosErrors);
            }
        }
        if (! isset($this->cuotaTotal)) {
            $errors[] = 'Cuerpo cuotaTotal: La cuota total es obligatoria';
        }
        if (! isset($this->importeTotal)) {
            $errors[] = 'Cuerpo importeTotal: El importe total es obligatorio';
        }

        // Validar que importeTotal = sum(bases + cuotas) del desglose
        $totalesErrors = $this->validateTotales();
        if (\count($totalesErrors) > 0) {
            $errors = array_merge($errors, $totalesErrors);
        }
        if (! $this->sistemaInformatico) {
            $errors[] = 'Cuerpo sistemaInformatico: El sistema informático es obligatorio';
        }
        if (! $this->desglose) {
            $errors[] = 'Cuerpo desglose: El desglose es obligatorio';
        } elseif (count($this->desglose) === 0) {
            $errors[] = 'Cuerpo desglose: Debe haber al menos una línea en el desglose';
        } elseif (count($this->desglose) > 12) {
            $errors[] = 'Cuerpo desglose: El desglose no puede tener más de 12 líneas';
        } else {
            $desgloseErrors = [];
            for ($index = 0; $index < count($this->desglose); $index++) {
                $desglose = $this->desglose[$index];
                if (method_exists($desglose, 'validate')) {
                    $desgloseErrors = array_merge($desgloseErrors, $desglose->validate('Cuerpo desglose linea '.($index + 1).': '));
                }
            }
            if (count($desgloseErrors) > 0) {
                $errors = array_merge($errors, $desgloseErrors);
            }
        }

        return $errors;
    }

    /**
     * Convierte el cuerpo de la factura a formato array
     */
    public function toArray(): array
    {
        $ahora = new \DateTime;

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
        $data = [
            'IDVersion' => $this->idVersion,
            'IDFactura' => $this->idFactura->toArray(),
            'NombreRazonEmisor' => $this->nombreRazonEmisor,
            'TipoFactura' => $this->tipoFactura,
            'DescripcionOperacion' => $this->descripcionOperacion,
            'Destinatarios' => array_map(fn ($d) => $d->toArray(), $this->destinatarios),
            'CuotaTotal' => $this->cuotaTotal,
            'ImporteTotal' => $this->importeTotal,
            'SistemaInformatico' => method_exists($this->sistemaInformatico, 'toArray') ? $this->sistemaInformatico->toArray() : $this->sistemaInformatico,
            'Desglose' => [
                'DetalleDesglose' => array_map(fn ($dg) => $dg->toArray(), $this->desglose),
            ],
            'TipoHuella' => $this->tipoHuella,
            'Huella' => $this->calculateHuella($ahora),
            'Encadenamiento' => $encadenamiento,
            'FechaHoraHusoGenRegistro' => $ahora->format('c'),
        ];
        if ($this->facturaIsRectificativa()) {
            $data['TipoRectificativa'] = $this->tipoRectificativa;
        }
        if (count($this->facturasSustituidas) > 0) {
            $data['FacturasSustituidas'] = [];
            foreach ($this->facturasSustituidas as $factura) {
                $data['FacturasSustituidas'][] = [
                    'IDFacturaSustituida' => $factura->toArray(),
                ];
            }
        }
        if (! empty($this->fechaOperacion)) {
            $data['FechaOperacion'] = $this->fechaOperacion->format('d-m-Y');
        }
        if ($this->baseRectificada !== null && $this->cuotaRectificada !== null) {
            $data['ImporteRectificacion'] = [
                'BaseRectificada' => $this->baseRectificada,
                'CuotaRectificada' => $this->cuotaRectificada,
            ];
            if ($this->cuotaRecargoRectificado !== null) {
                $data['ImporteRectificacion']['CuotaRecargoRectificado'] = $this->cuotaRecargoRectificado;
            }
        }

        return $data;
    }

    /**
     * Calcula la huella digital de la factura según el algoritmo SHA-256
     *
     * @return string Huella en hexadecimal mayúsculas
     */
    public function calculateHuella(?\DateTime $fechaHora = null): string
    {
        $fechaHora = $fechaHora ?? new \DateTime;

        $payload = 'IDEmisorFactura='.$this->idFactura->idEmisorFactura
            .'&NumSerieFactura='.$this->idFactura->numSerieFactura
            .'&FechaExpedicionFactura='.$this->idFactura->fechaExpedicionFactura->format('d-m-Y')
            .'&TipoFactura='.$this->tipoFactura
            .'&CuotaTotal='.$this->cuotaTotal
            .'&ImporteTotal='.$this->importeTotal
            .'&Huella='.($this->registroAnterior ? $this->registroAnterior->huella : '')
            .'&FechaHoraHusoGenRegistro='.$fechaHora->format('c');

        return strtoupper(hash('sha256', $payload));
    }

    /**
     * Valida que importeTotal y cuotaTotal coincidan con la suma del desglose.
     */
    private function validateTotales(): array
    {
        $errors = [];

        if (empty($this->desglose) || ! isset($this->importeTotal) || ! isset($this->cuotaTotal)) {
            return $errors;
        }

        $sumaBases = 0.0;
        $sumaCuotas = 0.0;

        foreach ($this->desglose as $index => $linea) {
            $sumaBases += (float) $linea->baseImponibleOimporteNoSujeto;
            if ($linea->cuotaRepercutida !== null) {
                $sumaCuotas += (float) $linea->cuotaRepercutida;
            }
            // La AEAT incluye el recargo de equivalencia en CuotaTotal e ImporteTotal
            if ($linea->cuotaRecargoEquivalencia !== null) {
                $sumaCuotas += (float) $linea->cuotaRecargoEquivalencia;
            }
        }

        $sumaTotal = $sumaBases + $sumaCuotas;
        $importeTotal = (float) $this->importeTotal;
        $cuotaTotal = (float) $this->cuotaTotal;

        // Tolerancia de 1 céntimo por línea
        $tolerancia = 0.01 * max(1, \count($this->desglose));

        if (abs($importeTotal - $sumaTotal) > $tolerancia) {
            $errors[] = sprintf(
                'Cuerpo importeTotal: El importe total (%.2f) no coincide con la suma de bases + cuotas del desglose (%.2f). Diferencia: %.2f',
                $importeTotal,
                $sumaTotal,
                $importeTotal - $sumaTotal
            );
        }

        if (abs($cuotaTotal - $sumaCuotas) > $tolerancia) {
            $errors[] = sprintf(
                'Cuerpo cuotaTotal: La cuota total (%.2f) no coincide con la suma de cuotas del desglose (%.2f). Diferencia: %.2f',
                $cuotaTotal,
                $sumaCuotas,
                $cuotaTotal - $sumaCuotas
            );
        }

        return $errors;
    }

    private function facturaIsRectificativa(): bool
    {
        return TipoFactura::tryFrom($this->tipoFactura)?->isRectificativa() ?? false;
    }
}
