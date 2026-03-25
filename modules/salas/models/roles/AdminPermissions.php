<?php
/**
 * AdminPermissions.php — Validaciones y permisos para rol Administrador
 * Helper estático: sin interfaz, solo validaciones
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */

class AdminPermissions
{
    /**
     * Verifica si el rol es administrador
     */
    public static function esAdmin($rol_normalizado)
    {
        return in_array($rol_normalizado, [
            'Administrador',
            'ADMIN',
        ]);
    }

    /**
     * Verifica si puede gestionar sedes
     */
    public static function puedeGestionarSedes($rol)
    {
        return self::esAdmin($rol);
    }

    /**
     * Verifica si puede gestionar salas
     */
    public static function puedeGestionarSalas($rol)
    {
        return self::esAdmin($rol);
    }

    /**
     * Verifica si puede gestionar equipos
     */
    public static function puedeGestionarEquipos($rol)
    {
        return self::esAdmin($rol);
    }

    /**
     * Verifica si puede ver historial completo
     */
    public static function puedeVerHistorial($rol)
    {
        return self::esAdmin($rol);
    }
}
