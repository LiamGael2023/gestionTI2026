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
        $sql = "SELECT v.id_voucher, v.num_operation, v.monto_total, v.fecha_deposito, 
                       v.url_imagen, v.fecha_registro,
                       COUNT(t.id_transaccion) as total_proformas,
                       ISNULL(SUM(t.total), 0) as monto_asignado
                FROM BD_PRODUCCIONDESARROLLO.dbo.voucher_deposito v
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.transaccion t ON v.id_voucher = t.id_voucher
                GROUP BY v.id_voucher, v.num_operation, v.monto_total, v.fecha_deposito, 
                         v.url_imagen, v.fecha_registro
                ORDER BY v.fecha_registro DESC";
        
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
        $sql = "SELECT id_voucher, num_operation, monto_total, fecha_deposito, url_imagen
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
        // Preparar parámetros incluyendo archivo BLOB
        $params = [
            $data['num_operation'],
            $data['monto_total'],
            $data['fecha_deposito'],
            null // url_imagen - ya no se usa
        ];
        
        // Agregar archivo BLOB si existe
        if (!empty($data['archivo_blob'])) {
            // Convertir base64 a binario si viene codificado
            if (is_string($data['archivo_blob']) && base64_decode($data['archivo_blob'], true)) {
                $params[] = base64_decode($data['archivo_blob']);
            } else {
                $params[] = $data['archivo_blob'];
            }
            
            $sql = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.voucher_deposito 
                    (num_operation, monto_total, fecha_deposito, url_imagen, archivo_blob, fecha_registro)
                    OUTPUT INSERTED.id_voucher
                    VALUES (?, ?, ?, ?, CONVERT(VARBINARY(MAX), ?), GETDATE())";
        } else {
            $params[] = null;
            $sql = "INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.voucher_deposito 
                    (num_operation, monto_total, fecha_deposito, url_imagen, archivo_blob, fecha_registro)
                    OUTPUT INSERTED.id_voucher
                    VALUES (?, ?, ?, ?, NULL, GETDATE())";
        }
        
        $stmt = sqlsrv_query($this->db, $sql, $params);
        
        if ($stmt === false) {
            error_log('Error guardarVoucher: ' . print_r(sqlsrv_errors(), true));
            return ['success' => false, 'message' => 'Error al guardar voucher'];
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return ['success' => true, 'id_voucher' => $row['id_voucher']];
    }
    
    /**
     * Obtener archivo BLOB de voucher para descarga
     */
    public function obtenerArchivoBlob($idVoucher) {
        $sql = "SELECT archivo_blob, num_operation 
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
            
            if ($totalProformas > $montoVoucher) {
                throw new Exception("El monto total de proformas (S/ $totalProformas) excede el monto del voucher (S/ $montoVoucher)");
            }
            
            // Actualizar cada proforma
            $sqlUpdate = "UPDATE BD_PRODUCCIONDESARROLLO.dbo.transaccion 
                        SET estado = 'PROCESADO', id_voucher = ?, metodo_pago = 'VOUCHER'
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
        $sql = "SELECT t.id_transaccion, t.total, t.fecha_creacion, t.estado,
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
        
        $sql .= " ORDER BY t.fecha_creacion DESC";
        
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if ($stmt === false) {
            error_log('Error listarProformasDisponibles: ' . print_r(sqlsrv_errors(), true));
            return [];
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Verificar si un voucher ya está asignado a proformas
     */
    public function getProformasPorVoucher($idVoucher) {
        $sql = "SELECT t.id_transaccion, t.total, c.nombre_rs as nombre_cliente
                FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion t
                LEFT JOIN BD_PRODUCCIONDESARROLLO.dbo.cliente c ON t.id_cliente = c.id_cliente
                WHERE t.id_voucher = ?";
        
        $stmt = sqlsrv_query($this->db, $sql, [$idVoucher]);
        if ($stmt === false) {
            return [];
        }
        
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }
}
?>
