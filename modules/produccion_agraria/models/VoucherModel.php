<?php
class VoucherModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Listar todos los vouchers
     */
    public function listarVouchers($filtros = []) {
        $sql = "SELECT v.id_voucher, v.num_operacion as num_operation, v.monto_total, v.fecha_deposito, 
                       v.url_imagen,
                       CASE WHEN v.archivo_blob IS NOT NULL THEN 1 ELSE 0 END as tiene_archivo,
                       COUNT(t.id_transaccion) as total_proformas,
                       ISNULL(SUM(t.total), 0) as monto_asignado
                FROM BD_PRODUCCIONDESARROLLO.dbo.voucher_deposito v
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.transaccion t ON v.id_voucher = t.id_voucher
                WHERE v.activo = 1
                GROUP BY v.id_voucher, v.num_operacion, v.monto_total, v.fecha_deposito, 
                         v.url_imagen, v.archivo_blob
                ORDER BY v.id_voucher DESC";
        
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            error_log('Error listarVouchers: ' . print_r(sqlsrv_errors(), true));
            return [];
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Obtener voucher por ID
     */
    public function obtenerVoucher($idVoucher) {
        $sql = "SELECT id_voucher, num_operacion as num_operation, monto_total, fecha_deposito, url_imagen
                FROM BD_PRODUCCIONDESARROLLO.dbo.voucher_deposito
                WHERE id_voucher = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$idVoucher]);
        if ($stmt === false) {
            return null;
        }
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    /**
     * Subir nuevo voucher con archivo BLOB
     */
    public function guardarVoucher($data) {
        $blob = $data['archivo_blob'] ?? null;
        $blobParam = null;
        if ($blob !== null) {
            // Indicar explícitamente codificación binaria para evitar que SQL Server intente traducirlo como string UCS-2
            $blobParam = array($blob, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max'));
        }
        
        $params = [
            $data['num_operation'],
            $data['monto_total'],
            $data['fecha_deposito'],
            $data['archivo_nombre'] ?? null, // url_imagen ahora guarda el nombre original del archivo
            $blobParam
        ];
        
        $sql = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.voucher_deposito 
                (num_operacion, monto_total, fecha_deposito, url_imagen, archivo_blob)
                OUTPUT INSERTED.id_voucher
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = sqlsrv_query($this->db, $sql, $params);
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $msg = 'Error de base de datos';
            if (!empty($errors)) {
                $rawMsg = $errors[0]['message'];
                if (function_exists('mb_convert_encoding')) {
                    $msg = mb_convert_encoding($rawMsg, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
                } elseif (function_exists('utf8_encode')) {
                    $msg = utf8_encode($rawMsg);
                } else {
                    $msg = preg_replace('/[^\x20-\x7E]/', '', $rawMsg);
                }
            }
            error_log('Error guardarVoucher: ' . print_r($errors, true));
            return ['success' => false, 'message' => 'Error al guardar voucher: ' . $msg];
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return ['success' => true, 'id_voucher' => $row['id_voucher']];
    }
    
    /**
     * Obtener archivo BLOB de voucher para descarga
     */
    public function obtenerArchivoBlob($idVoucher) {
        $sql = "SELECT archivo_blob, num_operacion as num_operation, url_imagen as archivo_nombre
                FROM BD_PRODUCCIONDESARROLLO.dbo.voucher_deposito
                WHERE id_voucher = ? AND archivo_blob IS NOT NULL";
        
        $stmt = sqlsrv_query($this->db, $sql, [$idVoucher]);
        if ($stmt === false) {
            return null;
        }
        
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    /**
     * Asignar voucher a proformas
     */
    public function asignarVoucherAProformas($idVoucher, $idsTransacciones) {
        try {
            sqlsrv_begin_transaction($this->db);
            
            // Obtener monto del voucher
            $voucher = $this->obtenerVoucher($idVoucher);
            if (!$voucher) {
                throw new Exception('Voucher no encontrado');
            }
            
            $montoVoucher = $voucher['monto_total'];
            
            // Calcular total de proformas seleccionadas
            $placeholders = implode(',', array_fill(0, count($idsTransacciones), '?'));
            $sqlTotal = "SELECT SUM(total) as total FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion 
                        WHERE id_transaccion IN ($placeholders) AND estado = 'PENDIENTE'";
            $stmtTotal = sqlsrv_query($this->db, $sqlTotal, $idsTransacciones);
            $rowTotal = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC);
            $totalProformas = $rowTotal['total'] ?? 0;
            
            // No bloquear por cálculo matemático (informativo únicamente)
            /*
            if ($totalProformas > $montoVoucher) {
                throw new Exception("El monto total de proformas (S/ $totalProformas) excede el monto del voucher (S/ $montoVoucher)");
            }
            */
            
            // Actualizar cada proforma sin sobreescribir su método de pago original (VENTA/DONACION)
            $sqlUpdate = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.transaccion 
                        SET estado = 'PROCESADO', id_voucher = ?
                        WHERE id_transaccion = ? AND estado = 'PENDIENTE'";
            
            foreach ($idsTransacciones as $idTransaccion) {
                $stmtUpdate = sqlsrv_query($this->db, $sqlUpdate, [$idVoucher, $idTransaccion]);
                if ($stmtUpdate === false) {
                    throw new Exception('Error al asignar voucher a proforma #' . $idTransaccion);
                }
            }
            
            sqlsrv_commit($this->db);
            return ['success' => true, 'message' => 'Voucher asignado correctamente a ' . count($idsTransacciones) . ' proforma(s)'];
            
        } catch (Exception $e) {
            sqlsrv_rollback($this->db);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Listar proformas disponibles para asignar a un voucher
     */
    public function listarProformasDisponibles($excluirIds = []) {
        $sql = "SELECT t.id_transaccion, t.total, t.fecha_creacion, t.estado, t.num_grupo,
                       c.nombre_rs as nombre_cliente, c.dni_ruc as documento_cliente
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.cliente c ON t.id_cliente = c.id_cliente
                WHERE t.estado = 'PENDIENTE'
                AND t.tipo_op = 'VENTA'";
        
        $params = [];
        if (!empty($excluirIds)) {
            $placeholders = implode(',', array_fill(0, count($excluirIds), '?'));
            $sql .= " AND t.id_transaccion NOT IN ($placeholders)";
            $params = $excluirIds;
        }
        
        $sql .= " ORDER BY t.num_grupo ASC, t.fecha_creacion DESC";
        
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            error_log('Error listarProformasDisponibles: ' . print_r(sqlsrv_errors(), true));
            return [];
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (isset($row['fecha_creacion']) && $row['fecha_creacion'] instanceof DateTime) {
                $row['fecha_creacion'] = $row['fecha_creacion']->format('Y-m-d H:i:s');
            }
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Des-asignar voucher de todas las proformas vinculadas
     */
    public function desasignarVoucher($idVoucher) {
        try {
            sqlsrv_begin_transaction($this->db);

            // Verificar que el voucher exista y tenga proformas asignadas
            $sqlCheck = "SELECT COUNT(*) as total FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion WHERE id_voucher = ?";
            $stmtCheck = sqlsrv_query($this->db, $sqlCheck, [$idVoucher]);
            $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
            $totalProformas = $rowCheck['total'] ?? 0;

            if ($totalProformas == 0) {
                sqlsrv_commit($this->db);
                return ['success' => true, 'message' => 'El voucher no tiene proformas asignadas'];
            }

            // Quitar id_voucher y volver estado a PENDIENTE
            $sqlUpdate = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.transaccion
                        SET estado = 'PENDIENTE', id_voucher = NULL
                        WHERE id_voucher = ?";
            $stmtUpdate = sqlsrv_query($this->db, $sqlUpdate, [$idVoucher]);
            if ($stmtUpdate === false) {
                throw new Exception('Error al des-asignar voucher de proformas');
            }

            sqlsrv_commit($this->db);
            return ['success' => true, 'message' => 'Voucher des-asignado correctamente. ' . $totalProformas . ' proforma(s) volvieron a estado Pendiente.'];

        } catch (Exception $e) {
            sqlsrv_rollback($this->db);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Eliminar voucher completamente
     */
    public function eliminarVoucher($idVoucher) {
        try {
            sqlsrv_begin_transaction($this->db);

            // Primero des-asignar de cualquier proforma
            $sqlUpdate = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.transaccion
                        SET estado = 'PENDIENTE', id_voucher = NULL
                        WHERE id_voucher = ?";
            sqlsrv_query($this->db, $sqlUpdate, [$idVoucher]);

            // Luego desactivar el voucher (soft delete)
            $sqlSoft = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.voucher_deposito SET activo = 0 WHERE id_voucher = ?";
            $stmtSoft = sqlsrv_query($this->db, $sqlSoft, [$idVoucher]);
            if ($stmtSoft === false) {
                throw new Exception('Error al desactivar el voucher');
            }

            // Verificar que se desactivó
            if (sqlsrv_rows_affected($stmtSoft) === 0) {
                throw new Exception('Voucher no encontrado o ya fue desactivado');
            }

            sqlsrv_commit($this->db);
            return ['success' => true, 'message' => 'Voucher desactivado correctamente'];

        } catch (Exception $e) {
            sqlsrv_rollback($this->db);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Actualizar datos de un voucher (monto, num_operacion, fecha)
     */
    public function actualizarVoucher($idVoucher, $data) {
        try {
            $campos = [];
            $params = [];

            if (isset($data['num_operation'])) {
                $campos[] = "num_operacion = ?";
                $params[] = $data['num_operation'];
            }
            if (isset($data['monto_total'])) {
                $campos[] = "monto_total = ?";
                $params[] = $data['monto_total'];
            }
            if (isset($data['fecha_deposito'])) {
                $campos[] = "fecha_deposito = ?";
                $params[] = $data['fecha_deposito'];
            }

            if (empty($campos)) {
                return ['success' => false, 'message' => 'No hay campos para actualizar'];
            }

            $params[] = $idVoucher;
            $sql = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.voucher_deposito SET " . implode(', ', $campos) . " WHERE id_voucher = ?";
            $stmt = sqlsrv_query($this->db, $sql, $params);

            if ($stmt === false) {
                $errors = sqlsrv_errors();
                $msg = !empty($errors) ? $errors[0]['message'] : 'Error de base de datos';
                return ['success' => false, 'message' => 'Error al actualizar: ' . $msg];
            }

            return ['success' => true, 'message' => 'Voucher actualizado correctamente'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>
