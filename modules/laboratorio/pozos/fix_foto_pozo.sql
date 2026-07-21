-- Ejecutar en SQL Server (BD_GESTION_TI)
-- Arregla el error: "String or binary data would be truncated in column 'foto_pozo'"

ALTER TABLE laboratorio.Catastro_Pozo ALTER COLUMN foto_pozo VARCHAR(MAX) NULL;

-- Verificar cambio
SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'laboratorio' 
  AND TABLE_NAME = 'Catastro_Pozo' 
  AND COLUMN_NAME = 'foto_pozo';
