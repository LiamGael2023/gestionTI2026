-- Script para modificar el CHECK constraint de metodo_pago
-- para permitir 'VENTA' y 'DONACION'
-- Ejecutar en SQL Server, base BD_PRODUCCIONDESARROLLO

-- PASO 1: Ver el constraint actual (opcional, para diagnosticar)
SELECT 
    con.name AS constraint_name,
    con.definition,
    col.name AS column_name
FROM sys.check_constraints con
INNER JOIN sys.columns col ON con.parent_object_id = col.object_id AND con.parent_column_id = col.column_id
INNER JOIN sys.tables t ON con.parent_object_id = t.object_id
WHERE t.name = 'transaccion' AND col.name = 'metodo_pago';
GO

-- PASO 2: Eliminar el constraint existente (si existe)
-- NOTA: Reemplazar 'CK__transacci__metod__5165187F' con el nombre real si es diferente
IF EXISTS (
    SELECT * FROM sys.check_constraints 
    WHERE parent_object_id = OBJECT_ID('BD_PRODUCCIONDESARROLLO.dbo.transaccion') 
    AND name LIKE 'CK__transacci__metod%'
)
BEGIN
    ALTER TABLE BD_PRODUCCIONDESARROLLO.dbo.transaccion 
    DROP CONSTRAINT CK__transacci__metod__5165187F;
END
GO

-- PASO 3: Crear nuevo CHECK constraint que permita VENTA y DONACION
ALTER TABLE BD_PRODUCCIONDESARROLLO.dbo.transaccion 
ADD CONSTRAINT CK_transaccion_metodo_pago 
CHECK (metodo_pago IN ('VENTA', 'DONACION'));
GO

-- PASO 4: Verificar que se aplicó correctamente
SELECT 
    con.name AS constraint_name,
    con.definition
FROM sys.check_constraints con
INNER JOIN sys.tables t ON con.parent_object_id = t.object_id
WHERE t.name = 'transaccion' AND con.name = 'CK_transaccion_metodo_pago';
GO
