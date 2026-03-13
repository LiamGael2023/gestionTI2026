<?php
require_once __DIR__ . '/BaseModel.php';
/**
 * SedesRepository.php
 * Gestión de CRUD para la entidad Sedes (comun.Sedes).
 *
 * Principio SOLID aplicado: SRP — solo opera sobre Sedes.
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */

class SedesRepository extends BaseModel
{
    /**
     * Devuelve las sedes activas (para selectores de usuario).
     */
    public function getSedes(): array
    {
        return $this->fetchAll(
            "SELECT id, nombre, direccion
               FROM comun.Sedes
              WHERE activo = 1
              ORDER BY nombre ASC"
        );
    }

    /**
     * Devuelve todas las sedes incluyendo inactivas (panel admin).
     */
    public function getAllSedes(): array
    {
        return $this->fetchAll(
            "SELECT id, nombre, direccion, activo
               FROM comun.Sedes
              ORDER BY activo DESC, nombre ASC"
        );
    }

    public function getSedeById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT id, nombre, direccion, activo FROM comun.Sedes WHERE id = ?",
            [$id]
        );
    }

    /**
     * Crea o actualiza una sede. Devuelve el id resultante o false en error.
     */
    public function guardarSede(array $datos)
    {
        $nombre    = trim($datos['nombre']);
        $direccion = trim($datos['direccion'] ?? '');

        if (empty($datos['id'])) {
            return $this->insertAndGetId(
                "INSERT INTO comun.Sedes (nombre, direccion) VALUES (?, ?)",
                [$nombre, $direccion]
            );
        }

        $ok = $this->execute(
            "UPDATE comun.Sedes SET nombre = ?, direccion = ? WHERE id = ?",
            [$nombre, $direccion, (int) $datos['id']]
        );
        return $ok ? (int) $datos['id'] : false;
    }

    public function toggleSede(int $id, int $activo): bool
    {
        return $this->execute(
            "UPDATE comun.Sedes SET activo = ? WHERE id = ?",
            [$activo, $id]
        );
    }
}
