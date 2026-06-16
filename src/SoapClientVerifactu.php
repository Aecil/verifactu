<?php

namespace Aecil\Verifactu;

use const WSDL_CACHE_NONE;

use Aecil\Verifactu\Enums\VerifactuRespuestas;
use Aecil\Verifactu\Models\CancelarFactura;
use Aecil\Verifactu\Models\IdFactura;
use Aecil\Verifactu\Models\Invoice;
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

    public function send(Invoice $invoice): array
    {
        $this->client->__setLocation($this->endpoint);

        try {
            $errors = $invoice->validate();
            if (\count($errors) > 0) {
                throw new \InvalidArgumentException(implode("\n", $errors));
            }

            $result = $this->client->__soapCall(
                'RegFactuSistemaFacturacion',
                [$invoice->toArray()]
            );

            return $this->parseResponse($result);
        } catch (\SoapFault $fault) {
            return [
                'success' => false,
                'message' => $fault->getMessage(),
                'data' => $fault,
            ];
        } catch (\Throwable $error) {
            return [
                'success' => false,
                'message' => $error->getMessage() ?? 'Error desconocido',
                'data' => $error,
            ];
        }
    }

    /**
     * Cancela (anula) una factura previamente enviada a Verifactu.
     */
    public function cancelInvoice(CancelarFactura $cancelacion): array
    {
        $this->client->__setLocation($this->endpoint);

        try {
            $errors = $cancelacion->validate();
            if (\count($errors) > 0) {
                throw new \InvalidArgumentException(implode("\n", $errors));
            }

            $result = $this->client->__soapCall(
                'RegFactuSistemaFacturacion',
                [$cancelacion->toArray()]
            );

            return $this->parseResponse($result);
        } catch (\SoapFault $fault) {
            return [
                'success' => false,
                'message' => $fault->getMessage(),
                'data' => $fault,
            ];
        } catch (\Throwable $error) {
            return [
                'success' => false,
                'message' => $error->getMessage() ?? 'Error desconocido',
                'data' => $error,
            ];
        }
    }

    /**
     * Extrae el mensaje de error desde la RespuestaLinea, recorriendo
     * posibles ubicaciones del nodo en la respuesta SOAP.
     */
    private function extractErrorDescription(object $result): string
    {
        $linea = $result->RespuestaLinea ?? null;

        if ($linea && ! empty($linea->DescripcionErrorRegistro)) {
            $codigo = ! empty($linea->CodigoErrorRegistro) ? ' ['.$linea->CodigoErrorRegistro.']' : '';

            return $linea->DescripcionErrorRegistro.$codigo;
        }

        return '';
    }

    /**
     * Parsea la respuesta SOAP de Verifactu y devuelve un array normalizado.
     */
    private function parseResponse(object $result): array
    {
        $estadoRaw = strtoupper(str_replace(' ', '', $result->EstadoEnvio ?? ''));

        $estado = VerifactuRespuestas::tryFrom($estadoRaw) ?? VerifactuRespuestas::INCORRECTO;

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
            'success' => $estado->isAccepted(),
            'message' => $message,
            'estado' => $estado,
            'data' => $result,
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
