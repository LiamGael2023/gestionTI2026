<?php
class ResiduoModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerTodos() {
        $sql = "SELECT * FROM laboratorio.Residuo_Catalogo WHERE Activo = 1 ORDER BY Nombre_Item";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Residuos: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorId($id) {
        $sql = "SELECT * FROM laboratorio.Residuo_Catalogo WHERE Id_Residuo_Cat = ? AND Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($id));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Residuo: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function guardar($datos) {
        if (empty($datos['Id_Residuo_Cat'])) {
            // INSERT
            $sql = "INSERT INTO laboratorio.Residuo_Catalogo (Codigo_Item, Nombre_Item, Tipo_Principal, Subcategoria, Unidad_Referencia, Usuario_Creacion, Activo, Fecha_Creacion)
                    VALUES (?, ?, ?, ?, ?, ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
            $params = array(
                $datos['Codigo_Item'],
                $datos['Nombre_Item'],
                $datos['Tipo_Principal'],
                $datos['Subcategoria'] ?? null,
                $datos['Unidad_Referencia'],
                $_SESSION['usuario_id'] ?? 1
            );
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en INSERT Residuo: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            sqlsrv_next_result($stmt);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row['id'];
        } else {
            // UPDATE
            $sql = "UPDATE laboratorio.Residuo_Catalogo SET Codigo_Item=?, Nombre_Item=?, Tipo_Principal=?, Subcategoria=?, Unidad_Referencia=?, Fecha_Modificacion=GETDATE() WHERE Id_Residuo_Cat=?";
            $params = array(
                $datos['Codigo_Item'],
                $datos['Nombre_Item'],
                $datos['Tipo_Principal'],
                $datos['Subcategoria'] ?? null,
                $datos['Unidad_Referencia'],
                $datos['Id_Residuo_Cat']
            );
            $stmt = sqlsrv_query($this->db, $sql, $params);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error en UPDATE Residuo: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }
            return $datos['Id_Residuo_Cat'];
        }
    }

    public function eliminar($id) {
        $sql = "UPDATE laboratorio.Residuo_Catalogo SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Residuo_Cat = ?";
        sqlsrv_query($this->db, $sql, array($id));
    }

    public function obtenerDefinicionesPorServicio($idServicio) {
        $sql = "SELECT srd.*, rc.Nombre_Item FROM laboratorio.Servicio_Residuo_Def srd JOIN laboratorio.Residuo_Catalogo rc ON srd.Id_Residuo_Cat = rc.Id_Residuo_Cat WHERE srd.Id_Servicio = ? AND srd.Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($idServicio));
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function guardarDefinicion($datos) {
        $sql = "INSERT INTO laboratorio.Servicio_Residuo_Def (Id_Servicio, Id_Residuo_Cat, Cantidad_Estimada_Por_Muestra, Usuario_Creacion, Activo) VALUES (?, ?, ?, ?, 1)";
        sqlsrv_query($this->db, $sql, array($datos['Id_Servicio'], $datos['Id_Residuo_Cat'], $datos['Cantidad_Estimada_Por_Muestra'], $_SESSION['usuario_id'] ?? 1));
    }

    public function crearResiduo($datos) {
        return $this->guardar($datos);
    }

    public function actualizarResiduo($id, $datos) {
        $datos['Id_Residuo_Cat'] = $id;
        return $this->guardar($datos);
    }

    public function crearRegistroResiduo($datos) {
        $mes = intval($datos['Mes'] ?? 0);
        $anio = intval($datos['Anio'] ?? 0);
        $ubicacion = trim((string)($datos['Ubicacion'] ?? ''));
        $codigoSst = trim((string)($datos['Codigo_SST'] ?? 'SST-16'));
        $idResponsable = intval($datos['Id_Responsable'] ?? ($datos['Usuario_Creacion'] ?? 1));
        $usuarioCreacion = intval($datos['Usuario_Creacion'] ?? ($_SESSION['usuario_id'] ?? 1));
        $observacion = trim((string)($datos['Observacion'] ?? ''));
        $idsNormativas = is_array($datos['Ids_Normativas'] ?? null) ? $datos['Ids_Normativas'] : [];

        if ($mes <= 0 || $anio <= 0) {
            throw new Exception('Mes y año son obligatorios para crear el informe');
        }

        if (!sqlsrv_begin_transaction($this->db)) {
            throw new Exception('No se pudo iniciar transacción para crear informe');
        }

        try {
            $sqlBuscar = "SELECT TOP 1 Id_Registro_Res
                          FROM laboratorio.Registro_Residuos_Log
                          WHERE Mes = ? AND Anio = ? AND Activo = 1
                          ORDER BY Id_Registro_Res DESC";
            $stmtBuscar = sqlsrv_query($this->db, $sqlBuscar, array($mes, $anio));
            if ($stmtBuscar === false) {
                throw new Exception('Error al buscar cabecera del informe: ' . print_r(sqlsrv_errors(), true));
            }

            $rowExistente = sqlsrv_fetch_array($stmtBuscar, SQLSRV_FETCH_ASSOC);
            $idRegistro = intval($rowExistente['Id_Registro_Res'] ?? 0);

            if ($idRegistro > 0) {
                $sqlUpdate = "UPDATE laboratorio.Registro_Residuos_Log
                              SET Ubicacion = ?,
                                  Codigo_SST = ?,
                                  Id_Responsable = ?,
                                  Observacion = ?,
                                  Usuario_Creacion = ?,
                                  Fecha_Modificacion = GETDATE()
                              WHERE Id_Registro_Res = ?";
                $stmtUpdate = sqlsrv_query($this->db, $sqlUpdate, array(
                    $ubicacion,
                    $codigoSst,
                    $idResponsable,
                    ($observacion === '' ? null : $observacion),
                    $usuarioCreacion,
                    $idRegistro
                ));
                if ($stmtUpdate === false) {
                    throw new Exception('Error al actualizar cabecera del informe: ' . print_r(sqlsrv_errors(), true));
                }
            } else {
                $sqlInsert = "SET NOCOUNT ON;
                              INSERT INTO laboratorio.Registro_Residuos_Log
                              (Mes, Anio, Ubicacion, Codigo_SST, Id_Responsable, Observacion, Usuario_Creacion, Activo, Fecha_Creacion)
                              VALUES (?, ?, ?, ?, ?, ?, ?, 1, GETDATE());
                              SELECT CAST(SCOPE_IDENTITY() AS INT) AS id;";
                $stmtInsert = sqlsrv_query($this->db, $sqlInsert, array(
                    $mes,
                    $anio,
                    $ubicacion,
                    $codigoSst,
                    $idResponsable,
                    ($observacion === '' ? null : $observacion),
                    $usuarioCreacion
                ));
                if ($stmtInsert === false) {
                    throw new Exception('Error al crear cabecera del informe: ' . print_r(sqlsrv_errors(), true));
                }

                $rowNuevo = sqlsrv_fetch_array($stmtInsert, SQLSRV_FETCH_ASSOC);
                $idRegistro = intval($rowNuevo['id'] ?? 0);
                if ($idRegistro <= 0) {
                    throw new Exception('No se pudo obtener el Id_Registro_Res generado');
                }
            }

            $sqlDeleteLinks = "DELETE FROM laboratorio.Reporte_Normativa_Asociada WHERE Id_Registro_Res = ?";
            $stmtDeleteLinks = sqlsrv_query($this->db, $sqlDeleteLinks, array($idRegistro));
            if ($stmtDeleteLinks === false) {
                throw new Exception('Error al limpiar normativas asociadas: ' . print_r(sqlsrv_errors(), true));
            }

            $sqlInsertLink = "INSERT INTO laboratorio.Reporte_Normativa_Asociada
                              (Id_Registro_Res, Id_Normativa_SST, Usuario_Creacion, Fecha_Creacion)
                              VALUES (?, ?, ?, GETDATE())";

            $idsUnicos = [];
            foreach ($idsNormativas as $idN) {
                $idNorm = intval($idN);
                if ($idNorm > 0) {
                    $idsUnicos[$idNorm] = $idNorm;
                }
            }

            foreach ($idsUnicos as $idNormativa) {
                $stmtInsertLink = sqlsrv_query($this->db, $sqlInsertLink, array($idRegistro, $idNormativa, $usuarioCreacion));
                if ($stmtInsertLink === false) {
                    throw new Exception('Error al asociar normativa al informe: ' . print_r(sqlsrv_errors(), true));
                }
            }

            if (!sqlsrv_commit($this->db)) {
                throw new Exception('No se pudo confirmar la transacción del informe');
            }

            return $idRegistro;
        } catch (Exception $e) {
            sqlsrv_rollback($this->db);
            throw $e;
        }
    }

    public function agregarDetalleResiduo($datos) {
        $sql = "INSERT INTO laboratorio.Detalle_Residuos_Log (Id_Registro_Res, Id_Residuo_Cat, Fecha_Dia, Peso_Valor, Usuario_Creacion, Activo, Fecha_Creacion)
                VALUES (?, ?, ?, ?, ?, 1, GETDATE())";
        return sqlsrv_query($this->db, $sql, array(
            intval($datos['Id_Registro_Res']),
            intval($datos['Id_Residuo_Cat']),
            $datos['Fecha_Dia'],
            floatval($datos['Peso_Valor'] ?? 0),
            intval($datos['Usuario_Creacion'] ?? $_SESSION['usuario_id'] ?? 1)
        ));
    }

    public function obtenerInformePorId($idRegistro) {
        $idRegistro = intval($idRegistro);
        if ($idRegistro <= 0) {
            return null;
        }

        $sql = "SELECT TOP 1
                    rrl.Id_Registro_Res,
                    rrl.Mes,
                    rrl.Anio,
                    rrl.Ubicacion,
                    rrl.Codigo_SST,
                    rrl.Observacion,
                    rrl.Id_Responsable
                FROM laboratorio.Registro_Residuos_Log rrl
                WHERE rrl.Id_Registro_Res = ? AND rrl.Activo = 1";
        $stmt = sqlsrv_query($this->db, $sql, array($idRegistro));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al obtener informe: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }

        $informe = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$informe) {
            return null;
        }

        $sqlNorm = "SELECT rna.Id_Normativa_SST
                    FROM laboratorio.Reporte_Normativa_Asociada rna
                    INNER JOIN laboratorio.Normativa_SST n ON rna.Id_Normativa_SST = n.Id_Normativa_SST
                    WHERE rna.Id_Registro_Res = ? AND n.Activo = 1
                    ORDER BY rna.Id_Normativa_SST";
        $stmtNorm = sqlsrv_query($this->db, $sqlNorm, array($idRegistro));
        if ($stmtNorm === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error al obtener normativas asociadas: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }

        $idsNormativas = [];
        while ($row = sqlsrv_fetch_array($stmtNorm, SQLSRV_FETCH_ASSOC)) {
            $idNorm = intval($row['Id_Normativa_SST'] ?? 0);
            if ($idNorm > 0) {
                $idsNormativas[] = $idNorm;
            }
        }

        $informe['Ids_Normativas'] = $idsNormativas;
        return $informe;
    }

    public function actualizarInforme($idRegistro, $datos) {
        $idRegistro = intval($idRegistro);
        if ($idRegistro <= 0) {
            throw new Exception('ID de informe inválido');
        }

        $mes = intval($datos['Mes'] ?? 0);
        $anio = intval($datos['Anio'] ?? 0);
        $ubicacion = trim((string)($datos['Ubicacion'] ?? ''));
        $codigoSst = trim((string)($datos['Codigo_SST'] ?? 'SST-16'));
        $idResponsable = intval($datos['Id_Responsable'] ?? ($datos['Usuario_Creacion'] ?? 0));
        $observacion = trim((string)($datos['Observacion'] ?? ''));
        $idsNormativas = is_array($datos['Ids_Normativas'] ?? null) ? $datos['Ids_Normativas'] : [];
        $usuarioCreacion = intval($datos['Usuario_Creacion'] ?? ($_SESSION['usuario_id'] ?? 1));

        if ($mes <= 0 || $mes > 12 || $anio <= 0) {
            throw new Exception('Mes o año inválido');
        }
        if ($ubicacion === '') {
            throw new Exception('La ubicación es obligatoria');
        }
        if ($codigoSst === '') {
            $codigoSst = 'SST-16';
        }

        $idsUnicos = [];
        foreach ($idsNormativas as $idN) {
            $idNorm = intval($idN);
            if ($idNorm > 0) {
                $idsUnicos[$idNorm] = $idNorm;
            }
        }

        if (!sqlsrv_begin_transaction($this->db)) {
            throw new Exception('No se pudo iniciar transacción para actualizar informe');
        }

        try {
            $sqlUpdate = "UPDATE laboratorio.Registro_Residuos_Log
                          SET Mes = ?,
                              Anio = ?,
                              Ubicacion = ?,
                              Codigo_SST = ?,
                              Id_Responsable = ?,
                              Observacion = ?,
                              Fecha_Modificacion = GETDATE()
                          WHERE Id_Registro_Res = ? AND Activo = 1";
            $stmtUpdate = sqlsrv_query($this->db, $sqlUpdate, array(
                $mes,
                $anio,
                $ubicacion,
                $codigoSst,
                ($idResponsable > 0 ? $idResponsable : null),
                ($observacion === '' ? null : $observacion),
                $idRegistro
            ));
            if ($stmtUpdate === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error al actualizar cabecera del informe: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }

            $sqlDeleteLinks = "DELETE FROM laboratorio.Reporte_Normativa_Asociada WHERE Id_Registro_Res = ?";
            $stmtDeleteLinks = sqlsrv_query($this->db, $sqlDeleteLinks, array($idRegistro));
            if ($stmtDeleteLinks === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Error al limpiar normativas asociadas: ' . ($errors[0]['message'] ?? 'Error desconocido'));
            }

            if (!empty($idsUnicos)) {
                $sqlInsertLink = "INSERT INTO laboratorio.Reporte_Normativa_Asociada
                                  (Id_Registro_Res, Id_Normativa_SST, Usuario_Creacion, Fecha_Creacion)
                                  VALUES (?, ?, ?, GETDATE())";

                foreach ($idsUnicos as $idNormativa) {
                    $stmtInsertLink = sqlsrv_query($this->db, $sqlInsertLink, array($idRegistro, $idNormativa, $usuarioCreacion));
                    if ($stmtInsertLink === false) {
                        $errors = sqlsrv_errors();
                        throw new Exception('Error al asociar normativa al informe: ' . ($errors[0]['message'] ?? 'Error desconocido'));
                    }
                }
            }

            if (!sqlsrv_commit($this->db)) {
                throw new Exception('No se pudo confirmar la transacción del informe');
            }

            return true;
        } catch (Exception $e) {
            sqlsrv_rollback($this->db);
            throw $e;
        }
    }

    public function obtenerResiduoPorId($id) {
        return $this->obtenerPorId($id);
    }
}
