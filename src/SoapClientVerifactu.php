<?php

namespace Aecil\Verifactu;

use const WSDL_CACHE_NONE;

use Aecil\Verifactu\Enums\VerifactuRespuestas;
use Aecil\Verifactu\Exceptions\ApiErrorException;
use Aecil\Verifactu\Exceptions\SoapException;
use Aecil\Verifactu\Exceptions\ValidationException;
use Aecil\Verifactu\Exceptions\VerifactuException;
use Aecil\Verifactu\Models\CancelarFactura;
use Aecil\Verifactu\Models\ConsultaFactura;
use Aecil\Verifactu\Models\IdFactura;
use Aecil\Verifactu\Models\Invoice;
use Aecil\Verifactu\Models\RegistroAnterior;
use Illuminate\Support\HtmlString;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use SoapClient;

class SoapClientVerifactu
{
    private Certificate $certificate;

    private SoapClient $client;

    private string $wsdlPath;

    private string $endpoint;

    private string $qrEndpoint;

    public function __construct(array $config)
    {
        // Instancia Certificate usando la nueva config
        $this->certificate = new Certificate(
            $config['verifactu_cert_dir'],
            $config['verifactu_cert_name'],
            $config['verifactu_cert_password']
        );

        // Endpoint según entorno
        $env = $config['environment'] ?? 'sandbox';
        $this->endpoint = $config['main_endpoints'][$env]
            ?? throw new \InvalidArgumentException("Main Endpoint for environment [$env] not defined");

        $this->qrEndpoint = $config['qr_endpoints'][$env]
            ?? throw new \InvalidArgumentException("QR Endpoint for environment [$env] not defined");

        // WSDL path opcional
        $this->wsdlPath = $config['wsdl_path'] ?? __DIR__.'/xsd/SistemaFacturacion.wsdl';

        $this->bootSoapClient();
    }

    private function bootSoapClient(): void
    {
        $options = [
            'local_cert' => $this->certificate->getPemPath(),
            'passphrase' => $this->certificate->getPassword(),
            'trace' => 1,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
            'soap_version' => SOAP_1_1,
            'connection_timeout' => 30,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
                ],
            ]),
        ];

        $this->client = new SoapClient($this->wsdlPath, $options);
    }

    /**
     * @throws ValidationException Si los datos de la factura no superan la validación local.
     * @throws SoapException Si ocurre un error de transporte SOAP.
     * @throws ApiErrorException Si la AEAT rechaza la factura.
     */
    public function send(Invoice $invoice): array
    {
        $this->client->__setLocation($this->endpoint);

        try {
            $errors = $invoice->validate();
            if (\count($errors) > 0) {
                throw new ValidationException($errors, 'Error de validación en la factura');
            }

            $result = $this->client->__soapCall(
                'RegFactuSistemaFacturacion',
                [$invoice->toArray()]
            );

            return $this->parseResponse($result);
        } catch (VerifactuException $e) {
            throw $e;
        } catch (\SoapFault $fault) {
            throw SoapException::fromFault($fault);
        } catch (\Throwable $error) {
            throw new VerifactuException(
                message: $error->getMessage() ?: 'Error desconocido',
                previous: $error,
            );
        }
    }

    /**
     * @throws ValidationException Si los datos de cancelación no superan la validación local.
     * @throws SoapException Si ocurre un error de transporte SOAP.
     * @throws ApiErrorException Si la AEAT rechaza la cancelación.
     */
    public function cancelInvoice(CancelarFactura $cancelacion): array
    {
        $this->client->__setLocation($this->endpoint);

        try {
            $errors = $cancelacion->validate();
            if (\count($errors) > 0) {
                throw new ValidationException($errors, 'Error de validación en la cancelación');
            }

            $result = $this->client->__soapCall(
                'RegFactuSistemaFacturacion',
                [$cancelacion->toArray()]
            );

            return $this->parseResponse($result);
        } catch (VerifactuException $e) {
            throw $e;
        } catch (\SoapFault $fault) {
            throw SoapException::fromFault($fault);
        } catch (\Throwable $error) {
            throw new VerifactuException(
                message: $error->getMessage() ?: 'Error desconocido',
                previous: $error,
            );
        }
    }

    /**
     * Extrae el mensaje de error desde la RespuestaLinea, recorriendo
     * posibles ubicaciones del nodo en la respuesta SOAP.
     */
    private function extractErrorDescription(object $result): string
    {
        $linea = $result->RespuestaLinea ?? null;

        // SOAP_SINGLE_ELEMENT_ARRAYS puede envolver un solo elemento en un array
        if (is_array($linea)) {
            $linea = $linea[0] ?? null;
        }

        if ($linea && ! empty($linea->DescripcionErrorRegistro)) {
            $codigo = ! empty($linea->CodigoErrorRegistro) ? ' ['.$linea->CodigoErrorRegistro.']' : '';

            return $linea->DescripcionErrorRegistro.$codigo;
        }

        return '';
    }

    /**
     * @throws ApiErrorException Si la AEAT devuelve un estado de error.
     */
    private function parseResponse(object $result): array
    {
        $estadoRaw = strtoupper(str_replace(' ', '', $result->EstadoEnvio ?? ''));

        $estado = VerifactuRespuestas::tryFrom($estadoRaw) ?? VerifactuRespuestas::INCORRECTO;

        if (! $estado->isAccepted()) {
            throw ApiErrorException::fromResponse($result);
        }

        $message = match ($estado) {
            VerifactuRespuestas::CORRECTO => 'Factura enviada correctamente',
            VerifactuRespuestas::ACEPTADA_CON_ERRORES,
            VerifactuRespuestas::PARCIALMENTE_CORRECTO => 'Factura enviada con errores',
            VerifactuRespuestas::INCORRECTO => 'Error al enviar la factura',
        };

        $errorDesc = $this->extractErrorDescription($result);
        if ($errorDesc !== '') {
            $message .= ': '.$errorDesc;
        }

        return [
            'success' => true,
            'message' => $message,
            'estado' => $estado,
            'data' => $result,
        ];
    }

    /**
     * @throws SoapException Si ocurre un error de transporte SOAP.
     * @throws VerifactuException Si ocurre un error inesperado.
     */
    public function consultInvoice(ConsultaFactura $consulta): array
    {
        $this->client->__setLocation($this->endpoint);

        try {
            $result = $this->client->__soapCall(
                'ConsultaFactuSistemaFacturacion',
                [$consulta->toArray()]
            );

            $registros = $result->RegistroRespuestaConsultaFactuSistemaFacturacion ?? [];

            if (! is_array($registros)) {
                $registros = $registros ? [$registros] : [];
            }

            return [
                'success' => true,
                'message' => \count($registros).' registros encontrados',
                'registros' => $registros,
                'clavePaginacion' => $result->ClavePaginacion ?? null,
                'data' => $result,
            ];
        } catch (VerifactuException $e) {
            throw $e;
        } catch (\SoapFault $fault) {
            throw SoapException::fromFault($fault);
        } catch (\Throwable $error) {
            throw new VerifactuException(
                message: $error->getMessage() ?: 'Error desconocido',
                previous: $error,
            );
        }
    }

    /**
     * Consulta la AEAT y extrae los datos de la serie para calcular el siguiente
     * código incremental y el registro anterior para encadenamiento.
     *
     * Filtra los registros por el prefijo de serie (ej. "F261" para facturas,
     * "R261" para rectificativas) para que cada serie tenga su propio correlativo
     * y su propia cadena de huellas, sin contaminación cruzada.
     *
     * @param  ConsultaFactura  $consulta  Consulta ya configurada con emisor y período
     * @param  string  $seriesPrefix  Prefijo de la serie (ej. "F261", "R261")
     * @return array{max_incremental: int, registro_anterior: ?RegistroAnterior}
     */
    public function getNextInvoiceData(ConsultaFactura $consulta, string $seriesPrefix): array
    {
        $result = $this->consultInvoice($consulta);

        $filtered = collect($result['registros'])
            ->filter(fn ($r) => str_starts_with(
                (string) ($r->IDFactura->NumSerieFactura ?? ''),
                $seriesPrefix
            ));

        // Extrae el máximo número incremental real del NumSerieFactura,
        // no un conteo de registros (que asumiría numeración secuencial sin huecos).
        $maxIncremental = $filtered
            ->map(fn ($r) => (int) substr(
                (string) $r->IDFactura->NumSerieFactura,
                strlen($seriesPrefix)
            ))
            ->max() ?: 0;

        // El encadenamiento debe hacerse contra el último registro de la MISMA serie.
        $ultimo = $filtered->last();
        $registroAnterior = $ultimo
            ? RegistroAnterior::fromConsultaRegistro($ultimo)
            : null;

        return [
            'max_incremental' => $maxIncremental,
            'registro_anterior' => $registroAnterior,
        ];
    }

    public function getLastRequest(): ?string
    {
        return $this->client->__getLastRequest();
    }

    public function getLastResponse(): ?string
    {
        return $this->client->__getLastResponse();
    }

    public function getLastRequestHeaders(): ?string
    {
        return $this->client->__getLastRequestHeaders();
    }

    private function generateQrUrl(IdFactura $idFactura, string $totalAmount): string
    {
        $params = [
            'nif' => $idFactura->idEmisorFactura,
            'numserie' => $idFactura->numSerieFactura,
            'fecha' => $idFactura->fechaExpedicionFactura->format('d-m-Y'),
            'importe' => (string) $totalAmount,
        ];

        return $this->qrEndpoint.'/wlpl/TIKE-CONT/ValidarQR?'.http_build_query($params);
    }

    public function generateQr(IdFactura $idFactura, string $totalAmount, int $size = 300, bool $asBase64 = false): HtmlString|string
    {
        $qrUrl = $this->generateQrUrl($idFactura, $totalAmount);

        $qrCode = QrCode::format('png')
            ->size($size)
            ->generate($qrUrl);

        if ($asBase64) {
            // Convierte a base64 para incrustar en Dompdf u otros usos
            return 'data:image/png;base64,'.base64_encode($qrCode);
        }

        // Devuelve el binario normal de la imagen
        return $qrCode;
    }
}
