<?php
/**
 * DashboardRepository.php
 * Repositorio especializado para queries de Dashboard
 * Indicadores: Utilización, Estado, Sedes, Tendencias, Gerencia
 * Proyecto Especial Chavimochic (PECH) — GestionTI v1.0
 */

require_once __DIR__ . '/../core/BaseModel.php';

class DashboardRepository extends BaseModel
{
    private $personalApiDataCache = null;

    /**
     * Obtiene y cachea el listado del API de personal por cada request.
     */
    private function obtenerListadoPersonalApi(): array
    {
        if (is_array($this->personalApiDataCache)) {
            return $this->personalApiDataCache;
        }

        $apiUrl = 'https://www.chavimochic.gob.pe/api_incidencias/api_personal.php';
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'ignore_errors' => true,
                'header' => "User-Agent: GestionTI/1.0\r\n",
            ],
        ]);

        $jsonResponse = @file_get_contents($apiUrl, false, $context);
        if ($jsonResponse === false) {
            $this->personalApiDataCache = [];
            return $this->personalApiDataCache;
        }

        $respuesta = json_decode($jsonResponse, true);
        $lista = $respuesta['data'] ?? [];
        $this->personalApiDataCache = is_array($lista) ? $lista : [];

        return $this->personalApiDataCache;
    }

    /**
     * Función auxiliar: Obtener documentos de usuarios con gerencia válida
     * Llamar UNA SOLA VEZ desde el AJAX handler
     */
    public function obtenerDocumentosConGerenciaValida(): array
    {
        $listaPersonal = $this->obtenerListadoPersonalApi();

        $documentosValidos = [];
        foreach ($listaPersonal as $persona) {
            $gerencia = $persona['Gerencia_Laboral'] ?? null;
            // Solo incluir si tiene gerencia válida (no null, no vacío, no "sin asignar")
            if (!empty($gerencia) && $gerencia !== 'Sin asignar' && $gerencia !== 'NULL' && $gerencia !== 'Sincronizando...') {
                $documentosValidos[] = $persona['Documento'];
            }
        }

        return $documentosValidos;
    }

    /**
     * INDICADOR 1: Utilización de Salas (general + top 5 + menos utilizadas)
     */
    public function getUtilizacionSalas(array $documentos_validos = []): array
    {
        // Ocupación general (todos los registros - SIN filtro)
        $ocupacionGeneral = $this->fetchOne(
            "SELECT 
                ISNULL(COUNT(DISTINCT r.id_sala), 0) AS salas_utilizadas,
                (SELECT ISNULL(COUNT(*), 0) FROM salas.Sala s WHERE s.activo = 1) AS total_salas_activas,
                ISNULL(COUNT(r.id_reserva), 0) AS total_reservas,
                ISNULL(SUM(DATEDIFF(MINUTE, r.hora_inicio, r.hora_fin)), 0) AS minutos_totales,
                CAST(ISNULL(ROUND(SUM(DATEDIFF(MINUTE, r.hora_inicio, r.hora_fin)) / 60.0, 1), 0) AS DECIMAL(10,1)) AS horas_totales,
                CAST(ISNULL(ROUND(CASE 
                    WHEN COUNT(r.id_reserva) > 0 THEN SUM(DATEDIFF(MINUTE, r.hora_inicio, r.hora_fin)) / 60.0 / COUNT(r.id_reserva) 
                    ELSE 0 
                END, 1), 0) AS DECIMAL(10,1)) AS promedio_horas_por_reserva
             FROM salas.Reserva r
             WHERE r.estado IN ('APROBADA', 'PENDIENTE')",
            []
        );

        // Si el resultado es null, retornar valores por defecto
        if (!$ocupacionGeneral) {
            $ocupacionGeneral = [
                'salas_utilizadas' => 0,
                'total_salas_activas' => 0,
                'total_reservas' => 0,
                'minutos_totales' => 0,
                'horas_totales' => 0,
                'promedio_horas_por_reserva' => 0
            ];
        }

        // Top 5 salas más utilizadas
        $topSalas = $this->fetchAll(
            "SELECT TOP 3
                s.id_sala,
                s.nombre AS sala_nombre,
                se.nombre AS sede_nombre,
                ISNULL(COUNT(r.id_reserva), 0) AS total_reservas,
                CAST(ISNULL(SUM(DATEDIFF(MINUTE, r.hora_inicio, r.hora_fin)) / 60.0, 0) AS DECIMAL(10,1)) AS horas_utilizadas,
                CAST(ISNULL(ROUND(COUNT(r.id_reserva) * 100.0 / NULLIF((SELECT COUNT(*) FROM salas.Reserva WHERE estado IN ('APROBADA', 'PENDIENTE')), 0), 1), 0) AS DECIMAL(10,1)) AS porcentaje
             FROM salas.Sala s
             LEFT JOIN salas.Reserva r ON s.id_sala = r.id_sala 
                AND r.estado IN ('APROBADA', 'PENDIENTE')
             INNER JOIN comun.Sedes se ON s.id_sede = se.id
             WHERE s.activo = 1
             GROUP BY s.id_sala, s.nombre, se.nombre
             ORDER BY total_reservas DESC",
            []
        );

        // Salas menos utilizadas
        $menosSalas = $this->fetchAll(
            "SELECT TOP 3
                s.id_sala,
                s.nombre AS sala_nombre,
                se.nombre AS sede_nombre,
                ISNULL(COUNT(r.id_reserva), 0) AS total_reservas,
                CAST(ISNULL(SUM(DATEDIFF(MINUTE, r.hora_inicio, r.hora_fin)) / 60.0, 0) AS DECIMAL(10,1)) AS horas_utilizadas
             FROM salas.Sala s
             LEFT JOIN salas.Reserva r ON s.id_sala = r.id_sala 
                AND r.estado IN ('APROBADA', 'PENDIENTE')
             INNER JOIN comun.Sedes se ON s.id_sede = se.id
             WHERE s.activo = 1
             GROUP BY s.id_sala, s.nombre, se.nombre
             ORDER BY total_reservas ASC",
            []
        );

        return [
            'ocupacion_general' => $ocupacionGeneral,
            'top_salas' => $topSalas,
            'menos_salas' => $menosSalas
        ];
    }

    /**
     * INDICADOR 2: Estado de Solicitudes (últimos 30 días)
     * SIN filtro de gerencia - muestra TODOS los datos
     */
    public function getEstadoSolicitudes(array $documentos_validos = []): array
    {
        $estados = $this->fetchAll(
            "SELECT 
                r.estado,
                COUNT(r.id_reserva) AS total
             FROM salas.Reserva r
             GROUP BY r.estado
             ORDER BY total DESC",
            []
        );

        // Calcular porcentaje en PHP
        $totalReservas = array_sum(array_column($estados, 'total'));
        if ($totalReservas > 0) {
            foreach ($estados as &$estado) {
                $estado['porcentaje'] = round($estado['total'] * 100.0 / $totalReservas, 1);
            }
        }

        // Tiempo promedio de aprobación GENERAL (sin filtro de gerencia)
        $tiempoAprobacion = $this->fetchOne(
            "SELECT 
                ISNULL(AVG(DATEDIFF(HOUR, r.created_at, r.fecha_aprobacion)), 0) AS promedio_horas,
                ISNULL(MIN(DATEDIFF(HOUR, r.created_at, r.fecha_aprobacion)), 0) AS minimo_horas,
                ISNULL(MAX(DATEDIFF(HOUR, r.created_at, r.fecha_aprobacion)), 0) AS maximo_horas,
                COUNT(r.id_reserva) AS total_aprobadas
             FROM salas.Reserva r
             WHERE r.estado = 'APROBADA'
             AND r.fecha_aprobacion IS NOT NULL",
            []
        );

        if (!$tiempoAprobacion) {
            $tiempoAprobacion = [
                'promedio_horas' => 0,
                'minimo_horas' => 0,
                'maximo_horas' => 0,
                'total_aprobadas' => 0
            ];
        }

        // Pendientes hace más de 24h
        $pendientesVencidas = $this->fetchOne(
            "SELECT 
                ISNULL(COUNT(r.id_reserva), 0) AS total
             FROM salas.Reserva r
             WHERE r.estado = 'PENDIENTE'
             AND DATEDIFF(HOUR, r.created_at, GETDATE()) > 24",
            []
        );

        if (!$pendientesVencidas) {
            $pendientesVencidas = ['total' => 0];
        }

        return [
            'estados' => $estados,
            'tiempo_aprobacion' => $tiempoAprobacion,
            'pendientes_vencidas' => $pendientesVencidas
        ];
    }

    /**
     * INDICADOR 4: Top 3 Equipos Más Usados por Sala
     * SIN filtro de gerencia - muestra TODOS los datos
     */
    public function getTopEquiposPorSala(array $documentos_validos = []): array
    {
        // Obtener todas las salas activas
        $salas = $this->fetchAll(
            "SELECT DISTINCT
                s.id_sala,
                s.nombre AS sala_nombre
             FROM salas.Sala s
             WHERE s.activo = 1
             ORDER BY s.nombre",
            []
        );

        $resultado = [];
        foreach ($salas as $sala) {
            // Top 3 equipos por sala
            $equipos = $this->fetchAll(
                "SELECT TOP 3
                    e.id_equipo,
                    e.nombre,
                    ISNULL(COUNT(re.id_equipo), 0) AS uso_count
                 FROM salas.Equipo e
                 LEFT JOIN salas.Reserva_Equipo re ON e.id_equipo = re.id_equipo
                 WHERE e.id_sala = ?
                 AND e.activo = 1
                 GROUP BY e.id_equipo, e.nombre
                 ORDER BY uso_count DESC",
                [$sala['id_sala']]
            );

            $resultado[] = [
                'sala_nombre' => $sala['sala_nombre'],
                'equipos' => $equipos ?: []
            ];
        }

        return $resultado;
    }

    /**
     * INDICADOR 5: Tendencias Temporales (últimos 7 días)
     * SIN filtro de gerencia - muestra TODOS los datos
     */
    public function getTendenciasTemporales(array $documentos_validos = []): array
    {
        // Últimos 30 días
        $tendencias = $this->fetchAll(
            "SELECT 
                CAST(r.fecha AS DATE) AS fecha,
                CASE DATENAME(WEEKDAY, r.fecha)
                    WHEN 'Monday' THEN 'Lunes'
                    WHEN 'Tuesday' THEN 'Martes'
                    WHEN 'Wednesday' THEN 'Miércoles'
                    WHEN 'Thursday' THEN 'Jueves'
                    WHEN 'Friday' THEN 'Viernes'
                    WHEN 'Saturday' THEN 'Sábado'
                    WHEN 'Sunday' THEN 'Domingo'
                END AS dia_semana,
                ISNULL(COUNT(r.id_reserva), 0) AS total_reservas,
                CAST(ISNULL(SUM(DATEDIFF(MINUTE, r.hora_inicio, r.hora_fin)) / 60.0, 0) AS DECIMAL(10,1)) AS horas_utilizadas
             FROM salas.Reserva r
             WHERE r.estado IN ('APROBADA', 'PENDIENTE')
             GROUP BY CAST(r.fecha AS DATE), DATENAME(WEEKDAY, r.fecha)
             ORDER BY CAST(r.fecha AS DATE) DESC",
            []
        );

        // Horas pico de uso
        $horsasPico = $this->fetchAll(
            "SELECT TOP 5
                DATEPART(HOUR, r.hora_inicio) AS hora,
                COUNT(r.id_reserva) AS total_reservas
             FROM salas.Reserva r
             WHERE r.estado IN ('APROBADA', 'PENDIENTE')
             GROUP BY DATEPART(HOUR, r.hora_inicio)
             ORDER BY total_reservas DESC",
            []
        );

        // Día más utilizado
        $diaMasUtilizado = $this->fetchOne(
            "SELECT TOP 1
                CAST(r.fecha AS DATE) AS fecha,
                CASE DATENAME(WEEKDAY, r.fecha)
                    WHEN 'Monday' THEN 'Lunes'
                    WHEN 'Tuesday' THEN 'Martes'
                    WHEN 'Wednesday' THEN 'Miércoles'
                    WHEN 'Thursday' THEN 'Jueves'
                    WHEN 'Friday' THEN 'Viernes'
                    WHEN 'Saturday' THEN 'Sábado'
                    WHEN 'Sunday' THEN 'Domingo'
                END AS dia_semana,
                COUNT(r.id_reserva) AS total_reservas
             FROM salas.Reserva r
             WHERE r.estado IN ('APROBADA', 'PENDIENTE')
             GROUP BY CAST(r.fecha AS DATE), DATENAME(WEEKDAY, r.fecha)
             ORDER BY total_reservas DESC",
            []
        );

        if (!$diaMasUtilizado) {
            $diaMasUtilizado = ['fecha' => null, 'dia_semana' => 'N/A', 'total_reservas' => 0];
        }

        // Día menos utilizado
        $diaMenosUtilizado = $this->fetchOne(
            "SELECT TOP 1
                CAST(r.fecha AS DATE) AS fecha,
                CASE DATENAME(WEEKDAY, r.fecha)
                    WHEN 'Monday' THEN 'Lunes'
                    WHEN 'Tuesday' THEN 'Martes'
                    WHEN 'Wednesday' THEN 'Miércoles'
                    WHEN 'Thursday' THEN 'Jueves'
                    WHEN 'Friday' THEN 'Viernes'
                    WHEN 'Saturday' THEN 'Sábado'
                    WHEN 'Sunday' THEN 'Domingo'
                END AS dia_semana,
                COUNT(r.id_reserva) AS total_reservas
             FROM salas.Reserva r
             WHERE r.estado IN ('APROBADA', 'PENDIENTE')
             GROUP BY CAST(r.fecha AS DATE), DATENAME(WEEKDAY, r.fecha)
             ORDER BY total_reservas ASC",
            []
        );

        if (!$diaMenosUtilizado) {
            $diaMenosUtilizado = ['fecha' => null, 'dia_semana' => 'N/A', 'total_reservas' => 0];
        }

        return [
            'tendencias' => $tendencias ?: [],
            'horas_pico' => $horsasPico ?: [],
            'dia_mas_utilizado' => $diaMasUtilizado,
            'dia_menos_utilizado' => $diaMenosUtilizado
        ];
    }

    /**
     * INDICADOR EXTRA: Gerencias/Unidades que más solicitan y usan salas
     * Usa opción B: Consulta dinámica del API de Personal en tiempo real
     */
    public function getAnalisisPorGerencia(array $documentos_validos = []): array
    {
        // Primero: obtener datos de reservas por usuario (TOP 5)
        $gerWhere = "WHERE u.activo = 1";
        $gerParams = [];
        if (!empty($documentos_validos)) {
            $placeholders = implode(',', array_fill(0, count($documentos_validos), '?'));
            $gerWhere .= " AND u.documento IN ($placeholders)";
            $gerParams = $documentos_validos;
        }

        $reservasPorUsuario = $this->fetchAll(
            "SELECT TOP 5
                u.id_usuario,
                u.usuario,
                u.documento,
                u.nombres + ' ' + u.apellidos AS usuario_nombre,
                u.correo,
                ISNULL(COUNT(r.id_reserva), 0) AS total_solicitudes,
                ISNULL(SUM(CASE WHEN r.estado = 'APROBADA' THEN 1 ELSE 0 END), 0) AS solicitudes_aprobadas,
                ISNULL(SUM(CASE WHEN r.estado = 'PENDIENTE' THEN 1 ELSE 0 END), 0) AS solicitudes_pendientes,
                ISNULL(SUM(CASE WHEN r.estado = 'RECHAZADA' THEN 1 ELSE 0 END), 0) AS solicitudes_rechazadas,
                CAST(ISNULL(SUM(CASE WHEN r.estado IN ('APROBADA', 'PENDIENTE') THEN DATEDIFF(MINUTE, r.hora_inicio, r.hora_fin) ELSE 0 END) / 60.0, 0) AS DECIMAL(10,1)) AS horas_utilizadas
             FROM comun.Usuarios u
             LEFT JOIN salas.Reserva r ON u.id_usuario = r.id_usuario_solicitante
             $gerWhere
             GROUP BY u.id_usuario, u.usuario, u.documento, u.nombres, u.apellidos, u.correo
             ORDER BY total_solicitudes DESC",
            $gerParams
        );

        return $reservasPorUsuario ?: [];
    }

    /**
     * Obtener datos del API de Personal para enriquecer análisis por Gerencia
     * IMPORTANTE: Llama a módulo de usuarios para obtener datos actualizados
     */
    public function obtenerDatosDelAPIPersonal(array $documentos): array
    {
        $listaPersonal = $this->obtenerListadoPersonalApi();
        if (empty($listaPersonal) || empty($documentos)) {
            return [];
        }

        $documentosSet = array_fill_keys(array_map('strval', $documentos), true);

        // Filtrar solo usuarios que tienen reservas
        $datosEnriquecidos = [];
        foreach ($listaPersonal as $persona) {
            $documentoPersona = (string) ($persona['Documento'] ?? '');
            if ($documentoPersona !== '' && isset($documentosSet[$documentoPersona])) {
                $datosEnriquecidos[$persona['Documento']] = [
                    'documento' => $persona['Documento'],
                    'nombre' => $persona['Nombres'] . ' ' . $persona['Trab_Paterno'] . ' ' . $persona['Trab_Materno'],
                    'usuario' => $persona['usuario'] ?? '',
                    'correo_api' => $persona['Correo'] ?? '',
                    'gerencia_laboral' => $persona['Gerencia_Laboral'] ?? 'N/A',
                    'unidad_laboral' => $persona['Unidad_Laboral'] ?? 'N/A',
                    'centro_costo' => $persona['Centro_Costo'] ?? 'N/A'
                ];
            }
        }

        return $datosEnriquecidos;
    }

    /**
     * Tiempo Promedio de Aprobación por Gerencia - TOP 5 (excluye sin gerencia)
     */
    public function getTiempoAprobacionPorGerencia(array $documentos_validos = []): array
    {
        $tiemWhere = "WHERE u.activo = 1";
        $tiemParams = [];
        if (!empty($documentos_validos)) {
            $placeholders = implode(',', array_fill(0, count($documentos_validos), '?'));
            $tiemWhere .= " AND u.documento IN ($placeholders)";
            $tiemParams = $documentos_validos;
        }

        $usuariosTiempo = $this->fetchAll(
            "SELECT TOP 5
                u.documento,
                u.usuario,
                ISNULL(AVG(DATEDIFF(HOUR, r.created_at, r.fecha_aprobacion)), 0) AS tiempo_promedio_horas,
                COUNT(CASE WHEN r.estado = 'APROBADA' AND r.fecha_aprobacion IS NOT NULL THEN 1 END) AS aprobadas
             FROM comun.Usuarios u
             LEFT JOIN salas.Reserva r ON u.id_usuario = r.id_usuario_solicitante
                AND r.estado = 'APROBADA' AND r.fecha_aprobacion IS NOT NULL
             $tiemWhere
             GROUP BY u.documento, u.usuario
             HAVING COUNT(CASE WHEN r.estado = 'APROBADA' AND r.fecha_aprobacion IS NOT NULL THEN 1 END) > 0
             ORDER BY tiempo_promedio_horas ASC",
            $tiemParams
        );

        $documentos = array_filter(array_column($usuariosTiempo, 'documento'));
        $documentosSet = array_fill_keys(array_map('strval', $documentos), true);
        $datosGerencias = [];
        
        try {
            $listaPersonal = $this->obtenerListadoPersonalApi();
            foreach ($listaPersonal as $persona) {
                $documentoPersona = (string) ($persona['Documento'] ?? '');
                if ($documentoPersona === '' || !isset($documentosSet[$documentoPersona])) {
                    continue;
                }

                $gerencia = $persona['Gerencia_Laboral'] ?? null;
                // Solo guardar si tiene gerencia válida (no null, no vacío, no "sin asignar")
                if (!empty($gerencia) && $gerencia !== 'Sin asignar' && $gerencia !== 'NULL') {
                    $datosGerencias[$documentoPersona] = $gerencia;
                }
            }
        } catch (Exception $e) {
        }

        $resultado = [];
        foreach ($usuariosTiempo as $usuario) {
            // Solo procesar si tiene gerencia válida en el API
            if (!isset($datosGerencias[$usuario['documento']])) {
                continue;
            }
            
            $gerencia = $datosGerencias[$usuario['documento']];
            
            if (!isset($resultado[$gerencia])) {
                $resultado[$gerencia] = ['gerencia' => $gerencia, 'total_horas' => 0, 'contador' => 0];
            }
            
            $resultado[$gerencia]['total_horas'] += floatval($usuario['tiempo_promedio_horas']) ?: 0;
            $resultado[$gerencia]['contador'] += 1;
        }

        $resultadoFinal = [];
        foreach ($resultado as $gerencia) {
            $resultadoFinal[] = [
                'gerencia' => $gerencia['gerencia'],
                'tiempo_promedio' => round($gerencia['total_horas'] / $gerencia['contador'], 1)
            ];
        }

        usort($resultadoFinal, function($a, $b) {
            return $a['tiempo_promedio'] <=> $b['tiempo_promedio'];
        });

        return array_slice($resultadoFinal, 0, 5);
    }
}
