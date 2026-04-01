<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db.php';


header('Content-Type: application/json');

class ApiTrabajadoresTurnoDia {

    public function ejecutar() {

        $fecha = $_GET["fecha"] ?? null;

        if (!$fecha) {
            echo json_encode(["error" => "Debe enviar la fecha"]);
            exit;
        }

        if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha)) {
            echo json_encode(["error" => "Formato de fecha inválido"]);
            exit;
        }

        $conn = Conexion::conectar();

        $sql = "SELECT 
    apd.*, 
      rtrim( et.Trab_Documento) as documento
FROM BD_PERSONAL_2026.Asistencia.Tbl_Asistencia_Proc_Dias apd
INNER JOIN BD_PERSONAL_2026.Escalafon.Tbl_Trabajador et 
    ON et.Id_Trabajador = apd.Id_Trabajador
WHERE apd.esTurno = 1  
  AND apd.ProcDias_Estado = 1
  AND CONVERT(DATE, apd.ProcDias_Fecha_Ini) = ?";

        $params = array($fecha);

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            echo json_encode(["error" => "Error en la consulta"]);
            exit;
        }

        $data = [];

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data[] = $row;
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$api = new ApiTrabajadoresTurnoDia();
$api->ejecutar();