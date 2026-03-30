<?php
require_once __DIR__ . "/../models/ReporteAsignacionModel.php";

class ReporteAsignacionController
{
    /* ════════════════════════════════════════════════════════
       OBTENER OPCIONES DE FILTROS (API + BD)
    ════════════════════════════════════════════════════════ */
    static public function ctrObtenerFiltros(): array
    {
        $personal    = ReporteAsignacionModel::mdlObtenerPersonalApi();
        $tiposActivo = ReporteAsignacionModel::mdlListarTiposActivo();

        $gerencias = $unidades = $sedes = $tipos_contrato = [];
        foreach ($personal as $p) {
            $g = trim($p['Gerencia_Laboral']       ?? '');
            $u = trim($p['Unidad_Laboral']          ?? '');
            $s = trim($p['SEDE']                    ?? '');
            $t = trim($p['TrabTipo_Descripcion']    ?? '');
            if ($g && !in_array($g, $gerencias,      true)) $gerencias[]      = $g;
            if ($u && !in_array($u, $unidades,       true)) $unidades[]       = $u;
            if ($s && !in_array($s, $sedes,          true)) $sedes[]          = $s;
            if ($t && !in_array($t, $tipos_contrato, true)) $tipos_contrato[] = $t;
        }
        sort($gerencias); sort($unidades); sort($sedes); sort($tipos_contrato);

        return [
            'gerencias'      => $gerencias,
            'unidades'       => $unidades,
            'sedes'          => $sedes,
            'tipos_contrato' => $tipos_contrato,
            'tiposActivo'    => $tiposActivo,
        ];
    }

    /* ════════════════════════════════════════════════════════
       GENERAR REPORTE con filtros combinados API + BD
    ════════════════════════════════════════════════════════ */
    static public function ctrGenerarReporte(array $post = []): array
    {
        $personal = ReporteAsignacionModel::mdlObtenerPersonalApi();

        $filtroGerencia     = trim($post['gerencia']     ?? '');
        $filtroUnidad       = trim($post['unidad']       ?? '');
        $filtroSede         = trim($post['sede']         ?? '');
        $filtroTipoContrato = trim($post['tipoContrato'] ?? '');
        $filtroNombre       = mb_strtoupper(trim($post['nombre'] ?? ''), 'UTF-8');
        $filtroDni          = trim($post['dni']          ?? '');
        $filtroJefe         = intval($post['idJefe']     ?? 0);

        $dnisMap = [];
        foreach ($personal as $p) {
            if ($filtroGerencia     && ($p['Gerencia_Laboral']     ?? '') !== $filtroGerencia)     continue;
            if ($filtroUnidad       && ($p['Unidad_Laboral']        ?? '') !== $filtroUnidad)       continue;
            if ($filtroSede         && ($p['SEDE']                  ?? '') !== $filtroSede)         continue;
            if ($filtroTipoContrato && ($p['TrabTipo_Descripcion']  ?? '') !== $filtroTipoContrato) continue;
            if ($filtroJefe         && ($p['jefeinmediato']         ?? 0)  != $filtroJefe)          continue;
            if ($filtroDni          && strpos($p['Documento'] ?? '', $filtroDni) === false)         continue;
            if ($filtroNombre) {
                $nc = mb_strtoupper(
                    ($p['Trab_Paterno'] ?? '') . ' ' . ($p['Trab_Materno'] ?? '') . ' ' . ($p['Nombres'] ?? ''),
                    'UTF-8'
                );
                if (strpos($nc, $filtroNombre) === false) continue;
            }
            $dnisMap[$p['Documento']] = $p;
        }

        $filtrosBD = [
            'dnis'         => array_keys($dnisMap),
            'idTipoActivo' => $post['idTipoActivo'] ?? '',
            'fechaDesde'   => $post['fechaDesde']   ?? '',
            'fechaHasta'   => $post['fechaHasta']   ?? '',
        ];

        $asignaciones = empty($dnisMap)
            ? []
            : ReporteAsignacionModel::mdlReporteAsignaciones($filtrosBD);

        $pivotDni    = [];
        $tiposUsados = [];

        foreach ($asignaciones as $row) {
            $dni   = $row['dni'];
            $idTip = $row['idTipoActivo'];
            $tiposUsados[$idTip] = ['descripcion' => $row['tipoActivo'], 'icono' => $row['iconoTipo']];

            if (!isset($pivotDni[$dni])) {
                $pivotDni[$dni] = [
                    'tipos'    => [],
                    'total'    => 0,
                    'estacion' => $row['nombreEstacion'],
                    'ubicacion'=> $row['ubicacion'],
                    'ambiente' => $row['ambiente'],
                    'fechaAsig'=> $row['fechaAsignacion'],
                ];
            }
            $pivotDni[$dni]['tipos'][$idTip] = intval($row['cantidad']);
            $pivotDni[$dni]['total']        += intval($row['cantidad']);
        }

        $filas = [];
        foreach ($dnisMap as $dni => $datosApi) {
            $pivot  = $pivotDni[$dni] ?? null;
            $nombre = trim(
                ($datosApi['Trab_Paterno'] ?? '') . ' ' .
                ($datosApi['Trab_Materno'] ?? '') . ' ' .
                ($datosApi['Nombres']      ?? '')
            );
            $filas[] = [
                'dni'           => $dni,
                'nombre'        => $nombre,
                'cargo'         => $datosApi['Carg_Descripcion']     ?? '',
                'gerencia'      => $datosApi['Gerencia_Laboral']     ?? '',
                'unidad'        => $datosApi['Unidad_Laboral']       ?? '',
                'sede'          => $datosApi['SEDE']                 ?? '',
                'tipoContrato'  => $datosApi['TrabTipo_Descripcion'] ?? '',
                'jefeInmediato' => $datosApi['JefeInmediato']        ?? '',
                'correo'        => $datosApi['Correo']               ?? '',
                'celular'       => $datosApi['Celular']              ?? '',
                'estacion'      => $pivot['estacion']  ?? '',
                'ubicacion'     => $pivot['ubicacion'] ?? '',
                'ambiente'      => $pivot['ambiente']  ?? '',
                'fechaAsig'     => $pivot['fechaAsig'] ?? '',
                'tipos'         => $pivot['tipos']     ?? [],
                'total'         => $pivot['total']     ?? 0,
                'tieneActivos'  => isset($pivotDni[$dni]),
            ];
        }

        usort($filas, fn($a, $b) => strcmp($a['nombre'], $b['nombre']));

        $resumenTipos  = ReporteAsignacionModel::mdlResumenPorTipo($filtrosBD);
        $todosLosTipos = ReporteAsignacionModel::mdlListarTiposActivo();

        return [
            'filas'           => $filas,
            'tiposUsados'     => $tiposUsados,
            'todosLosTipos'   => $todosLosTipos,
            'resumenTipos'    => $resumenTipos,
            'totalPersonal'   => count($dnisMap),
            'totalConActivos' => count(array_filter($filas, fn($f) => $f['tieneActivos'])),
        ];
    }

    /* ════════════════════════════════════════════════════════
       DETALLE ACTIVOS — con hijos/componentes
    ════════════════════════════════════════════════════════ */
    static public function ctrDetalleActivos(string $dni, int $idTipoActivo): array
    {
        return ReporteAsignacionModel::mdlDetalleActivosTrabajador($dni, $idTipoActivo);
    }

    /* ════════════════════════════════════════════════════════
       BUSCAR POR CÓDIGO PATRIMONIAL
    ════════════════════════════════════════════════════════ */
    static public function ctrBuscarPorCodigoPatrimonial(string $codigo): array
    {
        return ReporteAsignacionModel::mdlBuscarPorCodigoPatrimonial($codigo);
    }

    /* ════════════════════════════════════════════════════════
       OBTENER JEFES para filtro jerárquico
    ════════════════════════════════════════════════════════ */
    static public function ctrObtenerJefes(): array
    {
        $personal = ReporteAsignacionModel::mdlObtenerPersonalApi();
        $jefes    = [];
        foreach ($personal as $p) {
            if (!empty($p['JefeArea']) || !empty($p['JefeSede'])) {
                $nombre = trim(
                    ($p['Trab_Paterno'] ?? '') . ' ' .
                    ($p['Trab_Materno'] ?? '') . ' ' .
                    ($p['Nombres']      ?? '')
                );
                $jefes[$p['id_Trabajador']] = $nombre;
            }
        }
        asort($jefes);
        return $jefes;
    }
}
