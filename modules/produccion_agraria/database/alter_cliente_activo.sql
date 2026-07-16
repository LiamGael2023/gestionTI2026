-- Soft Delete: Agregar columna activo a cliente
-- Reemplaza DELETE fisico por UPDATE activo = 0
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('BD_PRODUCCIONDESARROLLO.dbo.cliente') AND name = 'activo')
BEGIN
    ALTER TABLE BD_PRODUCCIONDESARROLLO.dbo.cliente ADD activo BIT NOT NULL DEFAULT 1;
END
