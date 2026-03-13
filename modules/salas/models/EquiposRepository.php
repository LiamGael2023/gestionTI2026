<?php
require_once __DIR__ . '/BaseModel.php';
/**
 * EquiposRepository.php
 * Gestión de CRUD para la entidad Equipo (salas.Equipo).
 *
 * Principio SOLID aplicado: SRP — solo opera sobre Equipos.
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */

class EquiposRepository extends BaseModel
{
    /**
     * Devuelve los equipos activos de una sala (para checkboxes de solicitud).
     */
    public function getEquiposBySala(int $id_sala): array
    {
        return $this->fetchAll(
            "SELECT id_equipo, nombre, tipo, descripcion
               FROM salas.Equipo
              WHERE id_sala = ? AND activo = 1
              ORDER BY tipo ASC, nombre ASC",
            [$id_sala]
        );
    }

    /**
     * Devuelve todos los equipos con datos de sala y sede (panel admin).
     */
    public function getAllEquipos(): array
    {
        return $this->fetchAll(
            "SELECT e.id_equipo, e.nombre, e.tipo, e.descripcion, e.activo, e.id_sala,
                    s.nombre AS sala_nombre,
                    se.nombre AS sede_nombre
               FROM salas.Equipo e
               INNER JOIN salas.Sala s ON e.id_sala = s.id_sala
               INNER JOIN comun.Sedes se ON s.id_sede = se.id
              ORDER BY e.activo DESC, se.nombre ASC, s.nombre ASC, e.nombre ASC"
        );
    }

    public function getEquipoById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT id_equipo, nombre, tipo, descripcion, activo, id_sala
               FROM salas.Equipo WHERE id_equipo = ?",
            [$id]
        );
    }

    /**
     * Crea o actualiza un equipo. Devuelve el id resultante o false en error.
     */
    public function guardarEquipo(array $datos)
    {
        $nombre      = trim($datos['nombre']);
        $tipo        = trim($datos['tipo']);
        $descripcion = trim($datos['descripcion'] ?? '');
        $id_sala     = (int) $datos['id_sala'];

        if (empty($datos['id_equipo'])) {
            return $this->insertAndGetId(
                "INSERT INTO salas.Equipo (id_sala, nombre, tipo, descripcion) VALUES (?, ?, ?, ?)",
                [$id_sala, $nombre, $tipo, $descripcion]
            );
        }

        $ok = $this->execute(
            "UPDATE salas.Equipo SET id_sala = ?, nombre = ?, tipo = ?, descripcion = ? WHERE id_equipo = ?",
            [$id_sala, $nombre, $tipo, $descripcion, (int) $datos['id_equipo']]
        );
        return $ok ? (int) $datos['id_equipo'] : false;
    }

    public function toggleEquipo(int $id, int $activo): bool
    {
        return $this->execute(
            "UPDATE salas.Equipo SET activo = ? WHERE id_equipo = ?",
            [$activo, $id]
        );
    }
}
