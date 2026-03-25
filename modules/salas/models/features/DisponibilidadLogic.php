<?php
/**
 * DisponibilidadLogic.php — Lógica compartida de Disponibilidad
 * Features comunes: cálculos y validaciones de disponibilidad
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */

class DisponibilidadLogic
{
    /**
     * Verifica si hay solapamiento entre rangos horarios
     */
    public static function hayConflicto($inicio1, $fin1, $inicio2, $fin2)
    {
        // Convierte a minutos desde inicio del día para comparación
        $start1 = strtotime("2000-01-01 $inicio1");
        $end1 = strtotime("2000-01-01 $fin1");
        $start2 = strtotime("2000-01-01 $inicio2");
        $end2 = strtotime("2000-01-01 $fin2");

        // Hay conflicto si se solapan
        return !($end1 <= $start2 || $end2 <= $start1);
    }

    /**
     * Calcula disponibilidad en un rango de fechas
     */
    public static function calcularDisponibilidad($fecha_inicio, $fecha_fin, $slots_ocupados = [])
    {
        $disponible = [];
        $fecha_actual = new DateTime($fecha_inicio);
        $fecha_final = new DateTime($fecha_fin);

        while ($fecha_actual <= $fecha_final) {
            $fecha_str = $fecha_actual->format('Y-m-d');
            
            // Slots disponibles: 07:00-08:00, 08:00-09:00, ...20:00-21:00
            $slots = [];
            for ($h = 7; $h < 21; $h++) {
                $inicio = sprintf('%02d:00', $h);
                $fin = sprintf('%02d:00', $h + 1);
                
                // Verificar si está ocupado
                $ocupado = false;
                foreach ($slots_ocupados as $ocupada) {
                    if ($ocupada['fecha'] === $fecha_str && 
                        self::hayConflicto($inicio, $fin, $ocupada['inicio'], $ocupada['fin'])) {
                        $ocupado = true;
                        break;
                    }
                }
                
                $slots[] = [
                    'hora' => $inicio,
                    'disponible' => !$ocupado,
                ];
            }

            $disponible[$fecha_str] = $slots;
            $fecha_actual->add(new DateInterval('P1D'));
        }

        return $disponible;
    }
}
