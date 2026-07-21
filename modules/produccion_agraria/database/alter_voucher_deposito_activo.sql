-- Soft Delete: Agregar columna activo a voucher_deposito
-- Reemplaza DELETE fisico por UPDATE activo = 0
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('BD_PRODUCCIONDESARROLLO.dbo.voucher_deposito') AND name = 'activo')
BEGIN
    ALTER TABLE BD_PRODUCCIONDESARROLLO.dbo.voucher_deposito ADD activo BIT NOT NULL DEFAULT 1;
END
