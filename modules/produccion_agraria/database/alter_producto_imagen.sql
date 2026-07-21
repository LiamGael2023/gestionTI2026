-- Script para agregar columnas de imagen a la tabla producto
-- Ejecutar en SQL Server, base BD_PRODUCCIONDESARROLLO

IF NOT EXISTS (
    SELECT * FROM sys.columns 
    WHERE object_id = OBJECT_ID('BD_PRODUCCIONDESARROLLO.dbo.producto') 
    AND name = 'imagen_nombre'
)
BEGIN
    ALTER TABLE BD_PRODUCCIONDESARROLLO.dbo.producto ADD imagen_nombre VARCHAR(255) NULL;
END
GO

IF NOT EXISTS (
    SELECT * FROM sys.columns 
    WHERE object_id = OBJECT_ID('BD_PRODUCCIONDESARROLLO.dbo.producto') 
    AND name = 'imagen_blob'
)
BEGIN
    ALTER TABLE BD_PRODUCCIONDESARROLLO.dbo.producto ADD imagen_blob VARBINARY(MAX) NULL;
END
GO
