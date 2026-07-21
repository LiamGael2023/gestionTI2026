-- Agregar FK Id_Asignacion a Muestra_Lab para relacion directa con Monitoreo_Pozo_Asignacion
-- Esto permite tracking preciso de fechas de analisis por pozo a lo largo del tiempo

-- 1. Agregar columna
ALTER TABLE laboratorio.Muestra_Lab
ADD Id_Asignacion INT NULL;
GO

-- 2. Foreign Key
ALTER TABLE laboratorio.Muestra_Lab
ADD CONSTRAINT FK_Muestra_Asignacion 
FOREIGN KEY (Id_Asignacion) REFERENCES laboratorio.Monitoreo_Pozo_Asignacion(Id_Asignacion);
GO

-- 3. Poblar Id_Asignacion para registros existentes (usando Id_Proyecto + Id_Pozo + Fecha_Toma)
UPDATE ml
SET ml.Id_Asignacion = mpa.Id_Asignacion
FROM laboratorio.Muestra_Lab ml
INNER JOIN laboratorio.Monitoreo_Pozo_Asignacion mpa 
    ON ml.Id_Proyecto = mpa.Id_Proyecto 
    AND ml.Id_Pozo = mpa.Id_Pozo
WHERE ml.Id_Pozo IS NOT NULL 
  AND ml.Id_Asignacion IS NULL;
GO

-- Verificar
SELECT COUNT(*) AS total, 
       SUM(CASE WHEN Id_Asignacion IS NOT NULL THEN 1 ELSE 0 END) AS con_asignacion,
       SUM(CASE WHEN Id_Asignacion IS NULL AND Id_Pozo IS NOT NULL THEN 1 ELSE 0 END) AS sin_asignacion
FROM laboratorio.Muestra_Lab
WHERE Id_Pozo IS NOT NULL;
GO
