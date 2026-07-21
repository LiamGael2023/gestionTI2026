<?php
require_once __DIR__ . '/config/db.php';
$conn = Conexion::conectar();
if (!$conn) {
    die("Error connecting to DB: " . print_r(sqlsrv_errors(), true));
}

$queries = [
    "IF COL_LENGTH('laboratorio.Parametro_Analisis', 'Posgre_Nombre') IS NULL
     BEGIN
         ALTER TABLE laboratorio.Parametro_Analisis ADD Posgre_Nombre NVARCHAR(100) NULL;
     END",
     
    "IF COL_LENGTH('laboratorio.Parametro_Analisis', 'Posgre_Tabla') IS NULL
     BEGIN
         ALTER TABLE laboratorio.Parametro_Analisis ADD Posgre_Tabla NVARCHAR(100) NULL;
     END",
     
    "IF COL_LENGTH('laboratorio.Muestra_Lab', 'Id_Medicion_PG') IS NULL
     BEGIN
         ALTER TABLE laboratorio.Muestra_Lab ADD Id_Medicion_PG INT NULL;
     END",
     
    "IF COL_LENGTH('laboratorio.Proyecto_Monitoreo', 'Id_Proyecto_Pozos_Origen') IS NULL
     BEGIN
         ALTER TABLE laboratorio.Proyecto_Monitoreo ADD Id_Proyecto_Pozos_Origen INT NULL;
         ALTER TABLE laboratorio.Proyecto_Monitoreo ADD CONSTRAINT FK_ProyOrigen_Proy FOREIGN KEY (Id_Proyecto_Pozos_Origen) REFERENCES laboratorio.Proyecto_Monitoreo(Id_Proyecto);
     END",
     
    "IF COL_LENGTH('laboratorio.Monitoreo_Pozo_Asignacion', 'Id_Producto_Lab') IS NULL
     BEGIN
         ALTER TABLE laboratorio.Monitoreo_Pozo_Asignacion ADD Id_Producto_Lab INT NULL;
         ALTER TABLE laboratorio.Monitoreo_Pozo_Asignacion ADD CONSTRAINT FK_MonAsig_ProdLab FOREIGN KEY (Id_Producto_Lab) REFERENCES laboratorio.Producto_Venta(Id_Producto);
     END",
     
    "IF COL_LENGTH('laboratorio.Muestra_Lab', 'Lab_Habilitado') IS NULL
     BEGIN
         ALTER TABLE laboratorio.Muestra_Lab ADD Lab_Habilitado BIT DEFAULT 0;
     END",
     
    "IF OBJECT_ID('laboratorio.Consumo_Resultado', 'U') IS NULL
     BEGIN
         CREATE TABLE laboratorio.Consumo_Resultado (
             Id_Consumo_Resultado INT IDENTITY(1,1) PRIMARY KEY,
             Id_Resultado INT NOT NULL CONSTRAINT FK_ConRes_Res FOREIGN KEY REFERENCES laboratorio.Resultado_Analisis(Id_Resultado),
             Id_Movimiento INT NOT NULL CONSTRAINT FK_ConRes_Mov FOREIGN KEY REFERENCES laboratorio.Movimiento_Kardex(Id_Movimiento),
             Activo BIT DEFAULT 1,
             Fecha_Creacion DATETIME DEFAULT GETDATE(),
             Usuario_Creacion INT
         );
     END",

    // === NUEVO: Columna Orden en Monitoreo_Pozo_Asignacion ===
    "IF COL_LENGTH('laboratorio.Monitoreo_Pozo_Asignacion', 'Orden') IS NULL
     BEGIN
         ALTER TABLE laboratorio.Monitoreo_Pozo_Asignacion ADD Orden INT NULL;
     END",

    // === NUEVO: Quitar indice unico viejo y crear uno que incluya Orden ===
    "IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_MonAsig_Activa' AND object_id = OBJECT_ID('laboratorio.Monitoreo_Pozo_Asignacion'))
     BEGIN
         DROP INDEX IX_MonAsig_Activa ON laboratorio.Monitoreo_Pozo_Asignacion;
     END",

    "IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_MonAsig_Activa_Orden' AND object_id = OBJECT_ID('laboratorio.Monitoreo_Pozo_Asignacion'))
     BEGIN
         CREATE UNIQUE INDEX IX_MonAsig_Activa_Orden
         ON laboratorio.Monitoreo_Pozo_Asignacion(Id_Proyecto, Orden, Numero_Muestra)
         WHERE Activo = 1;
     END",

    // === NUEVO: Columna Es_Pozo en Muestra_Lab ===
    "IF COL_LENGTH('laboratorio.Muestra_Lab', 'Es_Pozo') IS NULL
     BEGIN
         ALTER TABLE laboratorio.Muestra_Lab ADD Es_Pozo BIT DEFAULT 0;
     END",

    // === NUEVO: FK desde Parametro_Analisis a Unidad_Medida ===
    "IF COL_LENGTH('laboratorio.Parametro_Analisis', 'Id_Unidad_Medida') IS NULL
     BEGIN
         ALTER TABLE laboratorio.Parametro_Analisis ADD Id_Unidad_Medida INT NULL;
         ALTER TABLE laboratorio.Parametro_Analisis ADD CONSTRAINT FK_Param_UM 
             FOREIGN KEY (Id_Unidad_Medida) REFERENCES laboratorio.Unidad_Medida(Id_Unidad_Medida);
     END",
     
    // Migrar datos: insertar unidades desde Parametro_Analisis.Unidad_Medida
    "INSERT INTO laboratorio.Unidad_Medida (Nombre, Abreviatura, Activo, Fecha_Creacion, Usuario_Creacion)
     SELECT DISTINCT 
         CASE 
             WHEN LTRIM(RTRIM(ISNULL(pa.Unidad_Medida,''))) = 'Unidad de pH' THEN 'Unidad de pH'
             WHEN LTRIM(RTRIM(ISNULL(pa.Unidad_Medida,''))) = 'mg/L' THEN 'Miligramos por Litro'
             WHEN LTRIM(RTRIM(ISNULL(pa.Unidad_Medida,''))) = 'NTU' THEN 'Unidad Nefelometrica de Turbidez'
             WHEN LTRIM(RTRIM(ISNULL(pa.Unidad_Medida,''))) = 'CaCo3 mg/L' THEN 'Carbonato de Calcio mg/L'
             WHEN LTRIM(RTRIM(ISNULL(pa.Unidad_Medida,''))) = 'UFC/100 ml' THEN 'Unidades Formadoras de Colonias'
             WHEN LTRIM(RTRIM(ISNULL(pa.Unidad_Medida,''))) = 'mS/cm' THEN 'Millisiemens por Centimetro'
             WHEN LTRIM(RTRIM(ISNULL(pa.Unidad_Medida,''))) = 'g/ml' THEN 'Gramos por Mililitro'
             WHEN LTRIM(RTRIM(ISNULL(pa.Unidad_Medida,''))) = '%' THEN 'Porcentaje'
             WHEN LTRIM(RTRIM(ISNULL(pa.Unidad_Medida,''))) = 'mg/kg PS' THEN 'Miligramos por Kilogramo Peso Seco'
             ELSE ISNULL(pa.Unidad_Medida, 'Sin Unidad')
         END AS Nombre,
         LTRIM(RTRIM(ISNULL(pa.Unidad_Medida, '-'))) AS Abreviatura,
         1, GETDATE(), 1
     FROM laboratorio.Parametro_Analisis pa
     WHERE LTRIM(RTRIM(ISNULL(pa.Unidad_Medida, ''))) <> ''
       AND NOT EXISTS (
         SELECT 1 FROM laboratorio.Unidad_Medida um 
         WHERE um.Abreviatura = LTRIM(RTRIM(ISNULL(pa.Unidad_Medida, ''))) AND um.Activo = 1
     )",
     
    // Vincular FK
    "UPDATE pa SET pa.Id_Unidad_Medida = um.Id_Unidad_Medida
     FROM laboratorio.Parametro_Analisis pa
     INNER JOIN laboratorio.Unidad_Medida um ON LTRIM(RTRIM(ISNULL(pa.Unidad_Medida, ''))) = um.Abreviatura AND um.Activo = 1
     WHERE pa.Id_Unidad_Medida IS NULL"
];

foreach ($queries as $q) {
    $stmt = sqlsrv_query($conn, $q);
    if ($stmt === false) {
        echo "Error in query: $q\n" . print_r(sqlsrv_errors(), true) . "\n";
    } else {
        echo "Success: $q\n";
    }
}
echo "Migration finished.\n";
?>
