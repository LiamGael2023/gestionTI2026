<?php
/**
 * CalendarioLogic.php — Lógica compartida del Calendario
 * Features comunes: cálculos, transformaciones, validaciones de calendario
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */

class CalendarioLogic
{
    /**
     * Valida que la fecha de reserva sea válida
     * (no pasada, dentro de horario permitido, etc.)
     */
    public static function validarFechaReserva($fecha, $hora_inicio, $hora_fin)
    {
        $hoy = date('Y-m-d');
        
        // No puede ser en el pasado
        if ($fecha < $hoy) {
            return ['ok' => false, 'msg' => 'La fecha no puede ser en el pasado.'];
        }

        // Validar horario (07:00 - 21:00)
        if ($hora_inicio < '07:00' || $hora_fin > '21:00') {
            return ['ok' => false, 'msg' => 'El horario debe estar entre 07:00 y 21:00.'];
        }

        // hora_fin debe ser después de hora_inicio
        if ($hora_fin <= $hora_inicio) {
            return ['ok' => false, 'msg' => 'La hora de fin debe ser después de la hora de inicio.'];
        }

        return ['ok' => true, 'msg' => 'Fecha y horario válidos.'];
    }

    /**
     * Formatea fecha de reserva para mostrar en calendario
     */
    public static function formatearFechaCalendario($fecha, $hora_inicio, $hora_fin)
    {
        $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha);
        return [
            'date' => $fecha,
            'start' => $fecha . 'T' . $hora_inicio . ':00',
            'end' => $fecha . 'T' . $hora_fin . ':00',
            'display' => $fecha_obj->format('d/m/Y'),
        ];
    }
}
