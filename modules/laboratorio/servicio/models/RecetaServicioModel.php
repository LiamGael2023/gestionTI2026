<?php
class RecetaServicioModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerTodos() {
        $sql = "SELECT rs.*, st.Nombre AS Servicio_Nombre, r.Nombre AS Reactivo_Nombre FROM laboratorio.Receta_Servicio rs JOIN laboratorio.Servicio_Tecnico st ON rs.Id_Servicio = st.Id_Servicio JOIN laboratorio.Reactivo_Lab r ON rs.Id_Reactivo = r.Id_Reactivo WHERE rs.Activo = 1 ORDER BY st.Nombre";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Recetas: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function guardar($datos) {
        $idReactivo = $datos['Id_Reactivo'];
        $idServicio = $datos['Id_Servicio'];
        $cantidadNecesaria = $datos['Cantidad_Necesaria'];
        $usuarioId = $_SESSION['usuario_id'] ?? 1;

        // PRIMERO: Verificar si ya existe un registro (active o inactive)
        $sqlCheck = "SELECT COUNT(*) as total FROM laboratorio.Receta_Servicio 
                     WHERE Id_Reactivo = ? AND Id_Servicio = ?";
        $stmtCheck = sqlsrv_query($this->db, $sqlCheck, array($idReactivo, $idServicio));
        $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
        
        if ($rowCheck['total'] > 0) {
            // YA EXISTE: Hacer UPDATE (reactivar si estaba inactivo + actualizar cantidad)
            $sql = "UPDATE laboratorio.Receta_Servicio 
                    SET Cantidad_Necesaria = ?, Activo = 1, Fecha_Modificacion = GETDATE() 
                    WHERE Id_Reactivo = ? AND Id_Servicio = ?";
            $stmt = sqlsrv_query($this->db, $sql, array($cantidadNecesaria, $idReactivo, $idServicio));
        } else {
            // NO EXISTE: Hacer INSERT nuevo
            $sql = "INSERT INTO laboratorio.Receta_Servicio (Id_Reactivo, Id_Servicio, Cantidad_Necesaria, Usuario_Creacion, Activo, Fecha_Creacion) 
                    VALUES (?, ?, ?, ?, 1, GETDATE())";
            $stmt = sqlsrv_query($this->db, $sql, array($idReactivo, $idServicio, $cantidadNecesaria, $usuarioId));
        }
        
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en guardar Receta: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
    }

    public function eliminar($idReactivo, $idServicio) {
        $sql = "UPDATE laboratorio.Receta_Servicio SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Reactivo = ? AND Id_Servicio = ?";
        sqlsrv_query($this->db, $sql, array($idReactivo, $idServicio));
    }
}
