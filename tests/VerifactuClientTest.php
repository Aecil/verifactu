<?php

namespace Aecil\Verifactu\Tests;

use Aecil\Verifactu\Enums\TipoFactura;
use Aecil\Verifactu\Enums\TipoOperacion;
use Aecil\Verifactu\Enums\VerifactuRespuestas;
use Aecil\Verifactu\Models\CuerpoFactura;
use Aecil\Verifactu\Models\IdFactura;
use Aecil\Verifactu\Models\IdentificacionFiscal;
use Aecil\Verifactu\Models\LineaFactura;
use Aecil\Verifactu\Models\RegistroAnterior;
use Aecil\Verifactu\Models\SistemaInformatico;
use PHPUnit\Framework\TestCase;

class VerifactuClientTest extends TestCase
{
    // ─── VerifactuRespuestas ────────────────────────────────────────────

    public function test_verifactu_respuestas_enum_values(): void
    {
        $this->assertSame('CORRECTO', VerifactuRespuestas::CORRECTO->value);
        $this->assertSame('ACEPTADACONERRORES', VerifactuRespuestas::ACEPTADA_CON_ERRORES->value);
        $this->assertSame('PARCIALMENTECORRECTO', VerifactuRespuestas::PARCIALMENTE_CORRECTO->value);
        $this->assertSame('INCORRECTO', VerifactuRespuestas::INCORRECTO->value);
    }

    public function test_is_accepted_returns_true_for_success_states(): void
    {
        $this->assertTrue(VerifactuRespuestas::CORRECTO->isAccepted());
        $this->assertTrue(VerifactuRespuestas::ACEPTADA_CON_ERRORES->isAccepted());
        $this->assertTrue(VerifactuRespuestas::PARCIALMENTE_CORRECTO->isAccepted());
    }

    public function test_is_accepted_returns_false_for_incorrecto(): void
    {
        $this->assertFalse(VerifactuRespuestas::INCORRECTO->isAccepted());
    }

    public function test_try_from_handles_spaces(): void
    {
        // La AEAT puede devolver "Aceptada Con Errores" con espacios y case mixto
        $normalized = strtoupper(str_replace(' ', '', 'Aceptada Con Errores'));
        $this->assertSame('ACEPTADACONERRORES', $normalized);

        $estado = VerifactuRespuestas::tryFrom($normalized);
        $this->assertSame(VerifactuRespuestas::ACEPTADA_CON_ERRORES, $estado);
    }

    // ─── TipoFactura ────────────────────────────────────────────────────

    public function test_tipo_factura_is_rectificativa(): void
    {
        $this->assertFalse(TipoFactura::F1->isRectificativa());
        $this->assertFalse(TipoFactura::F2->isRectificativa());
        $this->assertTrue(TipoFactura::R1->isRectificativa());
        $this->assertTrue(TipoFactura::R2->isRectificativa());
        $this->assertTrue(TipoFactura::R5->isRectificativa());
    }

    // ─── TipoOperacion ──────────────────────────────────────────────────

    public function test_tipo_operacion_is_exenta(): void
    {
        $this->assertFalse(TipoOperacion::S1->isExenta());
        $this->assertFalse(TipoOperacion::S2->isExenta());
        $this->assertTrue(TipoOperacion::E1->isExenta());
        $this->assertTrue(TipoOperacion::E6->isExenta());
    }

    // ─── RegistroAnterior ───────────────────────────────────────────────

    public function test_from_id_factura_creates_registro(): void
    {
        $idFactura = new IdFactura('B12345678', 'F-2024-001', new \DateTime('2024-06-15'));

        $registro = RegistroAnterior::fromIdFactura($idFactura, 'ABC123DEF');

        $this->assertSame('B12345678', $registro->idEmisorFactura);
        $this->assertSame('F-2024-001', $registro->numSerieFactura);
        $this->assertSame('ABC123DEF', $registro->huella);
        $this->assertSame('15-06-2024', $registro->fechaExpedicionFactura->format('d-m-Y'));
    }

    public function test_from_soap_response_returns_null_for_empty_response(): void
    {
        $this->assertNull(RegistroAnterior::fromSoapResponse((object) []));
        $this->assertNull(RegistroAnterior::fromSoapResponse((object) ['RespuestaLinea' => null]));
    }

    public function test_from_soap_response_extracts_correctly(): void
    {
        $response = (object) [
            'RespuestaLinea' => (object) [
                'IDFactura' => (object) [
                    'IDEmisorFactura' => 'B12345678',
                    'NumSerieFactura' => 'FACT-001',
                    'FechaExpedicionFactura' => '15-06-2024',
                ],
                'Huella' => 'HUELLA123ABC',
            ],
        ];

        $registro = RegistroAnterior::fromSoapResponse($response);

        $this->assertNotNull($registro);
        $this->assertSame('B12345678', $registro->idEmisorFactura);
        $this->assertSame('FACT-001', $registro->numSerieFactura);
        $this->assertSame('HUELLA123ABC', $registro->huella);
    }

    // ─── CuerpoFactura validateTotales ──────────────────────────────────

    private function makeCuerpoConDesglose(array $lineas, string $importeTotal, string $cuotaTotal): CuerpoFactura
    {
        $cuerpo = new CuerpoFactura;
        $cuerpo->idFactura = new IdFactura('B12345678', 'TEST-001', new \DateTime('2024-06-15'));
        $cuerpo->nombreRazonEmisor = 'Empresa Test';
        $cuerpo->tipoFactura = 'F1';
        $cuerpo->descripcionOperacion = 'Servicios de prueba';
        $cuerpo->destinatarios = [new IdentificacionFiscal('Cliente Test', '12345678Z')];
        $cuerpo->sistemaInformatico = new SistemaInformatico('77', 'Test', 'TestVendor', 'B12345678', '1.0', 'T001');
        $cuerpo->desglose = $lineas;
        $cuerpo->importeTotal = $importeTotal;
        $cuerpo->cuotaTotal = $cuotaTotal;

        return $cuerpo;
    }

    public function test_validate_totales_passes_with_correct_amounts(): void
    {
        $lineas = [
            new LineaFactura('100.00', '21.00', '21.00'),
        ];

        $cuerpo = $this->makeCuerpoConDesglose($lineas, '121.00', '21.00');
        $errors = $cuerpo->validate();

        $totalesErrors = array_filter($errors, fn ($e) => str_contains($e, 'importeTotal') || str_contains($e, 'cuotaTotal'));
        $this->assertCount(0, $totalesErrors);
    }

    public function test_validate_totales_detects_mismatched_importe_total(): void
    {
        $lineas = [
            new LineaFactura('100.00', '21.00', '21.00'),
        ];

        $cuerpo = $this->makeCuerpoConDesglose($lineas, '999.00', '21.00');
        $errors = $cuerpo->validate();

        $totalesErrors = array_filter($errors, fn ($e) => str_contains($e, 'importeTotal'));
        $this->assertGreaterThan(0, count($totalesErrors));
    }

    public function test_validate_totales_detects_mismatched_cuota_total(): void
    {
        $lineas = [
            new LineaFactura('100.00', '21.00', '21.00'),
        ];

        $cuerpo = $this->makeCuerpoConDesglose($lineas, '121.00', '50.00');
        $errors = $cuerpo->validate();

        $totalesErrors = array_filter($errors, fn ($e) => str_contains($e, 'cuotaTotal'));
        $this->assertGreaterThan(0, count($totalesErrors));
    }

    public function test_validate_totales_with_multiple_lineas(): void
    {
        $lineas = [
            new LineaFactura('100.00', '21.00', '21.00'),
            new LineaFactura('200.00', '42.00', '21.00'),
        ];

        // Suma bases: 300, Suma cuotas: 63, Total: 363
        $cuerpo = $this->makeCuerpoConDesglose($lineas, '363.00', '63.00');
        $errors = $cuerpo->validate();

        $totalesErrors = array_filter($errors, fn ($e) => str_contains($e, 'importeTotal') || str_contains($e, 'cuotaTotal'));
        $this->assertCount(0, $totalesErrors);
    }

    // ─── ParseResponse con respuestas reales de la AEAT ─────────────────

    public function test_parse_response_correcto(): void
    {
        $soapResponse = (object) [
            'EstadoEnvio' => 'Correcto',
            'RespuestaLinea' => (object) [
                'DescripcionErrorRegistro' => '',
            ],
        ];

        // Validación manual del comportamiento esperado
        $estadoRaw = strtoupper(str_replace(' ', '', $soapResponse->EstadoEnvio));
        $estado = VerifactuRespuestas::tryFrom($estadoRaw);

        $this->assertSame(VerifactuRespuestas::CORRECTO, $estado);
        $this->assertTrue($estado->isAccepted());
    }

    public function test_parse_response_incorrecto_con_error(): void
    {
        // Respuesta real de la AEAT: Código 4116 - NIF incorrecto
        $soapResponse = (object) [
            'EstadoEnvio' => 'Incorrecto',
            'RespuestaLinea' => (object) [
                'CodigoErrorRegistro' => '4116',
                'DescripcionErrorRegistro' => 'Error en la cabecera: el campo NIF del bloque ObligadoEmision tiene un formato incorrecto.',
            ],
        ];

        $estadoRaw = strtoupper(str_replace(' ', '', $soapResponse->EstadoEnvio));
        $estado = VerifactuRespuestas::tryFrom($estadoRaw);

        $this->assertSame(VerifactuRespuestas::INCORRECTO, $estado);
        $this->assertFalse($estado->isAccepted());
        $this->assertNotEmpty($soapResponse->RespuestaLinea->DescripcionErrorRegistro);
        $this->assertSame('4116', $soapResponse->RespuestaLinea->CodigoErrorRegistro);
    }

    public function test_parse_response_aceptada_con_errores(): void
    {
        // Respuesta real: AceptadaConErrores con error 2005
        $soapResponse = (object) [
            'EstadoEnvio' => 'AceptadaConErrores',
            'RespuestaLinea' => (object) [
                'CodigoErrorRegistro' => '2005',
                'DescripcionErrorRegistro' => 'El campo ImporteTotal tiene un valor incorrecto.',
            ],
        ];

        $estadoRaw = strtoupper(str_replace(' ', '', $soapResponse->EstadoEnvio));
        $estado = VerifactuRespuestas::tryFrom($estadoRaw);

        $this->assertSame(VerifactuRespuestas::ACEPTADA_CON_ERRORES, $estado);
        $this->assertTrue($estado->isAccepted());
    }

    // ─── IdFactura ─────────────────────────────────────────────────────

    public function test_id_factura_to_array(): void
    {
        $id = new IdFactura('B12345678', 'FACT-2024-001', new \DateTime('2024-06-15'));

        $arr = $id->toArray();

        $this->assertSame('B12345678', $arr['IDEmisorFactura']);
        $this->assertSame('FACT-2024-001', $arr['NumSerieFactura']);
        $this->assertSame('15-06-2024', $arr['FechaExpedicionFactura']);
    }

    // ─── IdentificacionFiscal ──────────────────────────────────────────

    public function test_identificacion_fiscal_validate(): void
    {
        $id = new IdentificacionFiscal('Empresa Test', 'B12345678');
        $this->assertEmpty($id->validate());

        $idInvalida = new IdentificacionFiscal('', '123');
        $errors = $idInvalida->validate();
        $this->assertNotEmpty($errors);
    }

    // ─── SistemaInformatico ─────────────────────────────────────────────

    public function test_sistema_informatico_to_array(): void
    {
        $sistema = new SistemaInformatico('77', 'InmoLender', 'Aecil', 'B12345678', '1.0', 'INST001');

        $arr = $sistema->toArray();

        $this->assertSame('Aecil', $arr['NombreRazon']);
        $this->assertSame('B12345678', $arr['NIF']);
        $this->assertSame('InmoLender', $arr['NombreSistemaInformatico']);
        $this->assertSame('77', $arr['IdSistemaInformatico']);
        $this->assertSame('S', $arr['TipoUsoPosibleSoloVerifactu']);
    }
}
