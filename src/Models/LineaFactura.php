<?php

namespace Aecil\Verifactu\Models;

use Aecil\Verifactu\Enums\TipoImpuesto;
use Aecil\Verifactu\Enums\TipoOperacion;
use Aecil\Verifactu\Enums\TipoRegimen;

/**
 * Representa una línea de desglose de impuestos en la factura
 *
 * @field DetalleDesglose
 */
class LineaFactura
{
    /** @var TipoImpuesto */
    public string $tipoImpuesto;

    /** @var TipoRegimen */
    public string $claveRegimen;

    /** @var TipoOperacion */
    public string $calificacionOperacion;

    public string $baseImponibleOimporteNoSujeto;

    public ?string $tipoImpositivo = null;

    public ?string $cuotaRepercutida = null;

    public ?string $tipoRecargoEquivalencia = null;

    public ?string $cuotaRecargoEquivalencia = null;

    public function __construct(string $baseImponibleOimporteNoSujeto, string $cuotaRepercutida, string $tipoImpositivo = '21.00', string $tipoImpuesto = TipoImpuesto::IVA->value, string $claveRegimen = TipoRegimen::C01->value, string $calificacionOperacion = TipoOperacion::Subject->value)
    {
        $this->tipoImpuesto = $tipoImpuesto;
        $this->claveRegimen = $claveRegimen;
        $this->calificacionOperacion = $calificacionOperacion;
        $this->baseImponibleOimporteNoSujeto = $baseImponibleOimporteNoSujeto;
        $this->tipoImpositivo = $tipoImpositivo;
        $this->cuotaRepercutida = $cuotaRepercutida;
    }

    /**
     * Valida los datos de la línea de factura
     *
     * @param  string  $prefix  Prefijo para los mensajes de error
     * @return array Lista de errores encontrados
     */
    public function validate(string $prefix = ''): array
    {
        $errors = [];
        if (! $this->tipoImpuesto) {
            $errors[] = $prefix.'El tipo de impuesto es obligatorio';
        }
        if (! $this->claveRegimen) {
            $errors[] = $prefix.'La clave de régimen es obligatoria';
        }
        if (! $this->calificacionOperacion) {
            $errors[] = $prefix.'La calificación de operación es obligatoria';
        }
        if (! $this->baseImponibleOimporteNoSujeto) {
            $errors[] = $prefix.'La base imponible o importe no sujeto es obligatorio';
        }
        // Las líneas EXENTAS (OperacionExenta E1-E6) y las NO SUJETAS (N1/N2)
        // deben ir SIN tipo ni cuota; solo son obligatorios en sujetas S1.
        if ($this->calificacionOperacion === TipoOperacion::S1->value) {
            if ($this->tipoImpositivo === null || $this->tipoImpositivo === '') {
                $errors[] = $prefix.'El tipo impositivo es obligatorio';
            }
            if ($this->cuotaRepercutida === null || $this->cuotaRepercutida === '') {
                $errors[] = $prefix.'La cuota repercutida es obligatoria';
            }
        }
        $operationTypeError = $this->validateOperationType();
        if ($operationTypeError) {
            $errors[] = $prefix.$operationTypeError;
        }
        $taxAmountError = $this->validateTaxAmount();
        if ($taxAmountError) {
            $errors[] = $prefix.$taxAmountError;
        }

        return $errors;
    }

    public function validateOperationType(): ?string
    {
        if (! isset($this->calificacionOperacion)) {
            return null;
        }
        // Tipo y cuota solo son obligatorios en operaciones sujetas S1; las
        // exentas (E1-E6) y las no sujetas (N1/N2) deben ir sin ellos.
        if ($this->calificacionOperacion === TipoOperacion::S1->value) {
            if ($this->tipoImpositivo === null) {
                return 'El tipo impositivo es obligatorio para operaciones sujetas';
            }
            if ($this->cuotaRepercutida === null) {
                return 'La cuota repercutida es obligatoria para operaciones sujetas';
            }
        }

        return null;
    }

    public function validateTaxAmount(): ?string
    {
        if (
            ! isset($this->baseImponibleOimporteNoSujeto)
            || $this->tipoImpositivo === null
            || $this->cuotaRepercutida === null
            || $this->lineaExentaIva()
        ) {
            return null;
        }
        $base = floatval($this->baseImponibleOimporteNoSujeto);
        $rate = floatval($this->tipoImpositivo);
        $taxAmountValue = $this->cuotaRepercutida;
        $bestTaxAmount = $base * ($rate / 100);
        $tolerances = [0, -0.01, 0.01, -0.02, 0.02];
        $validTaxAmount = false;
        foreach ($tolerances as $tolerance) {
            $expected = number_format($bestTaxAmount + $tolerance, 2, '.', '');
            if ($taxAmountValue === $expected) {
                $validTaxAmount = true;
                break;
            }
        }
        if (! $validTaxAmount) {
            $best = number_format($bestTaxAmount, 2, '.', '');

            return "La cuota esperada es $best, pero se ha proporcionado $taxAmountValue ya que la base es $base y el tipo impositivo es $rate";
        }

        return null;
    }

    /**
     * Convierte la línea de factura a formato array
     */
    public function toArray(): array
    {
        $data = [
            'Impuesto' => $this->tipoImpuesto,
            'ClaveRegimen' => $this->claveRegimen,
            'BaseImponibleOimporteNoSujeto' => $this->baseImponibleOimporteNoSujeto,
        ];
        if ($this->lineaExentaIva()) {
            $data['OperacionExenta'] = $this->calificacionOperacion;
        } else {
            // Las no sujetas (N1/N2) van SIN tipo ni cuota (minOccurs=0 en el XSD)
            if ($this->tipoImpositivo !== null) {
                $data['TipoImpositivo'] = $this->tipoImpositivo;
            }
            if ($this->cuotaRepercutida !== null) {
                $data['CuotaRepercutida'] = $this->cuotaRepercutida;
            }
            $data['CalificacionOperacion'] = $this->calificacionOperacion;
        }

        // Nombre EXACTO del XSD (SuministroInformacion.xsd): TipoRecargoEquivalencia.
        // Con el nombre erróneo anterior (TipoCargoEquivalencia) el SoapClient en
        // modo WSDL lo descartaba en silencio y la AEAT rechazaría el recargo.
        if ($this->tipoRecargoEquivalencia !== null) {
            $data['TipoRecargoEquivalencia'] = $this->tipoRecargoEquivalencia;
        }
        if ($this->cuotaRecargoEquivalencia !== null) {
            $data['CuotaRecargoEquivalencia'] = $this->cuotaRecargoEquivalencia;
        }

        return $data;
    }

    private function lineaExentaIva(): bool
    {
        return TipoOperacion::tryFrom($this->calificacionOperacion)?->isExenta() ?? false;
    }
}
