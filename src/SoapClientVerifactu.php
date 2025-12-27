<?php

namespace Aecil\Verifactu;

use const WSDL_CACHE_NONE;

use Aecil\Verifactu\Enums\VerifactuRespuestas;
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
            if (count($errors) > 0) {
                throw new \InvalidArgumentException(implode("\n", $errors));
            }

            $result = $this->client->__soapCall(
                'RegFactuSistemaFacturacion',
                [$invoice->toArray()]
            );

            $isSuccess = false;
            $message = 'Error al enviar la factura';

            $estado = strtoupper($result->EstadoEnvio ?? '');

            if ($estado === VerifactuRespuestas::CORRECTO) {
                $isSuccess = true;
                $message = 'Factura enviada correctamente';
            } elseif (
                $estado === VerifactuRespuestas::ACEPTADO_CON_ERRORES ||
                $estado === VerifactuRespuestas::PARCIALMENTE_CORRECTO
            ) {
                $isSuccess = true;
                $message = 'Factura enviada con errores';
            } elseif (! empty($result->RespuestaLinea->DescripcionErrorRegistro)) {
                $message .= ': '.$result->RespuestaLinea->DescripcionErrorRegistro;
            }

            return [
                'success' => $isSuccess,
                'message' => $message,
                'data' => $result,
            ];
        } catch (\Throwable $error) {
            return [
                'success' => false,
                'message' => $error->getMessage() ?? 'Error desconocido',
                'data' => $error,
            ];
        }
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
