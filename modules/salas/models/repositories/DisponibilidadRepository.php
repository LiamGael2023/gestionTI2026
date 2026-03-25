<?php
require_once __DIR__ . '/../core/BaseModel.php';
/**
 * DisponibilidadRepository.php
 * VerificaciÃ³n de disponibilidad de salas y eventos para FullCalendar.
 *
 * Principio SOLID aplicado: SRP â€” solo resuelve disponibilidad y eventos de calendario.
 * Proyecto Especial Chavimochic (PECH) â€” GestionTI v1.0
 */

class DisponibilidadRepository extends BaseModel
{
    /**
     * Verifica si una sala estÃ¡ libre en el rango fecha/hora indicado.
     *
     * @param int    $excluir_id  ID de reserva a excluir del chequeo (uso en ediciÃ³n).
     */
    public function verificarDisponibilidad(
        int    $id_sala,
        string $fecha,
        string $hora_inicio,
        string $hora_fin,
        int    $excluir_id = 0
    ): array {
        if ($hora_fin <= $hora_inicio) {
            return [
                'disponible' => false,
                'mensaje'    => 'La hora de fin debe ser mayor a la hora de inicio.',
            ];
        }

        // Validar que el horario solicitado no sea en el pasado
        $inicio_dt = $fecha . ' ' . $hora_inicio;
        if (strtotime($inicio_dt) <= time()) {
            return [
                'disponible' => false,
                'mensaje'    => 'No se puede reservar en una fecha u hora que ya paso.',
            ];
        }

        $excluir_sql = $excluir_id > 0 ? ' AND id_reserva <> ?' : '';
        $params = [$id_sala, $fecha, $hora_inicio, $hora_fin];
        if ($excluir_id > 0) $params[] = $excluir_id;

        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total
               FROM salas.Reserva
              WHERE id_sala = ?
                AND fecha = ?
                AND estado IN ('PENDIENTE','APROBADA')
                AND ? < hora_fin
                AND ? > hora_inicio" . $excluir_sql,
            $params
        );

        return (int) ($row['total'] ?? 0) > 0
            ? ['disponible' => false, 'mensaje' => 'La sala no esta disponible en ese horario.']
            : ['disponible' => true,  'mensaje' => 'La sala esta disponible.'];
    }

    /**
     * Eventos de una sala especÃ­fica para el mini-calendario de la solicitud.
     */
    public function getEventosCalendar(int $id_sala, string $fecha_inicio, string $fecha_fin): array
    {
        $rows = $this->fetchAll(
            "SELECT r.id_reserva,
                    r.motivo,
                    CONVERT(varchar(10), r.fecha, 120) AS fecha,
                    CONVERT(varchar(5), r.hora_inicio, 108) AS hora_inicio,
                    CONVERT(varchar(5), r.hora_fin, 108) AS hora_fin,
                    r.estado,
                    u.nombres + ' ' + u.apellidos AS solicitante
               FROM salas.Reserva r
               INNER JOIN comun.Usuarios u ON r.id_usuario_solicitante = u.id_usuario
              WHERE r.id_sala = ?
                AND r.fecha BETWEEN ? AND ?
                AND r.estado IN ('APROBADA','PENDIENTE')
              ORDER BY r.fecha, r.hora_inicio",
            [$id_sala, $fecha_inicio, $fecha_fin]
        );

        return array_map(function ($row) {
            $color = $row['estado'] === 'APROBADA' ? '#2fb344' : '#f59f00';
            return [
                'id'              => $row['id_reserva'],
                'title'           => $row['solicitante'] . ': ' . $row['motivo'],
                'start'           => $row['fecha'] . 'T' . $row['hora_inicio'],
                'end'             => $row['fecha'] . 'T' . $row['hora_fin'],
                'backgroundColor' => $color,
                'borderColor'     => $color,
                'extendedProps'   => [
                    'id_reserva'  => $row['id_reserva'],
                    'estado'      => $row['estado'],
                    'solicitante' => $row['solicitante'],
                    'motivo'      => $row['motivo'],
                ],
            ];
        }, $rows);
    }

    /**
     * Eventos de todas las salas para el cronograma semanal principal.
     * Filtros opcionales por sede y/o sala.
     */
    public function getEventosCronograma(
        string $fecha_inicio,
        string $fecha_fin,
        ?int   $id_sede = null,
        ?int   $id_sala = null
    ): array {
        $params = [$fecha_inicio, $fecha_fin];
        $extra  = '';

        if ($id_sede !== null) {
            $extra   .= ' AND s.id_sede = ?';
            $params[] = $id_sede;
        }
        if ($id_sala !== null) {
            $extra   .= ' AND r.id_sala = ?';
            $params[] = $id_sala;
        }

        $rows = $this->fetchAll(
            "SELECT r.id_reserva,
                    r.motivo,
                    CONVERT(varchar(10), r.fecha, 120) AS fecha,
                    CONVERT(varchar(5), r.hora_inicio, 108) AS hora_inicio,
                    CONVERT(varchar(5), r.hora_fin, 108) AS hora_fin,
                    r.estado,
                    u.nombres + ' ' + u.apellidos AS solicitante,
                    s.nombre AS sala_nombre,
                    se.nombre AS sede_nombre,
                    ISNULL(STUFF((SELECT ', ' + e.nombre
                                  FROM salas.Reserva_Equipo re2
                                  INNER JOIN salas.Equipo e ON re2.id_equipo = e.id_equipo
                                  WHERE re2.id_reserva = r.id_reserva
                                  FOR XML PATH('')), 1, 2, ''), '') AS equipos_av
               FROM salas.Reserva r
               INNER JOIN comun.Usuarios u ON r.id_usuario_solicitante = u.id_usuario
               INNER JOIN salas.Sala s ON r.id_sala = s.id_sala
               INNER JOIN comun.Sedes se ON s.id_sede = se.id
              WHERE r.fecha BETWEEN ? AND ?
                AND r.estado IN ('APROBADA','PENDIENTE')
                $extra
              ORDER BY r.fecha, r.hora_inicio",
            $params
        );

        return array_map(function ($row) {
            $color = $row['estado'] === 'APROBADA' ? '#2fb344' : '#f59f00';
            $motivo = (string) ($row['motivo'] ?? '');
            $motivo_corto = function_exists('mb_substr')
                ? mb_substr($motivo, 0, 40, 'UTF-8')
                : substr($motivo, 0, 40);
            return [
                'id'              => $row['id_reserva'],
                'title'           => $motivo_corto . ' | ' . $row['sala_nombre'],
                'start'           => $row['fecha'] . 'T' . $row['hora_inicio'],
                'end'             => $row['fecha'] . 'T' . $row['hora_fin'],
                'backgroundColor' => $color,
                'borderColor'     => $color,
                'extendedProps'   => [
                    'id_reserva'  => $row['id_reserva'],
                    'estado'      => $row['estado'],
                    'solicitante' => $row['solicitante'],
                    'sala'        => $row['sala_nombre'],
                    'sede'        => $row['sede_nombre'],
                    'motivo'      => $row['motivo'],
                    'hora_inicio' => $row['hora_inicio'],
                    'hora_fin'    => $row['hora_fin'],
                    'equipos_av'  => $row['equipos_av'] ?? '',
                ],
            ];
        }, $rows);
    }
}

