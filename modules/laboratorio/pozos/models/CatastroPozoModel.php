<?php
class CatastroPozoModel {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerTodos($offset = 0, $limit = 10, $search = '') {
        $search = trim((string)$search);
        $sql = "SELECT * FROM laboratorio.Catastro_Pozo WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $like = '%' . $search . '%';
            $sql .= " AND (Id_Pozo LIKE ? OR codigo LIKE ? OR valle LIKE ? OR propietario LIKE ?)";
            $params = [$like, $like, $like, $like];
        }

        $sql .= " ORDER BY Id_Pozo OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
        $params[] = $offset;
        $params[] = $limit;

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en obtenerTodos: ' . print_r(sqlsrv_errors(), true));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function contarTodos($search = '') {
        $search = trim((string)$search);
        $sql = "SELECT COUNT(*) AS total FROM laboratorio.Catastro_Pozo WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $like = '%' . $search . '%';
            $sql .= " AND (Id_Pozo LIKE ? OR codigo LIKE ? OR valle LIKE ? OR propietario LIKE ?)";
            $params = [$like, $like, $like, $like];
        }

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en contarTodos: ' . print_r(sqlsrv_errors(), true));
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return intval($row['total'] ?? 0);
    }

    public function obtenerPorId($id_pozo) {
        $sql = "SELECT * FROM laboratorio.Catastro_Pozo WHERE Id_Pozo = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id_pozo]);
        if ($stmt === false) {
            throw new Exception('Error en obtenerPorId: ' . print_r(sqlsrv_errors(), true));
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function obtenerPorValle($valle) {
        $sql = "SELECT * FROM laboratorio.Catastro_Pozo WHERE valle = ? ORDER BY Id_Pozo";
        $stmt = sqlsrv_query($this->db, $sql, [$valle]);
        if ($stmt === false) {
            throw new Exception('Error en obtenerPorValle: ' . print_r(sqlsrv_errors(), true));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerValles() {
        $sql = "SELECT DISTINCT valle FROM laboratorio.Catastro_Pozo WHERE valle IS NOT NULL AND valle != '' ORDER BY valle";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            throw new Exception('Error en obtenerValles: ' . print_r(sqlsrv_errors(), true));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = trim((string)$row['valle']);
        }
        return $result;
    }

    public function obtenerParaGeoportal($valle = '') {
        $sql = "SELECT Id_Pozo, codigo, valle, coord_este, coord_norte, zona, geom,
                       ubicacion, propietario, tipopozo
                FROM laboratorio.Catastro_Pozo
                WHERE 1=1";
        $params = [];
        if ($valle !== '') {
            $sql .= " AND valle = ?";
            $params[] = $valle;
        }
        $sql .= " ORDER BY Id_Pozo";
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en obtenerParaGeoportal: ' . print_r(sqlsrv_errors(), true));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    // Sincronizacion desde PostgreSQL
    public function sincronizarDesdePostgreSQL($pdoPg) {
        try {
            $sqlPg = "SELECT * FROM " . PG_SCHEMA . ".pozos_catastro ORDER BY id_pozo";
            $stmtPg = $pdoPg->query($sqlPg);
            $rows = $stmtPg->fetchAll();

            $insertados   = 0;
            $actualizados = 0;
            $sin_cambios  = 0;

            foreach ($rows as $row) {
                $idPozo    = strtoupper(trim((string)($row['id_pozo']        ?? '')));
                $codPech   = trim((string)($row['codigopech']    ?? ''));
                $codigo    = trim((string)($row['codigo']        ?? ''));
                $valle     = trim((string)($row['valle']         ?? ''));
                $geom      = trim((string)($row['geom']          ?? ''));
                $coordEste = floatval($row['cooreste']    ?? 0);
                $coordNorte= floatval($row['coornorte']   ?? 0);
                $zona      = floatval($row['zona']         ?? 0);
                $cota      = floatval($row['cota']         ?? 0);
                $dep       = trim((string)($row['departamento']  ?? ''));
                $prov      = trim((string)($row['provincia']     ?? ''));
                $dist      = trim((string)($row['distrito']      ?? ''));
                $ubicacion = trim((string)($row['ubicacion']     ?? ''));
                $aaa       = trim((string)($row['aaa']           ?? ''));
                $ala       = trim((string)($row['ala']           ?? ''));
                $uh        = trim((string)($row['uh']            ?? ''));
                $fechaInv  = trim((string)($row['fechainventario'] ?? ''));
                $prop      = trim((string)($row['propietario']    ?? ''));
                $tipoPozo  = trim((string)($row['tipopozo']       ?? ''));
                $pr        = floatval($row['pr']            ?? 0);
                $fotoPozo  = trim((string)($row['foto_pozo']      ?? ''));
                // Truncar a 255 chars si la columna aún es VARCHAR(255)
                if (strlen($fotoPozo) > 255) {
                    $fotoPozo = substr($fotoPozo, 0, 255);
                }

                if ($idPozo === '') continue;

                $existe = $this->obtenerPorId($idPozo);

                if ($existe) {
                    $cambios = $this->detectarCambiosPozo($existe, [
                        'codigopech' => $codPech, 'codigo' => $codigo, 'valle' => $valle,
                        'geom' => $geom, 'coord_este' => $coordEste, 'coord_norte' => $coordNorte,
                        'zona' => $zona, 'cota' => $cota, 'departamento' => $dep,
                        'provincia' => $prov, 'distrito' => $dist, 'ubicacion' => $ubicacion,
                        'aaa' => $aaa, 'ala' => $ala, 'uh' => $uh,
                        'fechainventario' => $fechaInv !== '' ? $fechaInv : null,
                        'propietario' => $prop, 'tipopozo' => $tipoPozo, 'pr' => $pr,
                        'foto_pozo' => $fotoPozo
                    ]);

                    if ($cambios) {
                        $sqlUp = "UPDATE laboratorio.Catastro_Pozo
                                  SET codigopech=?, codigo=?, valle=?, geom=?, coord_este=?, coord_norte=?,
                                      zona=?, cota=?, departamento=?, provincia=?, distrito=?,
                                      ubicacion=?, aaa=?, ala=?, uh=?, fechainventario=?,
                                      propietario=?, tipopozo=?, pr=?, foto_pozo=?,
                                      Fecha_Sincronizacion=GETDATE()
                                  WHERE Id_Pozo=?";
                        $params = [$codPech, $codigo, $valle, $geom, $coordEste, $coordNorte,
                                   $zona, $cota, $dep, $prov, $dist, $ubicacion, $aaa, $ala, $uh,
                                   $fechaInv !== '' ? $fechaInv : null, $prop, $tipoPozo, $pr,
                                   $fotoPozo, $idPozo];
                        $stmt = sqlsrv_query($this->db, $sqlUp, $params);
                        if ($stmt === false) {
                            throw new Exception('Error UPDATE Catastro_Pozo: ' . print_r(sqlsrv_errors(), true));
                        }
                        $actualizados++;
                    } else {
                        $sin_cambios++;
                    }
                } else {
                    $sqlIns = "INSERT INTO laboratorio.Catastro_Pozo
                               (Id_Pozo, codigopech, codigo, valle, geom, coord_este, coord_norte,
                                zona, cota, departamento, provincia, distrito, ubicacion, aaa, ala, uh,
                                fechainventario, propietario, tipopozo, pr, foto_pozo, Fecha_Sincronizacion)
                               VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,GETDATE())";
                    $params = [$idPozo, $codPech, $codigo, $valle, $geom, $coordEste, $coordNorte,
                               $zona, $cota, $dep, $prov, $dist, $ubicacion, $aaa, $ala, $uh,
                               $fechaInv !== '' ? $fechaInv : null, $prop, $tipoPozo, $pr,
                               $fotoPozo];
                    $stmt = sqlsrv_query($this->db, $sqlIns, $params);
                    if ($stmt === false) {
                        throw new Exception('Error INSERT Catastro_Pozo: ' . print_r(sqlsrv_errors(), true));
                    }
                    $insertados++;
                }
            }

            return [
                'insertados'   => $insertados,
                'actualizados' => $actualizados,
                'sin_cambios'  => $sin_cambios,
                'total_pg'     => count($rows)
            ];
        } catch (Exception $e) {
            throw new Exception('Error en sincronizacion: ' . $e->getMessage());
        }
    }

    public function obtenerResultadosInsituPorPozo($id_pozo, $anio_desde = null) {
        $sql = "SELECT 
                    ra.Fecha_Creacion AS Fecha_Medicion,
                    ra.Valor_Hallado AS Valor_Medicion,
                    pa.Id_Parametro, pa.Nombre AS Parametro, pa.Unidad_Medida, pa.Categoria,
                    sa.Id_Solicitud_Analisis,
                    m.Id_Muestra
                FROM laboratorio.Resultado_Analisis ra
                INNER JOIN laboratorio.Solicitud_Analisis sa ON ra.Id_Solicitud_Analisis = sa.Id_Solicitud_Analisis AND sa.Activo = 1
                INNER JOIN laboratorio.Parametro_Analisis pa ON ra.Id_Parametro = pa.Id_Parametro AND pa.Activo = 1
                INNER JOIN laboratorio.Muestra_Lab m ON sa.Id_Muestra = m.Id_Muestra AND m.Activo = 1
                WHERE m.Id_Pozo = ? AND ra.Activo = 1";
        $params = [$id_pozo];

        if ($anio_desde) {
            $sql .= " AND YEAR(ISNULL(ra.Fecha_Creacion, GETDATE())) >= ?";
            $params[] = intval($anio_desde);
        }

        $sql .= " ORDER BY ra.Fecha_Creacion DESC, pa.Categoria, pa.Nombre";

        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error en obtenerResultadosInsituPorPozo: ' . print_r(sqlsrv_errors(), true));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    private function detectarCambiosPozo($existente, $nuevo) {
        $campos = [
            'codigopech', 'codigo', 'valle', 'geom',
            'coord_este', 'coord_norte', 'zona', 'cota',
            'departamento', 'provincia', 'distrito', 'ubicacion',
            'aaa', 'ala', 'uh', 'propietario', 'tipopozo',
            'fechainventario', 'pr', 'foto_pozo'
        ];

        foreach ($campos as $campo) {
            $valExistente = $existente[$campo] ?? null;
            $valNuevo     = $nuevo[$campo] ?? null;

            if ($valExistente instanceof DateTime) {
                $valExistente = $valExistente->format('Y-m-d');
            }
            if ($valNuevo instanceof DateTime) {
                $valNuevo = $valNuevo->format('Y-m-d');
            }

            $valExistente = is_string($valExistente) ? trim($valExistente) : $valExistente;
            $valNuevo     = is_string($valNuevo)     ? trim($valNuevo)     : $valNuevo;

            if (is_numeric($valExistente) && is_numeric($valNuevo)) {
                if (floatval($valExistente) !== floatval($valNuevo)) return true;
            } elseif (is_bool($valExistente) && is_bool($valNuevo)) {
                if ($valExistente !== $valNuevo) return true;
            } else {
                if ((string)$valExistente !== (string)$valNuevo) return true;
            }
        }

        return false;
    }
}
