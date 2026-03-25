<?php
require_once __DIR__ . '/../core/BaseModel.php';
require_once __DIR__ . '/../../../../core/MailService.php';
/**
 * ReservasRepository.php
 * Operaciones CRUD de reservas para el rol Solicitante.
 *
 * Principio SOLID aplicado: SRP â€” solo gestiona el ciclo de vida
 * de las reservas desde la perspectiva del solicitante.
 * Proyecto Especial Chavimochic (PECH) â€” GestionTI v1.0
 */

class ReservasRepository extends BaseModel
{
    /**
     * Crea una nueva reserva con sus equipos AV asociados.
     * Valida (RN-05) que los equipos pertenezcan a la sala solicitada.
     *
     * @return int|false  ID de la reserva creada o false en error.
     */
    public function crearReserva(array $datos)
    {
        $id_usuario  = (int) $datos['id_usuario_solicitante'];
        $id_sala     = (int) $datos['id_sala'];
        $fecha       = $datos['fecha'];
        $hora_inicio = $datos['hora_inicio'];
        $hora_fin    = $datos['hora_fin'];
        $motivo      = trim($datos['motivo']);

        $id_reserva = $this->insertAndGetId(
            "INSERT INTO salas.Reserva
                (id_usuario_solicitante, id_sala, fecha, hora_inicio, hora_fin, motivo)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$id_usuario, $id_sala, $fecha, $hora_inicio, $hora_fin, $motivo]
        );

        if ($id_reserva === false) return false;

        foreach ((array) ($datos['equipos'] ?? []) as $id_equipo) {
            $eq = $this->fetchOne(
                "SELECT id_equipo FROM salas.Equipo
                  WHERE id_equipo = ? AND id_sala = ? AND activo = 1",
                [(int) $id_equipo, $id_sala]
            );
            if ($eq) {
                $this->execute(
                    "INSERT INTO salas.Reserva_Equipo (id_reserva, id_equipo) VALUES (?, ?)",
                    [$id_reserva, (int) $id_equipo]
                );
            }
        }

        // Notificar al solicitante sobre la nueva reserva registrada
        $info = $this->fetchOne(
            "SELECT u.nombres + ' ' + u.apellidos AS nombre, u.correo,
                    s.nombre AS sala, se.nombre AS sede,
                    STUFF(
                        (SELECT ', ' + e.nombre
                           FROM salas.Reserva_Equipo re
                           INNER JOIN salas.Equipo e ON re.id_equipo = e.id_equipo
                          WHERE re.id_reserva = ?
                          FOR XML PATH(''), TYPE).value('.','nvarchar(max)'),
                        1, 2, '') AS equipos_lista
               FROM comun.Usuarios u
               INNER JOIN salas.Sala s ON s.id_sala = ?
               INNER JOIN comun.Sedes se ON s.id_sede = se.id
              WHERE u.id_usuario = ?",
            [$id_reserva, $id_sala, $id_usuario]
        );
        if ($info) {
            MailService::notificarReservaCreada([
                'correo'      => $info['correo'],
                'nombre'      => $info['nombre'],
                'id_reserva'  => $id_reserva,
                'sede'        => $info['sede'],
                'sala'        => $info['sala'],
                'fecha'       => $fecha,
                'hora_inicio' => $hora_inicio,
                'hora_fin'    => $hora_fin,
                'motivo'      => $motivo,
                'equipos'     => $info['equipos_lista'] ?? '',
            ]);
        }

        return $id_reserva;
    }

    /**
     * Reservas del solicitante ordenadas por fecha de creaciÃ³n descendente.
     */
    public function getMisReservas(int $id_usuario): array
    {
        return $this->fetchAll(
            "SELECT r.id_reserva,
                    CONVERT(varchar(10), r.fecha, 120) AS fecha,
                    CONVERT(varchar(5), r.hora_inicio, 108) AS hora_inicio,
                    CONVERT(varchar(5), r.hora_fin, 108) AS hora_fin,
                    r.motivo,
                    r.estado,
                    r.observacion_rechazo,
                    CONVERT(varchar(16), r.created_at, 120) AS created_at,
                    s.nombre AS sala_nombre,
                    se.nombre AS sede_nombre,
                    ISNULL(ua.nombres + ' ' + ua.apellidos, '-') AS autorizador_nombre,
                    CONVERT(varchar(16), r.fecha_aprobacion, 120) AS fecha_aprobacion
               FROM salas.Reserva r
               INNER JOIN salas.Sala s ON r.id_sala = s.id_sala
               INNER JOIN comun.Sedes se ON s.id_sede = se.id
               LEFT  JOIN comun.Usuarios ua ON r.id_usuario_autorizador = ua.id_usuario
              WHERE r.id_usuario_solicitante = ?
              ORDER BY r.created_at DESC",
            [$id_usuario]
        );
    }

    /**
     * Detalle completo de una reserva incluyendo equipos AV.
     * Si $id_usuario > 0 restringe al propietario (solicitante).
     */
    public function getReservaDetalle(int $id_reserva, int $id_usuario = 0): ?array
    {
        $filtro = '';
        $params = [$id_reserva];
        if ($id_usuario > 0) {
            $filtro = ' AND r.id_usuario_solicitante = ?';
            $params[] = $id_usuario;
        }

        $reserva = $this->fetchOne(
            "SELECT r.id_reserva,
                    r.id_sala,
                    r.id_usuario_solicitante,
                    CONVERT(varchar(10), r.fecha, 120) AS fecha,
                    CONVERT(varchar(5), r.hora_inicio, 108) AS hora_inicio,
                    CONVERT(varchar(5), r.hora_fin, 108) AS hora_fin,
                    r.motivo,
                    r.estado,
                    r.observacion_rechazo,
                    CONVERT(varchar(16), r.created_at, 120) AS created_at,
                    CONVERT(varchar(16), r.updated_at, 120) AS updated_at,
                    s.nombre AS sala_nombre,
                    s.capacidad,
                    se.id AS id_sede,
                    se.nombre AS sede_nombre,
                    us.nombres + ' ' + us.apellidos AS solicitante_nombre,
                    us.correo AS solicitante_correo,
                    ISNULL(ua.nombres + ' ' + ua.apellidos, '-') AS autorizador_nombre,
                    CONVERT(varchar(16), r.fecha_aprobacion, 120) AS fecha_aprobacion
               FROM salas.Reserva r
               INNER JOIN salas.Sala s ON r.id_sala = s.id_sala
               INNER JOIN comun.Sedes se ON s.id_sede = se.id
               INNER JOIN comun.Usuarios us ON r.id_usuario_solicitante = us.id_usuario
               LEFT  JOIN comun.Usuarios ua ON r.id_usuario_autorizador = ua.id_usuario
              WHERE r.id_reserva = ?" . $filtro,
                        $params
        );

        if (!$reserva) return null;

        $reserva['equipos'] = $this->fetchAll(
            "SELECT e.id_equipo, e.nombre, e.tipo
               FROM salas.Reserva_Equipo re
               INNER JOIN salas.Equipo e ON re.id_equipo = e.id_equipo
              WHERE re.id_reserva = ?",
            [$id_reserva]
        );

        return $reserva;
    }

    /**
     * Edita una reserva PENDIENTE verificando disponibilidad y validando equipos.
     */
    public function editarReserva(int $id_reserva, array $datos, int $id_usuario): array
    {
        $reserva = $this->fetchOne(
            "SELECT id_reserva, estado FROM salas.Reserva
              WHERE id_reserva = ? AND id_usuario_solicitante = ?",
            [$id_reserva, $id_usuario]
        );

        if (!$reserva)
            return ['ok' => false, 'msg' => 'Reserva no encontrada.'];
        if ($reserva['estado'] !== self::ESTADO_PENDIENTE)
            return ['ok' => false, 'msg' => 'Solo se pueden editar reservas en estado PENDIENTE.'];

        // Verificar disponibilidad excluyendo la reserva actual
        $disp = (new \DisponibilidadRepository($this->db))->verificarDisponibilidad(
            (int) $datos['id_sala'],
            $datos['fecha'],
            $datos['hora_inicio'],
            $datos['hora_fin'],
            $id_reserva
        );
        if (!$disp['disponible'])
            return ['ok' => false, 'msg' => $disp['mensaje']];

        $ok = $this->execute(
            "UPDATE salas.Reserva
                SET id_sala = ?, fecha = ?, hora_inicio = ?, hora_fin = ?,
                    motivo = ?, updated_at = SYSDATETIME()
              WHERE id_reserva = ? AND estado = 'PENDIENTE'",
            [
                (int) $datos['id_sala'],
                $datos['fecha'],
                $datos['hora_inicio'],
                $datos['hora_fin'],
                trim($datos['motivo']),
                $id_reserva,
            ]
        );

        if (!$ok) return ['ok' => false, 'msg' => 'Error al actualizar la reserva.'];

        // Reemplazar equipos
        $this->execute(
            "DELETE FROM salas.Reserva_Equipo WHERE id_reserva = ?",
            [$id_reserva]
        );
        foreach ((array) ($datos['equipos'] ?? []) as $id_equipo) {
            $eq = $this->fetchOne(
                "SELECT id_equipo FROM salas.Equipo
                  WHERE id_equipo = ? AND id_sala = ? AND activo = 1",
                [(int) $id_equipo, (int) $datos['id_sala']]
            );
            if ($eq) {
                $this->execute(
                    "INSERT INTO salas.Reserva_Equipo (id_reserva, id_equipo) VALUES (?, ?)",
                    [$id_reserva, (int) $id_equipo]
                );
            }
        }

        return ['ok' => true, 'msg' => 'Reserva actualizada correctamente.'];
    }

    /**
     * Cancela una reserva PENDIENTE del solicitante.
     */
    public function cancelarReserva(int $id_reserva, int $id_usuario): array
    {
        $reserva = $this->fetchOne(
            "SELECT id_reserva, estado FROM salas.Reserva
              WHERE id_reserva = ? AND id_usuario_solicitante = ?",
            [$id_reserva, $id_usuario]
        );

        if (!$reserva)
            return ['ok' => false, 'msg' => 'Reserva no encontrada.'];
        if ($reserva['estado'] !== self::ESTADO_PENDIENTE)
            return ['ok' => false, 'msg' => 'Solo se pueden cancelar reservas en estado PENDIENTE.'];

        // Obtener datos para la notificaciÃ³n antes de cancelar
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
                SET estado = 'CANCELADA', updated_at = SYSDATETIME()
              WHERE id_reserva = ? AND estado = 'PENDIENTE'",
            [$id_reserva]
        );

        if ($ok && $info) {
            MailService::notificarReservaCancelada(
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
                'Cancelada por el solicitante.'
            );
        }

        return $ok
            ? ['ok' => true,  'msg' => 'Reserva cancelada correctamente.']
            : ['ok' => false, 'msg' => 'Error al cancelar la reserva.'];
    }
}

