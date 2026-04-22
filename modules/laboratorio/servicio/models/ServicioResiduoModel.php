<?php
class ServicioResiduoModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function obtenerTodos() {
        $sql = "SELECT srd.*, st.Nombre AS Servicio_Nombre, rc.Nombre_Item FROM laboratorio.Servicio_Residuo_Def srd JOIN laboratorio.Servicio_Tecnico st ON srd.Id_Servicio = st.Id_Servicio JOIN laboratorio.Residuo_Catalogo rc ON srd.Id_Residuo_Cat = rc.Id_Residuo_Cat WHERE srd.Activo = 1 ORDER BY st.Nombre";
        $stmt = sqlsrv_query($this->db, $sql);
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en SELECT Servicio-Residuos: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function guardar($datos) {
        $sql = "INSERT INTO laboratorio.Servicio_Residuo_Def (Id_Servicio, Id_Residuo_Cat, Cantidad_Estimada_Por_Muestra, Usuario_Creacion, Activo, Fecha_Creacion) VALUES (?, ?, ?, ?, 1, GETDATE())";
        $stmt = sqlsrv_query($this->db, $sql, array($datos['Id_Servicio'], $datos['Id_Residuo_Cat'], $datos['Cantidad_Estimada_Por_Muestra'], $_SESSION['usuario_id'] ?? 1));
        if ($stmt === false) {
            $errors = sqlsrv_errors();
            throw new Exception('Error en INSERT Servicio-Residuo: ' . ($errors[0]['message'] ?? 'Error desconocido'));
        }
    }

    public function eliminar($idServicio, $idResiduo) {
        $sql = "UPDATE laboratorio.Servicio_Residuo_Def SET Activo = 0, Fecha_Modificacion = GETDATE() WHERE Id_Servicio = ? AND Id_Residuo_Cat = ?";
        sqlsrv_query($this->db, $sql, array($idServicio, $idResiduo));
    }
}