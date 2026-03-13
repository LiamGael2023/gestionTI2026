<?php
require_once __DIR__ . '/BaseModel.php';
/**
 * EstadisticasRepository.php
 * Cálculo de estadísticas para el dashboard del módulo de salas.
 *
 * Principio SOLID aplicado: SRP — solo produce métricas y conteos.
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */

class EstadisticasRepository extends BaseModel
{
    /**
     * Estadísticas de reservas del solicitante (panel lateral, rol Solicitante).
     */
    public function getEstadisticasSolicitante(int $id_usuario): array
    {
        $row = $this->fetchOne(
            "SELECT
                SUM(CASE WHEN estado = 'PENDIENTE' THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN estado = 'APROBADA'  THEN 1 ELSE 0 END) AS aprobadas,
                SUM(CASE WHEN estado = 'RECHAZADA' THEN 1 ELSE 0 END) AS rechazadas,
                SUM(CASE WHEN estado = 'CANCELADA' THEN 1 ELSE 0 END) AS canceladas,
                COUNT(*) AS total
               FROM salas.Reserva
              WHERE id_usuario_solicitante = ?",
            [$id_usuario]
        );

        return $row ?? $this->defaultStats();
    }

    /**
     * Estadísticas globales (panel lateral, rol Autorizador/Admin).
     * Incluye conteo de salas y sedes activas.
     */
    public function getEstadisticasGlobales(): array
    {
        $row = $this->fetchOne(
            "SELECT
                SUM(CASE WHEN estado = 'PENDIENTE' THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN estado = 'APROBADA'  THEN 1 ELSE 0 END) AS aprobadas,
                SUM(CASE WHEN estado = 'RECHAZADA' THEN 1 ELSE 0 END) AS rechazadas,
                SUM(CASE WHEN estado = 'CANCELADA' THEN 1 ELSE 0 END) AS canceladas,
                COUNT(*) AS total
               FROM salas.Reserva"
        );

        $salas = $this->fetchOne("SELECT COUNT(*) AS total FROM salas.Sala    WHERE activo = 1");
        $sedes = $this->fetchOne("SELECT COUNT(*) AS total FROM comun.Sedes WHERE activo = 1");

        return array_merge(
            $row ?? $this->defaultStats(),
            [
                'salas_activas' => (int) ($salas['total'] ?? 0),
                'sedes_activas' => (int) ($sedes['total'] ?? 0),
            ]
        );
    }

    // ── Privado ────────────────────────────────────────────────────────────

    private function defaultStats(): array
    {
        return [
            'pendientes' => 0,
            'aprobadas'  => 0,
            'rechazadas' => 0,
            'canceladas' => 0,
            'total'      => 0,
        ];
    }
}
