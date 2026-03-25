<?php
require_once __DIR__ . '/../core/BaseModel.php';
require_once __DIR__ . '/../../../../core/MailService.php';
/**
 * AutorizacionRepository.php
 * Operaciones de aprobaciÃ³n, rechazo e historial de reservas (rol Autorizador/Admin).
 *
 * Principio SOLID aplicado: SRP â€” solo gestiona el flujo de autorizaciÃ³n.
 * Proyecto Especial Chavimochic (PECH) â€” GestionTI v1.0
 */

class AutorizacionRepository extends BaseModel
{
    /**
     * Reservas en estado PENDIENTE ordenadas por fecha/hora ascendente.
     */
    public function getReservasPendientes(): array
    {
        return $this->fetchAll(
            "SELECT r.id_reserva,
                    CONVERT(varchar(10), r.fecha, 120) AS fecha,
                    CONVERT(varchar(5), r.hora_inicio, 108) AS hora_inicio,
                    CONVERT(varchar(5), r.hora_fin, 108) AS hora_fin,
                    r.motivo,
                    r.estado,
                    CONVERT(varchar(16), r.created_at, 120) AS created_at,
                    s.nombre AS sala_nombre,
                    se.nombre AS sede_nombre,
                    u.nombres + ' ' + u.apellidos AS solicitante_nombre,
                    u.correo AS solicitante_correo
               FROM salas.Reserva r
               INNER JOIN salas.Sala s ON r.id_sala = s.id_sala
               INNER JOIN comun.Sedes se ON s.id_sede = se.id
               INNER JOIN comun.Usuarios u ON r.id_usuario_solicitante = u.id_usuario
              WHERE r.estado = 'PENDIENTE'
              ORDER BY r.fecha ASC, r.hora_inicio ASC"
        );
    }

    /**
     * Aprueba una reserva PENDIENTE verificando conflicto con aprobadas (RF-G01).
     */
    public function aprobarReserva(int $id_reserva, int $id_autorizador): array
    {
        $reserva = $this->fetchOne(
            "SELECT id_reserva, id_sala, fecha, hora_inicio, hora_fin, estado
               FROM salas.Reserva WHERE id_reserva = ?",
            [$id_reserva]
        );

        if (!$reserva)
            return ['ok' => false, 'msg' => 'Reserva no encontrada.'];
        if ($reserva['estado'] !== self::ESTADO_PENDIENTE)
            return ['ok' => false, 'msg' => 'La reserva no estÃ¡ en estado PENDIENTE.'];

        $conflicto = $this->fetchOne(
            "SELECT COUNT(*) AS total
               FROM salas.Reserva
              WHERE id_sala = ?
                AND fecha = ?
                AND estado = 'APROBADA'
                AND ? < hora_fin
                AND ? > hora_inicio
                AND id_reserva <> ?",
            [
                $reserva['id_sala'], $reserva['fecha'],
                $reserva['hora_inicio'], $reserva['hora_fin'],
                $id_reserva,
            ]
        );

        if ((int) ($conflicto['total'] ?? 0) > 0) {
            return [
                'ok'  => false,
                'msg' => 'No se puede aprobar: existe una reserva aprobada en ese horario para la misma sala.',
            ];
        }

        $ok = $this->execute(
            "UPDATE salas.Reserva
                SET estado = 'APROBADA',
                    id_usuario_autorizador = ?,
                    fecha_aprobacion = SYSDATETIME(),
                    updated_at = SYSDATETIME()
              WHERE id_reserva = ? AND estado = 'PENDIENTE'",
            [$id_autorizador, $id_reserva]
        );

        if ($ok) {
            // Notificar al solicitante por correo
            $info = $this->fetchOne(
                "SELECT r.motivo,
                        CONVERT(varchar(10), r.fecha, 120) AS fecha,
                        CONVERT(varchar(5), r.hora_inicio, 108) AS hora_inicio,
                        CONVERT(varchar(5), r.hora_fin, 108) AS hora_fin,
                        u.nombres + ' ' + u.apellidos AS nombre,
                        u.correo,
                        s.nombre AS sala,
                        se.nombre AS sede,
                        STUFF(
                            (SELECT ', ' + e.nombre
                               FROM salas.Reserva_Equipo re2
                               INNER JOIN salas.Equipo e ON re2.id_equipo = e.id_equipo
                              WHERE re2.id_reserva = r.id_reserva
                              FOR XML PATH(''), TYPE).value('.','nvarchar(max)'),
                            1, 2, '') AS equipos_lista
                   FROM salas.Reserva r
                   INNER JOIN comun.Usuarios u ON r.id_usuario_solicitante = u.id_usuario
                   INNER JOIN salas.Sala s ON r.id_sala = s.id_sala
                   INNER JOIN comun.Sedes se ON s.id_sede = se.id
                  WHERE r.id_reserva = ?",
                [$id_reserva]
            );
            if ($info) {
                MailService::notificarReservaAprobada([
                    'correo'      => $info['correo'],
                    'nombre'      => $info['nombre'],
                    'id_reserva'  => $id_reserva,
                    'sede'        => $info['sede'],
                    'sala'        => $info['sala'],
                    'fecha'       => $info['fecha'],
                    'hora_inicio' => $info['hora_inicio'],
                    'hora_fin'    => $info['hora_fin'],
                    'motivo'      => $info['motivo'],
                    'equipos'     => $info['equipos_lista'] ?? '',
                ]);
            }
        }

        return $ok
            ? ['ok' => true,  'msg' => 'Reserva aprobada correctamente.']
            : ['ok' => false, 'msg' => 'Error al aprobar la reserva.'];
    }

    /**
     * Rechaza una reserva PENDIENTE con observaciÃ³n opcional.
     */
    public function rechazarReserva(int $id_reserva, int $id_autorizador, string $observacion = ''): array
    {
        $reserva = $this->fetchOne(
            "SELECT id_reserva, estado FROM salas.Reserva WHERE id_reserva = ?",
            [$id_reserva]
        );

        if (!$reserva)
            return ['ok' => false, 'msg' => 'Reserva no encontrada.'];
        if ($reserva['estado'] !== self::ESTADO_PENDIENTE)
            return ['ok' => false, 'msg' => 'La reserva no estÃ¡ en estado PENDIENTE.'];

        $obs = empty(trim($observacion)) ? null : trim($observacion);

        // Obtener datos antes de rechazar para la notificaciÃ³n
        $info = $this->fetchOne(
            "SELECT r.motivo,
                    CONVERT(varchar(10), r.fecha, 120) AS fecha,
                    CONVERT(varchar(5), r.hora_inicio, 108) AS hora_inicio,
                    CONVERT(varchar(5), r.hora_fin, 108) AS hora_fin,
                    u.nombres + ' ' + u.apellidos AS nombre,
                    u.correo,
                    s.nombre AS sala,
                    se.nombre AS sede,
                    STUFF(
                        (SELECT ', ' + e.nombre
                           FROM salas.Reserva_Equipo re2
                           INNER JOIN salas.Equipo e ON re2.id_equipo = e.id_equipo
                          WHERE re2.id_reserva = r.id_reserva
                          FOR XML PATH(''), TYPE).value('.','nvarchar(max)'),
                        1, 2, '') AS equipos_lista
               FROM salas.Reserva r
               INNER JOIN comun.Usuarios u ON r.id_usuario_solicitante = u.id_usuario
               INNER JOIN salas.Sala s ON r.id_sala = s.id_sala
               INNER JOIN comun.Sedes se ON s.id_sede = se.id
              WHERE r.id_reserva = ?",
            [$id_reserva]
        );

        $ok = $this->execute(
            "UPDATE salas.Reserva
                SET estado = 'RECHAZADA',
                    observacion_rechazo = ?,
                    id_usuario_autorizador = ?,
                    fecha_aprobacion = SYSDATETIME(),
                    updated_at = SYSDATETIME()
              WHERE id_reserva = ? AND estado = 'PENDIENTE'",
            [$obs, $id_autorizador, $id_reserva]
        );

        if ($ok && $info) {
            MailService::notificarReservaRechazada(
                [
                    'correo'      => $info['correo'],
                    'nombre'      => $info['nombre'],
                    'id_reserva'  => $id_reserva,
                    'sede'        => $info['sede'],
                    'sala'        => $info['sala'],
                    'fecha'       => $info['fecha'],
                    'hora_inicio' => $info['hora_inicio'],
                    'hora_fin'    => $info['hora_fin'],
                    'motivo'      => $info['motivo'],
                    'equipos'     => $info['equipos_lista'] ?? '',
                ],
                (string) $obs
            );
        }

        return $ok
            ? ['ok' => true,  'msg' => 'Solicitud rechazada.']
            : ['ok' => false, 'msg' => 'Error al rechazar la reserva.'];
    }

    /**
     * Cancela automÃ¡ticamente las reservas PENDIENTE cuya fecha+hora_inicio ya pasÃ³.
     * Se llama en cada peticiÃ³n AJAX del mÃ³dulo (sin necesidad de cron job).
     *
     * @return int  NÃºmero de reservas canceladas.
     */
    public function cancelarReservasVencidas(): int
    {
        // Obtener los datos de las reservas vencidas ANTES de cancelarlas
        $vencidas = $this->fetchAll(
            "SELECT r.id_reserva, r.motivo,
                    CONVERT(varchar(10), r.fecha, 120) AS fecha,
                    CONVERT(varchar(5), r.hora_inicio, 108) AS hora_inicio,
                    CONVERT(varchar(5), r.hora_fin, 108) AS hora_fin,
                    u.nombres + ' ' + u.apellidos AS nombre,
                    u.correo,
                    s.nombre AS sala,
                    se.nombre AS sede,
                    STUFF(
                        (SELECT ', ' + e.nombre
                           FROM salas.Reserva_Equipo re2
                           INNER JOIN salas.Equipo e ON re2.id_equipo = e.id_equipo
                          WHERE re2.id_reserva = r.id_reserva
                          FOR XML PATH(''), TYPE).value('.','nvarchar(max)'),
                        1, 2, '') AS equipos_lista
               FROM salas.Reserva r
               INNER JOIN comun.Usuarios u ON r.id_usuario_solicitante = u.id_usuario
               INNER JOIN salas.Sala s ON r.id_sala = s.id_sala
               INNER JOIN comun.Sedes se ON s.id_sede = se.id
              WHERE r.estado = 'PENDIENTE'
                AND CAST(
                      CONCAT(
                        CONVERT(varchar(10), r.fecha, 120),
                        ' ',
                        CONVERT(varchar(8), r.hora_inicio, 108)
                      ) AS DATETIME2
                    ) < SYSDATETIME()"
        );

        $stmt = sqlsrv_query(
            $this->db,
            "UPDATE salas.Reserva
                SET estado     = 'CANCELADA',
                    updated_at = SYSDATETIME()
              WHERE estado = 'PENDIENTE'
                AND CAST(
                      CONCAT(
                        CONVERT(varchar(10), fecha, 120),
                        ' ',
                        CONVERT(varchar(8), hora_inicio, 108)
                      ) AS DATETIME2
                    ) < SYSDATETIME()"
        );

        $canceladas = ($stmt !== false) ? (int) sqlsrv_rows_affected($stmt) : 0;

        // Notificar a cada solicitante por correo
        foreach ($vencidas as $r) {
            MailService::notificarReservaCancelada(
                [
                    'correo'      => $r['correo'],
                    'nombre'      => $r['nombre'],
                    'id_reserva'  => $r['id_reserva'],
                    'sede'        => $r['sede'],
                    'sala'        => $r['sala'],
                    'fecha'       => $r['fecha'],
                    'hora_inicio' => $r['hora_inicio'],
                    'hora_fin'    => $r['hora_fin'],
                    'motivo'      => $r['motivo'],
                    'equipos'     => $r['equipos_lista'] ?? '',
                ],
                'La reserva fue cancelada automÃ¡ticamente porque el horario ya expirÃ³ sin ser aprobada.'
            );
        }

        return $canceladas;
    }

    /**
     * Historial de reservas finalizadas con filtros opcionales.
     *
     * @param array $filtros  Claves aceptadas: fecha_desde, fecha_hasta, id_sala, estado.
     */
    public function getHistorial(array $filtros = []): array
    {
        $where  = "r.estado IN ('APROBADA','RECHAZADA','CANCELADA')";
        $params = [];

        if (!empty($filtros['fecha_desde'])) {
            $where   .= ' AND r.fecha >= ?';
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $where   .= ' AND r.fecha <= ?';
            $params[] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['id_sala'])) {
            $where   .= ' AND r.id_sala = ?';
            $params[] = (int) $filtros['id_sala'];
        }
        if (!empty($filtros['id_sede'])) {
            $where   .= ' AND se.id = ?';
            $params[] = (int) $filtros['id_sede'];
        }
        if (!empty($filtros['estado'])) {
            $where   .= ' AND r.estado = ?';
            $params[] = $filtros['estado'];
        }

        return $this->fetchAll(
            "SELECT r.id_reserva,
                    CONVERT(varchar(10), r.fecha, 120) AS fecha,
                    CONVERT(varchar(5), r.hora_inicio, 108) AS hora_inicio,
                    CONVERT(varchar(5), r.hora_fin, 108) AS hora_fin,
                    r.motivo, r.estado, r.observacion_rechazo,
                    CONVERT(varchar(16), r.fecha_aprobacion, 120) AS fecha_aprobacion,
                    s.nombre AS sala_nombre, se.nombre AS sede_nombre,
                    u.nombres  + ' ' + u.apellidos  AS solicitante_nombre,
                    ISNULL(ua.nombres + ' ' + ua.apellidos, '-') AS autorizador_nombre
               FROM salas.Reserva r
               INNER JOIN salas.Sala s ON r.id_sala = s.id_sala
               INNER JOIN comun.Sedes se ON s.id_sede = se.id
               INNER JOIN comun.Usuarios u ON r.id_usuario_solicitante = u.id_usuario
               LEFT  JOIN comun.Usuarios ua ON r.id_usuario_autorizador = ua.id_usuario
              WHERE $where
              ORDER BY r.fecha DESC, r.hora_inicio DESC",
            $params
        );
    }

    /**
     * Historial de cambios de estado de una reserva especÃ­fica (timeline).
     */
    public function getHistorialByReserva(int $id_reserva): array
    {
        return $this->fetchAll(
            "SELECT h.id_historial,
                    h.estado_anterior,
                    h.estado_nuevo,
                    CONVERT(varchar(19), h.fecha_accion, 120) AS fecha_accion,
                    h.observacion,
                    u.nombres + ' ' + u.apellidos AS usuario_accion
               FROM salas.Reserva_Historial h
               INNER JOIN comun.Usuarios u ON h.id_usuario_accion = u.id_usuario
              WHERE h.id_reserva = ?
              ORDER BY h.fecha_accion ASC",
            [$id_reserva]
        );
    }
}

