<?php
/**
 * BaseModel.php
 * Clase base con helpers de acceso a base de datos (SQL Server vía sqlsrv).
 * Define también las constantes de roles y estados compartidas por todo el módulo.
 *
 * Principio SOLID aplicado: SRP — responsabilidad única de acceso a datos.
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */
class BaseModel
{
    protected $db;

    // ── Constantes de roles ────────────────────────────────────────────────
    const ROL_SOLICITANTE   = 'Solicitante';
    const ROL_AUTORIZADOR   = 'Autorizador';
    const ROL_ADMINISTRADOR = 'Administrador';

    // ── Constantes de estado de reserva ───────────────────────────────────
    const ESTADO_PENDIENTE = 'PENDIENTE';
    const ESTADO_APROBADA  = 'APROBADA';
    const ESTADO_RECHAZADA = 'RECHAZADA';
    const ESTADO_CANCELADA = 'CANCELADA';

    public function __construct($db)
    {
        $this->db = $db;
    }

    // ── Helpers de BD ─────────────────────────────────────────────────────

    /**
     * Ejecuta una consulta y devuelve todos los resultados como array asociativo.
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = empty($params)
            ? sqlsrv_query($this->db, $sql)
            : sqlsrv_query($this->db, $sql, $params);

        if ($stmt === false) {
            $errs = sqlsrv_errors();
            error_log('[' . static::class . '] SQL error: ' . json_encode($errs) . ' | SQL: ' . $sql);
            return [];
        }

        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            foreach ($row as $k => $v) {
                if ($v instanceof DateTime) {
                    $row[$k] = $v->format('Y-m-d H:i:s');
                }
            }
            $rows[] = $row;
        }
        sqlsrv_free_stmt($stmt);
        return $rows;
    }

    /**
     * Ejecuta una consulta y devuelve únicamente la primera fila.
     */
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $rows = $this->fetchAll($sql, $params);
        return $rows[0] ?? null;
    }

    /**
     * Ejecuta una sentencia DML (INSERT/UPDATE/DELETE) sin retorno de filas.
     */
    protected function execute(string $sql, array $params = []): bool
    {
        $stmt = empty($params)
            ? sqlsrv_query($this->db, $sql)
            : sqlsrv_query($this->db, $sql, $params);

        if ($stmt === false) {
            $errs = sqlsrv_errors();
            error_log('[' . static::class . '] SQL error: ' . json_encode($errs) . ' | SQL: ' . $sql);
            return false;
        }
        sqlsrv_free_stmt($stmt);
        return true;
    }

    /**
     * Inserta un registro y retorna el IDENTITY generado (SCOPE_IDENTITY).
     */
    protected function insertAndGetId(string $sql, array $params = [])
    {
        $sql .= '; SELECT SCOPE_IDENTITY() AS id;';
        $stmt = empty($params)
            ? sqlsrv_query($this->db, $sql)
            : sqlsrv_query($this->db, $sql, $params);

        if ($stmt === false) {
            $errs = sqlsrv_errors();
            error_log('[' . static::class . '] SQL error: ' . json_encode($errs) . ' | SQL: ' . $sql);
            return false;
        }
        sqlsrv_next_result($stmt);
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        return (int) $row['id'];
    }

    // ── Helpers de roles (estáticos para el controlador/ajax) ─────────────

    /**
     * Normaliza el rol del usuario desde la sesión.
     * Fallback de $_SESSION['usuario_rol_nombre'] a $_SESSION['usuario_rol'] con validación.
     * 
     * @return string Rol normalizado o cadena vacía
     */
    public static function normalizarRolUsuario(): string
    {
        // Primero intenta el campo normalizado
        $rol = $_SESSION['usuario_rol_nombre'] ?? '';
        if (!empty($rol)) {
            return $rol;
        }

        // Fallback al campo legacy con normalización
        $rol_legacy = $_SESSION['usuario_rol'] ?? '';
        if (empty($rol_legacy)) {
            return '';
        }

        // Normaliza ADMIN → Administrador
        return ($rol_legacy === 'ADMIN') ? self::ROL_ADMINISTRADOR : $rol_legacy;
    }

    public static function esAutorizadorOAdmin(string $rol): bool
    {
        return in_array($rol, [self::ROL_AUTORIZADOR, self::ROL_ADMINISTRADOR, 'ADMIN']);
    }

    public static function esAdmin(string $rol): bool
    {
        return in_array($rol, [self::ROL_ADMINISTRADOR, 'ADMIN']);
    }
}
