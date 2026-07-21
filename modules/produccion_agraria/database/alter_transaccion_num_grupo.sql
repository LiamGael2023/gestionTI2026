-- =========================================================================
-- Migracion: Agregar columna num_grupo para agrupar ventas masivas
-- =========================================================================
USE BD_PRODUCCIONDESARROLLO;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE name = 'num_grupo'
      AND object_id = OBJECT_ID('dbo.transaccion')
)
BEGIN
    ALTER TABLE dbo.transaccion
    ADD num_grupo VARCHAR(30) NULL;
    PRINT 'Columna num_grupo agregada.';
END
ELSE
    PRINT 'Columna num_grupo ya existe.';
GO

-- Indice para busquedas por grupo
IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_transaccion_num_grupo'
      AND object_id = OBJECT_ID('dbo.transaccion')
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_transaccion_num_grupo
    ON dbo.transaccion (num_grupo)
    WHERE num_grupo IS NOT NULL;
    PRINT 'Indice IX_transaccion_num_grupo creado.';
END
ELSE
    PRINT 'Indice IX_transaccion_num_grupo ya existe.';
GO

-- Verificar
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'transaccion' AND COLUMN_NAME = 'num_grupo';
