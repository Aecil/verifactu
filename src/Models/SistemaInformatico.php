<?php

namespace Aecil\Verifactu\Models;

/**
 * Información sobre el sistema informático utilizado para generar la factura
 *
 * @field SistemaInformatico
 */
class SistemaInformatico
{
    public string $vendorName;

    public string $vendorNif;

    public string $name;

    public string $id; // ID del sistema informático (2 dígitos). Si eres colaborador te asignan un número propio; en caso contrario, usa 77 para software propio o no registrado.

    public string $version;

    public string $installationNumber;

    public bool $onlySupportsVerifactu = true;

    public bool $supportsMultipleTaxpayers = false;

    public bool $hasMultipleTaxpayers = false;

    /**
     * Crea una nueva instancia del sistema informático
     *
     * @param  string  $id  // ID del sistema informático (2 dígitos). Si eres colaborador te asignan un número propio; en caso contrario, usa 77 para software propio o no registrado.
     * @param  string  $name  Nombre del sistema
     * @param  string  $vendorName  Nombre del fabricante
     * @param  string  $vendorNif  NIF del fabricante
     * @param  string  $version  Versión del sistema
     * @param  string  $installationNumber  Número de instalación
     * @param  bool  $onlySupportsVerifactu  Indica si solo soporta Verifactu
     * @param  bool  $supportsMultipleTaxpayers  Indica si soporta múltiples contribuyentes
     * @param  bool  $hasMultipleTaxpayers  Indica si tiene múltiples contribuyentes configurados
     */
    public function __construct($id, $name, $vendorName, $vendorNif, $version, $installationNumber, $onlySupportsVerifactu = true, $supportsMultipleTaxpayers = false, $hasMultipleTaxpayers = false)
    {
        $this->id = $id;
        $this->vendorName = $vendorName;
        $this->vendorNif = $vendorNif;
        $this->name = $name;
        $this->version = $version;
        $this->installationNumber = $installationNumber;
        $this->onlySupportsVerifactu = $onlySupportsVerifactu;
        $this->supportsMultipleTaxpayers = $supportsMultipleTaxpayers;
        $this->hasMultipleTaxpayers = $hasMultipleTaxpayers;
    }

    /**
     * Convierte el sistema informático a formato array
     */
    public function toArray(): array
    {
        return [
            'NombreRazon' => $this->vendorName,
            'NIF' => $this->vendorNif,
            'NombreSistemaInformatico' => $this->name,
            'IdSistemaInformatico' => ! empty($this->id) ? $this->id : null,
            'Version' => ! empty($this->version) ? $this->version : null,
            'NumeroInstalacion' => ! empty($this->installationNumber) ? $this->installationNumber : null,
            'TipoUsoPosibleSoloVerifactu' => $this->onlySupportsVerifactu ? 'S' : 'N',
            'TipoUsoPosibleMultiOT' => $this->supportsMultipleTaxpayers ? 'S' : 'N',
            'IndicadorMultiplesOT' => $this->hasMultipleTaxpayers ? 'S' : 'N',
        ];
    }
}
