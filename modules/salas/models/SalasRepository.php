<?php
require_once __DIR__ . '/BaseModel.php';
/**
 * SalasRepository.php
 * Gestión de CRUD para la entidad Sala (salas.Sala).
 *
 * Principio SOLID aplicado: SRP — solo opera sobre Salas.
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */

class SalasRepository extends BaseModel
{
    /**
     * Devuelve las salas activas de una sede (para selectores de solicitud).
     */
    public function getSalasBySede(int $id_sede): array
    {
        return $this->fetchAll(
            "SELECT id_sala, nombre, capacidad, descripcion, foto_ruta
               FROM salas.Sala
              WHERE id_sede = ? AND activo = 1
              ORDER BY nombre ASC",
            [$id_sede]
        );
    }

    /**
     * Devuelve todas las salas con datos de sede (panel admin).
     */
    public function getAllSalas(): array
    {
        return $this->fetchAll(
            "SELECT s.id_sala, s.nombre, s.capacidad, s.descripcion, s.activo,
                    se.nombre AS sede_nombre, s.id_sede, s.foto_ruta
               FROM salas.Sala s
               INNER JOIN comun.Sedes se ON s.id_sede = se.id
              ORDER BY s.activo DESC, se.nombre ASC, s.nombre ASC"
        );
    }

    public function getSalaById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT s.id_sala, s.nombre, s.capacidad, s.descripcion, s.activo, s.id_sede,
                    se.nombre AS sede_nombre, s.foto_ruta
               FROM salas.Sala s
               INNER JOIN comun.Sedes se ON s.id_sede = se.id
              WHERE s.id_sala = ?",
            [$id]
        );
    }

    /**
     * Crea o actualiza una sala. Devuelve el id resultante o false en error.
     */
    public function guardarSala(array $datos)
    {
        $nombre      = trim($datos['nombre']);
        $capacidad   = (int) $datos['capacidad'];
        $descripcion = trim($datos['descripcion'] ?? '');
        $id_sede     = (int) $datos['id_sede'];

        if ($capacidad <= 0) return false;

        if (empty($datos['id_sala'])) {
            return $this->insertAndGetId(
                "INSERT INTO salas.Sala (id_sede, nombre, capacidad, descripcion) VALUES (?, ?, ?, ?)",
                [$id_sede, $nombre, $capacidad, $descripcion]
            );
        }

        $ok = $this->execute(
            "UPDATE salas.Sala SET id_sede = ?, nombre = ?, capacidad = ?, descripcion = ? WHERE id_sala = ?",
            [$id_sede, $nombre, $capacidad, $descripcion, (int) $datos['id_sala']]
        );
        return $ok ? (int) $datos['id_sala'] : false;
    }

    public function toggleSala(int $id, int $activo): bool
    {
        return $this->execute(
            "UPDATE salas.Sala SET activo = ? WHERE id_sala = ?",
            [$activo, $id]
        );
    }

    public function guardarFotoSala(int $id_sala, string $foto_ruta): bool
    {
        return $this->execute(
            "UPDATE salas.Sala SET foto_ruta = ? WHERE id_sala = ?",
            [$foto_ruta, $id_sala]
        );
    }
}
