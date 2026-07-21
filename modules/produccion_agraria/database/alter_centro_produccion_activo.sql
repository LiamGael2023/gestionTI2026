-- Soft Delete: Agregar columna activo a centro_produccion
-- Reemplaza DELETE fisico por UPDATE activo = 0
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('BD_PRODUCCIONDESARROLLO.dbo.centro_produccion') AND name = 'activo')
BEGIN
    ALTER TABLE BD_PRODUCCIONDESARROLLO.dbo.centro_produccion ADD activo BIT NOT NULL DEFAULT 1;
END
