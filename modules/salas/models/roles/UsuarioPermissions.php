<?php
/**
 * UsuarioPermissions.php — Validaciones y permisos para rol Usuario
 * Helper estático: sin interfaz, solo validaciones
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */

class UsuarioPermissions
{
    /**
     * Verifica si el rol es usuario regular
     */
    public static function esUsuario($rol_normalizado)
    {
        return in_array($rol_normalizado, [
            'Usuario',
            'USER',
            'USUARIO',
        ]);
    }

    /**
     * Verifica si puede crear reservas
     */
    public static function puedeCrearReserva($rol)
    {
        // Cualquier usuario autenticado puede crear reservas
        return !empty($rol);
    }

    /**
     * Verifica si puede editar sus propias reservas
     */
    public static function puedeEditarPropia($rol, $id_usuario, $id_creador)
    {
        return $id_usuario === $id_creador;
    }

    /**
     * Verifica si puede cancelar sus propias reservas
     */
    public static function puedeCancelarPropia($rol, $id_usuario, $id_creador)
    {
        return $id_usuario === $id_creador;
    }

    /**
     * Verifica si puede ver sus propias reservas
     */
    public static function puedeVerPropias($rol, $id_usuario, $id_creador)
    {
        return $id_usuario === $id_creador;
    }
}
