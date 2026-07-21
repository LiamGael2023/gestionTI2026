-- =========================================================================
-- Migración: Agregar método de pago PLANILLA y campo descuento_planilla
-- =========================================================================
USE BD_PRODUCCIONDESARROLLO;
GO

-- 1. Eliminar constraint anterior si existe
IF EXISTS (
    SELECT 1 FROM sys.check_constraints
    WHERE name = 'CK_transaccion_metodo_pago'
      AND parent_object_id = OBJECT_ID('dbo.transaccion')
)
BEGIN
    ALTER TABLE dbo.transaccion DROP CONSTRAINT CK_transaccion_metodo_pago;
END
GO

-- 2. Agregar columna descuento_planilla si no existe
IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE name = 'descuento_planilla'
      AND object_id = OBJECT_ID('dbo.transaccion')
)
BEGIN
    ALTER TABLE dbo.transaccion
    ADD descuento_planilla BIT NOT NULL DEFAULT 0;
END
GO

-- 3. Recrear constraint incluyendo PLANILLA
ALTER TABLE dbo.transaccion
ADD CONSTRAINT CK_transaccion_metodo_pago
CHECK (metodo_pago IN ('VENTA', 'DONACION', 'PLANILLA'));
GO

-- 4. Verificar
SELECT 
    con.name AS constraint_name,
    con.definition,
    col.name AS column_name
FROM sys.check_constraints con
INNER JOIN sys.columns col 
    ON con.parent_object_id = col.object_id 
   AND con.parent_column_id = col.column_id
INNER JOIN sys.tables t 
    ON con.parent_object_id = t.object_id
WHERE t.name = 'transaccion' 
  AND col.name = 'metodo_pago';

SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'transaccion' AND COLUMN_NAME = 'descuento_planilla';
