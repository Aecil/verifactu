<?php

namespace Aecil\Verifactu;

class Certificate
{
    private string $certDir;

    private string $certName;

    private string $password;

    private ?string $pemPath = null;

    public function __construct(
        string $certDir,
        string $certName,
        string $password
    ) {
        if ($certDir === '') {
            throw new \InvalidArgumentException('Certificate directory is required');
        }

        if ($certName === '') {
            throw new \InvalidArgumentException('Certificate name is required');
        }

        if ($password === '') {
            throw new \InvalidArgumentException('Certificate password is required');
        }

        $this->certDir = rtrim($certDir, DIRECTORY_SEPARATOR);
        $this->certName = $certName;
        $this->password = $password;
    }

    /**
     * Devuelve la ruta absoluta del certificado original (PFX)
     */
    public function getOriginalPath(): string
    {
        $path = $this->certDir.DIRECTORY_SEPARATOR.$this->certName;

        if (! file_exists($path)) {
            throw new \InvalidArgumentException("Certificate not found: {$path}");
        }

        return realpath($path);
    }

    /**
     * Devuelve la ruta absoluta del PEM, generándolo si no existe
     */
    public function getPemPath(): string
    {
        if ($this->pemPath !== null) {
            return $this->pemPath;
        }

        $original = $this->getOriginalPath();
        $pemPath = $this->certDir.DIRECTORY_SEPARATOR.pathinfo(
            $this->certName,
            PATHINFO_FILENAME
        ).'.pem';

        if (! file_exists($pemPath)) {
            $this->pemPath = $this->convertPfxToPem($original, $pemPath);
        } else {
            $this->pemPath = realpath($pemPath);
        }

        return $this->pemPath;
    }

    /**
     * Devuelve la contraseña del certificado
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    private function convertPfxToPem(string $inputPath, string $outputPath): string
    {
        $opensslVersion = shell_exec('openssl version');
        $useLegacy = preg_match('/OpenSSL\s+3\./', $opensslVersion) === 1;
        $legacyFlag = $useLegacy ? '-legacy' : '';

        $command = sprintf(
            'openssl pkcs12 -in %s -out %s -nodes -clcerts %s -passin pass:%s',
            escapeshellarg($inputPath),
            escapeshellarg($outputPath),
            $legacyFlag,
            escapeshellarg($this->password)
        );

        exec($command.' 2>&1', $output, $status);

        if ($status !== 0 || ! file_exists($outputPath)) {
            @unlink($outputPath);

            throw new \RuntimeException(
                'Error converting PFX to PEM: '.implode("\n", (array) $output)
            );
        }

        return realpath($outputPath);
    }
}
