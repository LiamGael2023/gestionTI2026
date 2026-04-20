<?php

class CertificadosModel {

    private $db;

    public function __construct($db){
        $this->db = $db;
    }

    /* =============================
       DASHBOARD
    ============================= */
    public function total(){
        $sql = "SELECT COUNT(*) as total
                FROM certificados.CertificadosDigitales";
        $stmt = sqlsrv_query($this->db, $sql);
        if($stmt === false) die(print_r(sqlsrv_errors(), true));
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['total'];
    }

    public function activos(){
        $sql = "SELECT COUNT(*) as total
                FROM certificados.CertificadosDigitales
                WHERE estado='activo'";
        $stmt = sqlsrv_query($this->db, $sql);
        if($stmt === false) die(print_r(sqlsrv_errors(), true));
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['total'];
    }

    public function vencidos(){
        $sql = "SELECT COUNT(*) as total
                FROM certificados.CertificadosDigitales
                WHERE estado='vencido'";
        $stmt = sqlsrv_query($this->db, $sql);
        if($stmt === false) die(print_r(sqlsrv_errors(), true));
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['total'];
    }

    public function porVencer(){
        $sql = "SELECT COUNT(*) as total
                FROM certificados.CertificadosDigitales
                WHERE fecha_vencimiento BETWEEN GETDATE() AND DATEADD(DAY,20,GETDATE())";
        $stmt = sqlsrv_query($this->db, $sql);
        if($stmt === false) die(print_r(sqlsrv_errors(), true));
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['total'];
    }

    /* =============================
       LISTAR CERTIFICADOS
    ============================= */
   public function listar($buscar = null, $fecha_inicio = null, $fecha_fin = null, $tipo_tramite = null){
    $sql = "SELECT 
                c.id_certificado,
                p.nombres,
                p.apellidos,
                p.dni,
                p.gerencia_laboral,
                CONVERT(VARCHAR(19), c.fecha_emision, 120) AS fecha_emision,
                CONVERT(VARCHAR(19), c.fecha_vencimiento, 120) AS fecha_vencimiento,
                c.estado,
                c.tipo_certificado,
                c.tipo_tramite,
                u.nombres AS usuario_nombre,
                u.apellidos AS usuario_apellidos
            FROM certificados.CertificadosDigitales c
            INNER JOIN certificados.Personas p
                ON p.id_persona = c.id_persona
            LEFT JOIN comun.Usuarios u
                ON u.id_usuario = c.id_usuario_registro";

    $conditions = [];
    $params = [];

    if($buscar){
        $conditions[] = "(p.nombres LIKE ? OR p.apellidos LIKE ? OR p.dni LIKE ?)";
        $params[] = "%$buscar%";
        $params[] = "%$buscar%";
        $params[] = "%$buscar%";
    }

    if($fecha_inicio){
        $conditions[] = "c.fecha_emision >= ?";
        $params[] = $fecha_inicio;
    }

    if($fecha_fin){
        $conditions[] = "c.fecha_emision <= ?";
        $params[] = $fecha_fin;
    }

    // 🔥 FILTRO POR TIPO TRÁMITE
    if($tipo_tramite){
        $conditions[] = "c.tipo_tramite = ?";
        $params[] = $tipo_tramite;
    }

    if(count($conditions) > 0){
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY c.fecha_vencimiento DESC";

    $stmt = sqlsrv_query($this->db, $sql, $params);
    if($stmt === false) die(print_r(sqlsrv_errors(), true));

    $result = [];
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $result[] = $row;
    }
    return $result;
}

    public function certificadosPorVencer(){
    $sql = "SELECT 
                p.dni, 
                p.nombres, 
                p.apellidos, 
                p.gerencia_laboral, 
                CONVERT(VARCHAR(19), c.fecha_vencimiento, 120) AS fecha_vencimiento
            FROM certificados.CertificadosDigitales c
            JOIN certificados.Personas p ON p.id_persona = c.id_persona
            WHERE c.fecha_vencimiento BETWEEN GETDATE() AND DATEADD(DAY, 20, GETDATE())
              AND c.estado_tramite = 'no tramitado'
            ORDER BY c.fecha_vencimiento ASC";

    $stmt = sqlsrv_query($this->db, $sql);
    if($stmt === false) die(print_r(sqlsrv_errors(), true));

    $result = [];
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $result[] = $row;
    }
    return $result;
}
    public function proximosVencer(){
        $sql = "SELECT 
                    p.nombres,
                    p.apellidos,
                    p.dni,
                    CONVERT(VARCHAR(19), c.fecha_vencimiento, 120) AS fecha_vencimiento
                FROM certificados.CertificadosDigitales c
                INNER JOIN certificados.Personas p ON p.id_persona=c.id_persona
                WHERE c.fecha_vencimiento BETWEEN GETDATE() AND DATEADD(DAY,15,GETDATE())
                ORDER BY c.fecha_vencimiento";

        $stmt = sqlsrv_query($this->db, $sql);
        if($stmt === false) die(print_r(sqlsrv_errors(), true));

        $result = [];
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
            $result[] = $row;
        }
        return $result;
    }
public function listarBackups1($id_certificado){
    $sql = "SELECT 
                id_backup,
                codigo_reloj,
                identificador,
                ruta_archivo,
                CONVERT(VARCHAR(19), fecha_backup, 120) AS fecha_backup,
                id_certificado,
                id_usuario_backup
            FROM certificados.BackupsCertificados
            WHERE id_certificado = ?
            ORDER BY fecha_backup DESC";

    $params = [$id_certificado];
    $stmt = sqlsrv_query($this->db, $sql, $params);

    if($stmt === false) die(print_r(sqlsrv_errors(), true));

    $result = [];
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $result[] = $row;
    }

    return $result;
}
public function obtenerPorId1($id){

    $sql = "SELECT 
                c.id_certificado,
                c.codigo_reloj,
                c.fecha_emision,
                c.fecha_vencimiento,
                c.duracion_anios,
                c.estado,
                c.tipo_certificado,
                c.evidencia,
                c.fecha_creacion,
                p.dni,
                p.nombres,
                p.apellidos,
                p.correo,
                p.telefono,
                p.gerencia_laboral
            FROM certificados.CertificadosDigitales c
            INNER JOIN certificados.Personas p 
                ON p.id_persona = c.id_persona
            WHERE c.id_certificado = ?";

    $params = [$id];

    $stmt = sqlsrv_query($this->db,$sql,$params);

    if($stmt === false){
        die(print_r(sqlsrv_errors(),true));
    }

    return sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC);
}
public function obtenerBackups2($id){

    $sql = "SELECT *
            FROM certificados.BackupsCertificados
            WHERE id_certificado = ?";

    $params = [$id];

    $stmt = sqlsrv_query($this->db,$sql,$params);

    $data = [];

    while($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)){
        $data[] = $row;
    }

    return $data;
}
    public function obtenerPorId($id){
        $sql = "SELECT 
                    c.*,
                    p.dni,
                    p.nombres,
                    p.apellidos,
                    p.gerencia_laboral,
                    CONVERT(VARCHAR(19), c.fecha_emision, 120) AS fecha_emision,
                    CONVERT(VARCHAR(19), c.fecha_vencimiento, 120) AS fecha_vencimiento
                FROM certificados.CertificadosDigitales c
                INNER JOIN certificados.Personas p ON p.id_persona = c.id_persona
                WHERE c.id_certificado = ?";
        $params = [$id];
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if($stmt === false) die(print_r(sqlsrv_errors(), true));
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function obtenerFilaTablaPrincipal($id){
        $sql = "SELECT
                    c.id_certificado,
                    p.nombres,
                    p.apellidos,
                    p.dni,
                    p.gerencia_laboral,
                    CONVERT(VARCHAR(19), c.fecha_emision, 120) AS fecha_emision,
                    CONVERT(VARCHAR(19), c.fecha_vencimiento, 120) AS fecha_vencimiento,
                    c.estado,
                    c.tipo_certificado,
                    c.tipo_tramite,
                    u.nombres AS usuario_nombre,
                    u.apellidos AS usuario_apellidos
                FROM certificados.CertificadosDigitales c
                INNER JOIN certificados.Personas p
                    ON p.id_persona = c.id_persona
                LEFT JOIN comun.Usuarios u
                    ON u.id_usuario = c.id_usuario_registro
                WHERE c.id_certificado = ?";

        $params = [$id];
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if($stmt === false) die(print_r(sqlsrv_errors(), true));

        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    public function eliminar($id){
        $sql = "DELETE FROM certificados.CertificadosDigitales WHERE id_certificado = ?";
        $params = [$id];
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if($stmt === false) die(print_r(sqlsrv_errors(), true));
        return true;
    }

    /* =============================
       BACKUPS
    ============================= */
    public function listarBackups($id_certificado){
        $sql = "SELECT *, CONVERT(VARCHAR(19), fecha_backup, 120) AS fecha_backup
                FROM certificados.BackupsCertificados
                WHERE id_certificado = ?
                ORDER BY fecha_backup DESC";
        $params = [$id_certificado];
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if($stmt === false) die(print_r(sqlsrv_errors(), true));

        $result = [];
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
            $result[] = $row;
        }
        return $result;
    }

     // Listar certificados sin backup
    public function listarSinBackup(){
        $sql = "SELECT cd.id_certificado, p.nombres, p.apellidos, p.dni, cd.tipo_certificado,cd.fecha_emision
                FROM certificados.CertificadosDigitales cd
                INNER JOIN certificados.Personas p ON cd.id_persona = p.id_persona
                WHERE cd.id_certificado NOT IN (SELECT id_certificado FROM certificados.BackupsCertificados)";
        $stmt = sqlsrv_query($this->db, $sql);
        $result = [];
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
            if ($row['fecha_emision'] instanceof DateTime) {
        $row['fecha_emision'] = $row['fecha_emision']->format('d/m/Y');
    }
    $result[] = $row;
        }
        
        return $result;
    }
// Crear backup
    public function crearBackup1($codigo_reloj, $identificador, $ruta_archivo, $id_certificado, $id_usuario){
        $sql = "INSERT INTO certificados.BackupsCertificados
                (codigo_reloj, identificador, ruta_archivo, fecha_backup, id_certificado, id_usuario_backup)
                VALUES (?, ?, ?, GETDATE(), ?, ?)";
        $params = [$codigo_reloj, $identificador, $ruta_archivo, $id_certificado, $id_usuario];
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if($stmt === false){
            // Opcional: log de errores
            error_log(print_r(sqlsrv_errors(), true));
            return false;
        }
        return true;
    }
    /* =============================
       PERSONAS
    ============================= */
    public function listarPersonas() {
        $sql = "SELECT * FROM certificados.Personas ORDER BY apellidos, nombres";
        $stmt = sqlsrv_query($this->db, $sql);
        if($stmt === false) die(print_r(sqlsrv_errors(), true));

        $result = [];
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerCodigoReloj($id_persona){
        $sql = "SELECT codigo_reloj FROM certificados.Personas WHERE id_persona = ?";
        $params = [$id_persona];
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if($stmt === false) die(print_r(sqlsrv_errors(), true));
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row ? $row['codigo_reloj'] : null;
    }

    public function obtenerPersonaPorId1($id){
        $sql = "SELECT * FROM certificados.Personas WHERE id_persona = ?";
        $params = [$id];
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if($stmt === false) die(print_r(sqlsrv_errors(), true));
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    /* =============================
       CREAR CERTIFICADO
    ============================= */
    public function crearCertificado(
    $codigo_reloj,
    $fecha_emision,
    $duracion,
    $fecha_vencimiento,
    $estado,
    $id_persona,
    $id_usuario_registro,
    $archivo_nombre,
    $tipo_certificado,
    $tipo_tramite, 
    $estado_tramite
){
    // Validar estado y tipo_certificado según los CHECK constraints
    $estados_validos = ['activo','vencido','revocado','suspendido'];
    $tipos_validos = ['CLOUD','TOKEN_HARDWARE','TOKEN_SOFTWARE'];

    if(!in_array(strtolower($estado), $estados_validos)){
        throw new InvalidArgumentException("Estado inválido: $estado");
    }

    if(!in_array(strtoupper($tipo_certificado), $tipos_validos)){
        throw new InvalidArgumentException("Tipo de certificado inválido: $tipo_certificado");
    }

    // Convertir fechas a string si vienen como DateTime
    if($fecha_emision instanceof DateTime){
        $fecha_emision = $fecha_emision->format('Y-m-d H:i:s');
    }
    if($fecha_vencimiento instanceof DateTime){
        $fecha_vencimiento = $fecha_vencimiento->format('Y-m-d H:i:s');
    }

    $sql = "INSERT INTO certificados.CertificadosDigitales
            (codigo_reloj, fecha_emision, duracion_anios, fecha_vencimiento, estado, id_persona, id_usuario_registro, fecha_creacion, evidencia, tipo_certificado,tipo_tramite, estado_tramite)
            OUTPUT INSERTED.id_certificado
            VALUES (?, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?,?,?)";

    $params = [
        $codigo_reloj,
        $fecha_emision,
        $duracion,
        $fecha_vencimiento,
        strtolower($estado),
        $id_persona,
        $id_usuario_registro,
        $archivo_nombre,
        strtoupper($tipo_certificado),
        strtoupper($tipo_tramite), 
        strtoupper($estado_tramite)
    ];

    $stmt = sqlsrv_query($this->db, $sql, $params);
    if($stmt === false){
        $errores = sqlsrv_errors(SQLSRV_ERR_ERRORS);
        $mensajes = [];

        if(is_array($errores)){
            foreach($errores as $error){
                $mensajes[] = trim(($error['SQLSTATE'] ?? 'N/A') . ' - ' . ($error['message'] ?? 'Error desconocido'));
            }
        }

        throw new RuntimeException('Error al crear certificado: ' . implode(' | ', $mensajes));
    }

    $identityRow = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if(!$identityRow || empty($identityRow['id_certificado'])){
        throw new RuntimeException('Certificado creado, pero el ID generado no es valido.');
    }

    return (int)$identityRow['id_certificado'];
}



 public function listar2(){

        $sql = "SELECT * FROM certificados.Personas ORDER BY id_persona DESC";

        $stmt = sqlsrv_query($this->db,$sql);

        $data = [];

        while($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)){
            $data[] = $row;
        }

        return $data;
    }

    public function total1(){

        $sql = "SELECT COUNT(*) total FROM certificados.Personas";

        $stmt = sqlsrv_query($this->db,$sql);

        return sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)['total'];
    }

    public function obtener($id){

        $sql = "SELECT * FROM certificados.Personas WHERE id_persona = ?";

        $params = [$id];

        $stmt = sqlsrv_query($this->db,$sql,$params);

        return sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC);
    }

    public function guardar($data){

        $sql = "INSERT INTO certificados.Personas
        (dni,nombres,apellidos,correo,telefono,gerencia_laboral,codigo_reloj)
        VALUES (?,?,?,?,?,?,?)";

        $params = [
            $data['dni'],
            $data['nombres'],
            $data['apellidos'],
            $data['correo'],
            $data['telefono'],
            $data['gerencia_laboral'],
            $data['codigo_reloj']
        ];

        return sqlsrv_query($this->db,$sql,$params);
    }

    public function actualizar($data){

        $sql = "UPDATE certificados.Personas SET
                dni=?,
                nombres=?,
                apellidos=?,
                correo=?,
                telefono=?,
                gerencia_laboral=?,
                codigo_reloj=?
                WHERE id_persona=?";

        $params = [
            $data['dni'],
            $data['nombres'],
            $data['apellidos'],
            $data['correo'],
            $data['telefono'],
            $data['gerencia_laboral'],
            $data['codigo_reloj'],
            $data['id_persona']
        ];

        return sqlsrv_query($this->db,$sql,$params);
    }

    public function eliminar1($id){

        $sql = "DELETE FROM certificados.Personas WHERE id_persona=?";

        $params = [$id];

        return sqlsrv_query($this->db,$sql,$params);
    }
    public function buscar1($texto = "")
{

    $sql = "SELECT * 
            FROM certificados.Personas
            WHERE dni LIKE ? 
            OR nombres LIKE ?
            OR apellidos LIKE ?
            OR gerencia_laboral LIKE ?
            ORDER BY id_persona DESC";

    $buscar1 = "%".$texto."%";

    $params = [$buscar1,$buscar1,$buscar1,$buscar1];

    $stmt = sqlsrv_query($this->db,$sql,$params);

    $data = [];

    while($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)){
        $data[] = $row;
    }

    return $data;
}
public function contarGerencias(){

$sql = "SELECT gerencia_laboral, COUNT(*) total
        FROM certificados.Personas
        GROUP BY gerencia_laboral
        ORDER BY total DESC";

$stmt = sqlsrv_query($this->db,$sql);

$data = [];

while($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)){
$data[] = $row;
}

return $data;

}

public function obtenerCertificadosPersona($id_persona)
{

$sql = "SELECT *
        FROM certificados.CertificadosDigitales
        WHERE id_persona = ?
        ORDER BY fecha_vencimiento DESC";

$params = [$id_persona];

$stmt = sqlsrv_query($this->db,$sql,$params);

$data = [];

while($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)){
    $data[] = $row;
}

return $data;

}
public function obtenerPersona($id)
{

$sql = "SELECT *
        FROM certificados.Personas
        WHERE id_persona = ?";

$params = [$id];

$stmt = sqlsrv_query($this->db,$sql,$params);

return sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC);

}

public function obtenerCertificadosPorVencer2($tipo = 'PERSONAL')
{

$sql = "

SELECT 
c.id_certificado,
p.dni,
p.nombres,
p.apellidos,
p.correo,
p.telefono,
p.gerencia_laboral,
c.tipo_certificado,
c.tipo_tramite,
c.fecha_vencimiento

FROM certificados.Personas p

INNER JOIN certificados.CertificadosDigitales c
ON p.id_persona = c.id_persona

WHERE c.fecha_vencimiento 
BETWEEN GETDATE() AND DATEADD(day,10,GETDATE())
AND c.estado_tramite = 'NO TRAMITADO'
AND c.tipo_tramite = ?

ORDER BY c.fecha_vencimiento ASC

";

$params = [$tipo];

$stmt = sqlsrv_query($this->db, $sql, $params);

$data = [];

while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
    $data[] = $row;
}

return $data;

}

public function enviar(
        string $region,
        string $correo,
        string $mensaje,
        array $excel,
        array $recibo
    ): string {

        $baseDir = __DIR__ . '/../tramites';
        if (!file_exists($baseDir)) {
            mkdir($baseDir, 0777, true);
        }

        $timestamp = date('Ymd_His');

        $excelPath  = "$baseDir/{$timestamp}_" . basename($excel['name']);
        $reciboPath = "$baseDir/{$timestamp}_" . basename($recibo['name']);

        move_uploaded_file($excel['tmp_name'], $excelPath);
        move_uploaded_file($recibo['tmp_name'], $reciboPath);

        /* ZIP */
        $zipName = "tramite_certificados_$timestamp.zip";
        $zipPath = "$baseDir/$zipName";

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addFile($excelPath,  'excel/' . basename($excel['name']));
        $zip->addFile($reciboPath, 'recibo/' . basename($recibo['name']));
        $zip->close();

        /* CORREO */
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'franklinuquiche@gmail.com';
        $mail->Password   = 'vfanmcargdsomgzq';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->isHTML(true);


        $mail->setFrom($mail->Username, 'Sistema de Certificados');
        $mail->addAddress($correo);
        $mail->addAttachment($excelPath, basename($excel['name']));
$mail->addAttachment($reciboPath, basename($recibo['name']));
        $mail->Subject = "Remisión de Documentación – Trámite de Certificados Digitales | Región $region";

        $mail->Body = "
<div style='font-family:Arial, Helvetica, sans-serif; background-color:#f4f6f9; padding:30px;'>
    <div style='max-width:650px; margin:auto; background:#ffffff; border-radius:8px; padding:35px; box-shadow:0 2px 8px rgba(0,0,0,0.05);'>
        
        <div style='text-align:center; margin-bottom:25px;'>
            <h2 style='margin:0; color:#1f3c88;'>CONSUMO ELECTRÓNICO</h2>
            <p style='margin:6px 0 0 0; font-size:14px; color:#555;'>
                Sistema de Gestión de Certificados Digitales<br>
                Proyecto Especial Chavimochic
            </p>
        </div>

        <div style='font-size:14px; color:#333; line-height:1.6;'>
            <p>
                <strong>Región:</strong> $region<br>
                <strong>Fecha:</strong> " . date('d/m/Y') . "
            </p>

            <p>
                A la Sede Superior correspondiente:
            </p>

            <p>
                Tengo el agrado de dirigirme a usted para saludarle cordialmente y, a la vez,
                remitir la información relacionada al trámite de Certificados Digitales
                gestionado por esta dependencia.
            </p>

            <div style='background:#f8f9fa; padding:18px; border-left:4px solid #1f3c88; margin:20px 0;'>
                <strong>Detalle del Trámite:</strong><br><br>
                $mensaje
            </div>

            <p>
                Se adjunta al presente correo un archivo comprimido (.ZIP) que contiene: <br>
                1.- Lista de usuarios prontos a vencer su certificado.<br>
                2.- Boleta de Pago para la renovacion.<br>
                Es la documentación sustentatoria correspondiente, para su evaluación y
                acciones administrativas que estime pertinentes.
            </p>

            <p>
                Sin otro particular, reitero a usted las muestras de mi especial
                consideración y estima institucional.
            </p>

            <p style='margin-top:35px;'>
                Atentamente,<br><br>
                <strong>Proyecto Especial Chavimochic</strong><br>
                Sistema de Gestión de Certificados Digitales
            </p>
        </div>

        <hr style='margin-top:30px; border:none; border-top:1px solid #e0e0e0;'>

        <p style='font-size:12px; color:#888; text-align:center;'>
            Comunicación generada automáticamente a través del sistema institucional.<br>
            Este mensaje constituye una notificación electrónica oficial.
        </p>

    </div>
</div>
";



        $mail->send();

        return $zipName;
    }


    public function exportarPDF(): void
    {
        if (ob_get_length()) {
            ob_clean();
        }

        $data = $this->listar2();

        $pdf = new FPDF('L', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 14);

        $pdf->Cell(0, 10, 'Certificados por Vencer - Proximos 30 dias', 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 9);

        // Encabezados
        $pdf->Cell(30, 8, 'Certificado', 1);
        $pdf->Cell(45, 8, 'Persona', 1);
        $pdf->Cell(25, 8, 'DNI', 1);
        $pdf->Cell(55, 8, 'Correo', 1);
        $pdf->Cell(25, 8, 'Emision', 1);
        $pdf->Cell(25, 8, 'Vencimiento', 1);
        $pdf->Cell(20, 8, 'Dias', 1);
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 8);

        foreach ($data as $row) {

            $pdf->Cell(30, 8, $row['codigo_reloj'], 1);
            $pdf->Cell(45, 8, $row['nombres'].' '.$row['apellidos'], 1);
            $pdf->Cell(25, 8, $row['dni'], 1);
            $pdf->Cell(55, 8, $row['correo'], 1);
            $pdf->Cell(25, 8, date('d/m/Y', strtotime($row['fecha_emision'])), 1);
            $pdf->Cell(25, 8, date('d/m/Y', strtotime($row['fecha_vencimiento'])), 1);
            $pdf->Cell(20, 8, $row['dias_restantes'].' dias', 1);
            $pdf->Ln();
        }

        $filename = "certificados_por_vencer_" . date('Ymd_His') . ".pdf";

        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        $pdf->Output('I', $filename);
        exit;
    }

    public function actualizarEstadoCertificados(){

    // REVOCADOS (certificados con duración menor a 1 año)
    $sql1 = "UPDATE certificados.CertificadosDigitales
             SET estado = 'revocado'
             WHERE duracion_anios < 1";

    sqlsrv_query($this->db, $sql1);


    // VENCIDOS
    $sql2 = "UPDATE certificados.CertificadosDigitales
             SET estado = 'vencido'
             WHERE fecha_vencimiento < GETDATE()
             AND duracion_anios >= 1";

    sqlsrv_query($this->db, $sql2);


    // ACTIVOS
    $sql3 = "UPDATE certificados.CertificadosDigitales
             SET estado = 'activo'
             WHERE fecha_vencimiento >= GETDATE()
             AND duracion_anios >= 1";

    sqlsrv_query($this->db, $sql3);

}/* ===============================
       TOTAL DE PERSONAS
    ================================= */
    public function totalPersonas(){
        $sql = "SELECT COUNT(*) total FROM certificados.Personas";
        $stmt = sqlsrv_query($this->db,$sql);
        if($stmt === false) die(print_r(sqlsrv_errors(),true));
        $row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC);
        return $row['total'];
    }

    /* ===============================
       CERTIFICADOS POR MES
    ================================= */
    public function certificadosPorMes($anio = null){
        $anio = $anio ?? date('Y');
        $sql = "
        SELECT 
            DATENAME(MONTH, fecha_emision) mes,
            MONTH(fecha_emision) numero_mes,
            COUNT(*) total
        FROM certificados.CertificadosDigitales
        WHERE YEAR(fecha_emision) = ?
        GROUP BY DATENAME(MONTH, fecha_emision), MONTH(fecha_emision)
        ORDER BY numero_mes
        ";
        $params = [$anio];
        $stmt = sqlsrv_query($this->db, $sql, $params);
        if($stmt === false) die(print_r(sqlsrv_errors(),true));
        $datos = [];
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
            $datos[] = $row;
        }
        return $datos;
    }

    /* ===============================
       CERTIFICADOS POR AÑO
    ================================= */
    public function certificadosPorAnio(){
        $sql = "
        SELECT 
            YEAR(fecha_emision) anio,
            COUNT(*) total
        FROM certificados.CertificadosDigitales
        GROUP BY YEAR(fecha_emision)
        ORDER BY anio
        ";
        $stmt = sqlsrv_query($this->db,$sql);
        if($stmt === false) die(print_r(sqlsrv_errors(),true));
        $datos = [];
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
            $datos[] = $row;
        }
        return $datos;
    }

    /* ===============================
       CERTIFICADOS POR GERENCIA
    ================================= */
    public function comparativaGerencias(){
        $sql = "
        SELECT 
            p.gerencia_laboral,
            COUNT(cd.id_certificado) total
        FROM certificados.CertificadosDigitales cd
        INNER JOIN certificados.Personas p ON cd.id_persona = p.id_persona
        GROUP BY p.gerencia_laboral
        ORDER BY total DESC
        ";
        $stmt = sqlsrv_query($this->db,$sql);
        if($stmt === false) die(print_r(sqlsrv_errors(),true));
        $datos = [];
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
            $datos[] = $row;
        }
        return $datos;
    }

     public function eliminarBackup($id)
{
    $sql = "DELETE FROM certificados.BackupsCertificados WHERE id_backup = ?";
    
    $params = [$id];

    $stmt = sqlsrv_query($this->db, $sql, $params);

    return $stmt;
}

public function marcarCertificadosTramitados($ids = []) {
    if (empty($ids)) return false;

    // Crear lista separada por comas de IDs
    $idsStr = implode(',', $ids);

    $sql = "UPDATE certificados.CertificadosDigitales
            SET estado_tramite = 'TRAMITADO'
            WHERE id_certificado IN ($idsStr)";
    
    $stmt = sqlsrv_query($this->db, $sql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    return true;
}

public function actualizarEstadosTramite() {
    // Regresar a NO TRAMITADO los activos
    $sqlActivo = "UPDATE certificados.CertificadosDigitales
                  SET estado_tramite = 'NO TRAMITADO'
                  WHERE estado = 'activo'";
    $stmtActivo = sqlsrv_query($this->db, $sqlActivo);
    if($stmtActivo === false){
        die(print_r(sqlsrv_errors(), true));
    }

    // Marcar como TRAMITADO los vencidos
    $sqlVencido = "UPDATE certificados.CertificadosDigitales
                   SET estado_tramite = 'TRAMITADO'
                   WHERE estado = 'vencido'";
    $stmtVencido = sqlsrv_query($this->db, $sqlVencido);
    if($stmtVencido === false){
        die(print_r(sqlsrv_errors(), true));
    }

    return true;
}

public function darPermisoArchivo($archivo){
    $archivo = escapeshellarg($archivo);
    $comando = "icacls $archivo /grant Todos:F";
    exec($comando);
}

 // =========================
    // CERTIFICADO
    // =========================
    public function getCertificadoById($id)
    {
        $sql = "SELECT * FROM certificados.CertificadosDigitales WHERE id_certificado = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);

        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    // =========================
    // BACKUPS LISTA
    // =========================
    public function getBackupsByCertificado($id)
    {
        $sql = "SELECT * FROM certificados.BackupsCertificados WHERE id_certificado = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);

        $data = [];

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    // =========================
    // INSERT BACKUP
    // =========================
    public function insertBackup($data)
    {
        $sql = "INSERT INTO certificados.BackupsCertificados
                (codigo_reloj, identificador, ruta_archivo, fecha_backup, id_certificado, id_usuario_backup)
                VALUES (?, ?, ?, GETDATE(), ?, ?)";

        $params = [
            $data['codigo_reloj'],
            $data['identificador'],
            $data['ruta_archivo'],
            $data['id_certificado'],
            $data['id_usuario_backup']
        ];

        return sqlsrv_query($this->db, $sql, $params);
    }

    // =========================
    // ELIMINAR BACKUP
    // =========================
    public function deleteBackup($id)
    {
        $sql = "DELETE FROM certificados.BackupsCertificados WHERE id_backup = ?";
        return sqlsrv_query($this->db, $sql, [$id]);
    }

    // =========================
    // GET ARCHIVO BACKUP
    // =========================
    public function getBackupFile($id)
    {
        $sql = "SELECT ruta_archivo FROM certificados.BackupsCertificados WHERE id_backup = ?";
        $stmt = sqlsrv_query($this->db, $sql, [$id]);

        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

}
?>