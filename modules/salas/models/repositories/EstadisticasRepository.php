<?php
require_once __DIR__ . '/../core/BaseModel.php';
/**
 * EstadisticasRepository.php
 * CÃ¡lculo de estadÃ­sticas para el dashboard del mÃ³dulo de salas.
 *
 * Principio SOLID aplicado: SRP â€” solo produce mÃ©tricas y conteos.
 * Proyecto Especial Chavimochic (PECH) â€” GestionTI v1.0
 */

class EstadisticasRepository extends BaseModel
{
    /**
     * EstadÃ­sticas de reservas del solicitante (panel lateral, rol Solicitante).
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
     * EstadÃ­sticas globales (panel lateral, rol Autorizador/Admin).
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

    // â”€â”€ Privado â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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

