-- 07_idpozo.sql - Coloca el ID del catalogo (DREN-xxx / CAL-xxx) en id_pozo
-- Se sueltan las FKs de id_pozo (solo aceptaban pozos_catastro) y se retiran
-- las columnas puente id_dren/id_calidad (ya no hacen falta).
\set ON_ERROR_STOP on

BEGIN;

-- 1) Soltar FKs de id_pozo hacia pozos_catastro (impiden DREN-/CAL-)
ALTER TABLE hidrologia.pozos_monitoreo DROP CONSTRAINT pozos_monitoreo_id_pozo_fkey;
ALTER TABLE hidrologia.calidad_agua_laboratorio DROP CONSTRAINT fk_pozo_lab;

-- 2) Llenar id_pozo con el id del catalogo (drene o calidad)
UPDATE hidrologia.pozos_monitoreo
   SET id_pozo = COALESCE(id_dren, id_calidad)
 WHERE tipo <> 'comportamiento freatico';

UPDATE hidrologia.calidad_agua_laboratorio
   SET id_pozo = COALESCE(id_dren, id_calidad)
 WHERE tipo <> 'comportamiento freatico';

-- 3) Quitar columnas puente (sus FKs e indices se eliminan solos)
ALTER TABLE hidrologia.pozos_monitoreo DROP COLUMN id_dren, DROP COLUMN id_calidad;
ALTER TABLE hidrologia.calidad_agua_laboratorio DROP COLUMN id_dren, DROP COLUMN id_calidad;

-- 4) Indice sobre id_pozo en la tabla lab (pozos_monitoreo ya tiene idx_pozos_fk)
CREATE INDEX IF NOT EXISTS idx_lab_pozo ON hidrologia.calidad_agua_laboratorio (id_pozo);

COMMIT;
