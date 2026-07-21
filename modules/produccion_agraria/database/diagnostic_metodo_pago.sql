-- Script para diagnosticar el CHECK constraint de metodo_pago
-- Ejecutar en SQL Server

-- Ver el nombre y definicion del CHECK constraint
SELECT 
    con.name AS constraint_name,
    con.definition,
    col.name AS column_name
FROM sys.check_constraints con
INNER JOIN sys.columns col ON con.parent_object_id = col.object_id AND con.parent_column_id = col.column_id
INNER JOIN sys.tables t ON con.parent_object_id = t.object_id
WHERE t.name = 'transaccion' AND col.name = 'metodo_pago';

-- Alternativa: verificar valores existentes
SELECT DISTINCT metodo_pago FROM BD_PRODUCCIONDESARROLLO.dbo.transaccion WHERE metodo_pago IS NOT NULL;
