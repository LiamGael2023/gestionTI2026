<?php
/**
 * AutorizadorPermissions.php — Validaciones y permisos para rol Autorizador
 * Helper estático: sin interfaz, solo validaciones
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */

class AutorizadorPermissions
{
    /**
     * Verifica si el rol es autorizador o admin
     */
    public static function esAutorizadorOAdmin($rol_normalizado)
    {
        return in_array($rol_normalizado, [
            'Autorizador',
            'Administrador',
            'AUTH',
            'ADMIN',
        ]);
    }

    /**
     * Verifica si puede ver solicitudes pendientes
     */
    public static function puedeVerPendientes($rol)
    {
        return self::esAutorizadorOAdmin($rol);
    }

    /**
     * Verifica si puede autorizar reservas
     */
    public static function puedeAutorizar($rol)
    {
        return self::esAutorizadorOAdmin($rol);
    }

    /**
     * Verifica si puede rechazar reservas
     */
    public static function puedeRechazar($rol)
    {
        return self::esAutorizadorOAdmin($rol);
    }

    /**
     * Verifica si puede ver historial
     */
    public static function puedeVerHistorial($rol)
    {
        return self::esAutorizadorOAdmin($rol);
    }
}
