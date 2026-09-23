<?php

declare(strict_types=1);

namespace App\Aplicacion\Acceso;

use App\Dominio\Acceso\IdentidadDeGoogle;
use App\Dominio\Compartido\ReglaIncumplida;
use App\Modelos\Usuario;

/**
 * HU-37 · Entrar con la cuenta de Google. El sistema no registra a nadie: solo deja entrar a un correo que
 * ya está en una usuaria (RN-45). Quien instala el sistema decide quién entra, con `despliegue/crear-usuaria.sh`.
 */
class EntrarConGoogle
{
    public function __construct(private readonly IdentidadDeGoogle $identidad) {}

    public function estaDisponible(): bool
    {
        return $this->identidad->estaConfigurada();
    }

    public function direccionDeGoogle(string $estado): string
    {
        return $this->identidad->direccionDeAutorizacion($estado);
    }

    /**
     * La usuaria a la que pertenece el correo que Google confirmó.
     *
     * @throws ReglaIncumplida si Google no confirmó nada, o si ese correo no está registrado (RN-45)
     */
    public function usuariaDelCodigo(string $codigo): Usuario
    {
        $correo = $this->identidad->correoVerificado($codigo);

        if ($correo === null) {
            throw new ReglaIncumplida('RN-45', 'No se pudo confirmar tu cuenta de Google. Intenta otra vez o entra con tu usuario y contraseña.', 'usuario');
        }

        $usuaria = Usuario::where('correo', $correo)->first();

        if ($usuaria === null) {
            // No se dice qué correos sí existen, y no se crea nada: no hay registro abierto (RN-45, ADR-002)
            throw new ReglaIncumplida('RN-45', 'Ese correo no tiene acceso al sistema. Pídeselo a quien te lo instaló.', 'usuario');
        }

        return $usuaria;
    }
}
