<?php

namespace Aecil\Verifactu\Tests;

use Aecil\Verifactu\Certificate;
use PHPUnit\Framework\TestCase;

class CertificateTest extends TestCase
{
    private string $certDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Directorio temporal para los tests
        $this->certDir = sys_get_temp_dir().'/verifactu_cert_test';

        if (! is_dir($this->certDir)) {
            mkdir($this->certDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        // Limpieza de archivos temporales
        foreach (glob($this->certDir.'/*') as $file) {
            @unlink($file);
        }

        @rmdir($this->certDir);

        parent::tearDown();
    }

    public function test_constructor_throws_exception_when_cert_dir_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Certificate('', 'cert.pfx', 'secret');
    }

    public function test_constructor_throws_exception_when_cert_name_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Certificate($this->certDir, '', 'secret');
    }

    public function test_constructor_throws_exception_when_password_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Certificate($this->certDir, 'cert.pfx', '');
    }

    public function test_get_original_path_returns_real_path_when_certificate_exists(): void
    {
        $certPath = $this->certDir.'/cert.pfx';
        file_put_contents($certPath, 'fake-cert-content');

        $certificate = new Certificate(
            $this->certDir,
            'cert.pfx',
            'secret'
        );

        $result = $certificate->getOriginalPath();

        $this->assertSame(realpath($certPath), $result);
    }

    public function test_get_original_path_throws_exception_when_certificate_does_not_exist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $certificate = new Certificate(
            $this->certDir,
            'cert.pfx',
            'secret'
        );

        $certificate->getOriginalPath();
    }

    public function test_get_pem_path_returns_existing_pem_if_already_present(): void
    {
        $pfxPath = $this->certDir.'/cert.pfx';
        $pemPath = $this->certDir.'/cert.pem';

        file_put_contents($pfxPath, 'fake-cert-content');
        file_put_contents($pemPath, 'fake-pem-content');

        $certificate = new Certificate(
            $this->certDir,
            'cert.pfx',
            'secret'
        );

        $result = $certificate->getPemPath();

        $this->assertSame(realpath($pemPath), $result);
    }
}
