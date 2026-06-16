<?php

namespace Aecil\Verifactu\Enums;

/**
 * Posibles estados de respuesta del sistema Verifactu (AEAT).
 */
enum VerifactuRespuestas: string
{
    case CORRECTO = 'CORRECTO';
    case ACEPTADA_CON_ERRORES = 'ACEPTADACONERRORES';
    case PARCIALMENTE_CORRECTO = 'PARCIALMENTECORRECTO';
    case INCORRECTO = 'INCORRECTO';

    public function isAccepted(): bool
    {
        return in_array($this, [self::CORRECTO, self::ACEPTADA_CON_ERRORES, self::PARCIALMENTE_CORRECTO], true);
    }
}
