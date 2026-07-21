-- Eliminar tabla del report builder (ya no se usa)
-- Ejecutar manualmente en SQL Server:
IF OBJECT_ID('BD_PRODUCCIONDESARROLLO.dbo.reporte_config') IS NOT NULL
    DROP TABLE BD_PRODUCCIONDESARROLLO.dbo.reporte_config;
