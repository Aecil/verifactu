<?php

namespace Aecil\Verifactu\Models;

/**
 * Filtro de consulta de facturas ya registradas en Verifactu (AEAT).
 */
class ConsultaFactura
{
    public CabeceraFactura $cabecera;

    /** Ejercicio (YYYY) */
    public string $ejercicio;

    /** Período: 1S (primer semestre) o 2S (segundo semestre) */
    public string $periodo;

    /** Opcional: filtrar por número de serie de factura */
    public ?string $numSerieFactura = null;

    /** Opcional: filtrar por fecha de expedición (d-m-Y) */
    public ?string $fechaExpedicionFactura = null;

    /** Opcional: filtrar por sistema informático */
    public ?SistemaInformatico $sistemaInformatico = null;

    /** Opcional: clave de paginación para continuar una consulta previa */
    public ?string $clavePaginacion = null;

    public function __construct(
        CabeceraFactura $cabecera,
        string $ejercicio,
        string $periodo,
    ) {
        $this->cabecera = $cabecera;
        $this->ejercicio = $ejercicio;
        $this->periodo = $periodo;
    }

    public function toArray(): array
    {
        $filtro = [
            'PeriodoImputacion' => [
                'Ejercicio' => $this->ejercicio,
                'Periodo' => $this->periodo,
            ],
        ];

        if ($this->numSerieFactura !== null) {
            $filtro['NumSerieFactura'] = $this->numSerieFactura;
        }
        if ($this->fechaExpedicionFactura !== null) {
            $filtro['FechaExpedicionFactura'] = $this->fechaExpedicionFactura;
        }
        if ($this->sistemaInformatico !== null) {
            $filtro['SistemaInformatico'] = $this->sistemaInformatico->toArray();
        }
        if ($this->clavePaginacion !== null) {
            $filtro['ClavePaginacion'] = $this->clavePaginacion;
        }

        return [
            'Cabecera' => $this->cabecera->toArray(),
            'FiltroConsulta' => $filtro,
        ];
    }
}
