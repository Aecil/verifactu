<?php

namespace Aecil\Verifactu\Enums;

/**
 * Enumeración con los posibles estados de respuesta del sistema Verifactu
 */
enum VerifactuRespuestas
{
    const CORRECTO = 'CORRECTO';

    const ACEPTADO_CON_ERRORES = 'ACEPTADOCONERRORES';

    const PARCIALMENTE_CORRECTO = 'PARCIALMENTECORRECTO';

    const INCORRECTO = 'INCORRECTO';
}
