-- =========================================================================
-- Migracion: Restringir CHECK constraint de tipo_precio a UIT/Variable
-- Fecha: 2026-07-27
-- BD: BD_PRODUCCIONDESARROLLO
-- =========================================================================

USE BD_PRODUCCIONDESARROLLO;
GO

-- 1. Ver constraint actual
SELECT 
    con.name AS constraint_name,
    con.definition,
    col.name AS column_name
FROM sys.check_constraints con
INNER JOIN sys.columns col ON con.parent_object_id = col.object_id AND con.parent_column_id = col.column_id
INNER JOIN sys.tables t ON con.parent_object_id = t.object_id
WHERE t.name = 'producto' AND col.name = 'tipo_precio';
GO

-- 2. Eliminar constraint anterior si existe (cualquier nombre)
IF EXISTS (
    SELECT 1 FROM sys.check_constraints
    WHERE parent_object_id = OBJECT_ID('dbo.producto')
      AND parent_column_id = (
          SELECT column_id FROM sys.columns
          WHERE object_id = OBJECT_ID('dbo.producto') AND name = 'tipo_precio'
      )
)
BEGIN
    DECLARE @sql NVARCHAR(MAX);
    SELECT @sql = 'ALTER TABLE dbo.producto DROP CONSTRAINT ' + QUOTENAME(name)
    FROM sys.check_constraints
    WHERE parent_object_id = OBJECT_ID('dbo.producto')
      AND parent_column_id = (
          SELECT column_id FROM sys.columns
          WHERE object_id = OBJECT_ID('dbo.producto') AND name = 'tipo_precio'
      );
    EXEC sp_executesql @sql;
END
GO

-- 3. Recrear constraint permitiendo solo UIT y Variable
ALTER TABLE dbo.producto
ADD CONSTRAINT CK_producto_tipo_precio
CHECK (tipo_precio IN ('UIT', 'Variable'));
GO

-- 4. Verificar
SELECT 
    con.name AS constraint_name,
    con.definition,
    col.name AS column_name
FROM sys.check_constraints con
INNER JOIN sys.columns col ON con.parent_object_id = col.object_id AND con.parent_column_id = col.column_id
INNER JOIN sys.tables t ON con.parent_object_id = t.object_id
WHERE t.name = 'producto' AND col.name = 'tipo_precio';
GO

-- 5. Verificar valores existentes (no deben quedar invalidos)
SELECT DISTINCT tipo_precio FROM dbo.producto WHERE tipo_precio IS NOT NULL;
GO
