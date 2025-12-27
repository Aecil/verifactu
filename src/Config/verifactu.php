<?php

return [
    'verifactu_cert_dir' => storage_path(env('VERIFACTU_CERT_DIR', 'app/certs')),
    'verifactu_cert_name' => env('VERIFACTU_CERT_NAME', 'cert.pfx'),
    'verifactu_cert_password' => env('VERIFACTU_CERT_PASSWORD'),
    'environment' => env('VERIFACTU_ENV', 'sandbox'),
    'main_endpoints' => [
        'sandbox' => 'https://prewww1.aeat.es/wlpl/TIKE-CONT/WS',
        'production' => 'https://www1.aeat.es/wlpl/TIKE-CONT/WS',
    ],
    'qr_endpoints' => [
        'sandbox' => 'https://prewww2.aeat.es',
        'production' => 'https://www2.agenciatributaria.gob.es',
    ],
];
