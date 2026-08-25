-- 09_limpiar_renumerar.sql - Elimina los puntos fantasma (solo geometria, sin monitoreo)
-- y reenumera los catalogos: drenes 29 (DREN-001..029), calidad 11 (CAL-001..011).
\set ON_ERROR_STOP on

BEGIN;

-- 1) Eliminar los 4 puntos fantasma del catalogo
DELETE FROM hidrologia.drenes_catastro  WHERE id_dren   IN ('DREN-001','DREN-002');
DELETE FROM hidrologia.calidad_catastro WHERE id_calidad IN ('CAL-001','CAL-002');

-- 2) Reenumerar DRENES (29) y repuntar id_pozo
ALTER TABLE hidrologia.drenes_catastro DROP CONSTRAINT drenes_catastro_pkey;

CREATE TEMP TABLE _rn_d AS
SELECT id_dren AS old_id,
       'DREN-' || lpad(row_number() OVER (ORDER BY id_dren)::text, 3, '0') AS new_id
FROM hidrologia.drenes_catastro;

UPDATE hidrologia.pozos_monitoreo pm  SET id_pozo = r.new_id FROM _rn_d r WHERE pm.id_pozo  = r.old_id;
UPDATE hidrologia.calidad_agua_laboratorio cl SET id_pozo = r.new_id FROM _rn_d r WHERE cl.id_pozo = r.old_id;
UPDATE hidrologia.drenes_catastro d   SET id_dren = r.new_id FROM _rn_d r WHERE d.id_dren  = r.old_id;

ALTER TABLE hidrologia.drenes_catastro ADD CONSTRAINT drenes_catastro_pkey PRIMARY KEY (id_dren);

-- 3) Reenumerar CALIDAD (11) y repuntar id_pozo
ALTER TABLE hidrologia.calidad_catastro DROP CONSTRAINT calidad_catastro_pkey;

CREATE TEMP TABLE _rn_c AS
SELECT id_calidad AS old_id,
       'CAL-' || lpad(row_number() OVER (ORDER BY id_calidad)::text, 3, '0') AS new_id
FROM hidrologia.calidad_catastro;

UPDATE hidrologia.pozos_monitoreo pm  SET id_pozo = r.new_id FROM _rn_c r WHERE pm.id_pozo  = r.old_id;
UPDATE hidrologia.calidad_agua_laboratorio cl SET id_pozo = r.new_id FROM _rn_c r WHERE cl.id_pozo = r.old_id;
UPDATE hidrologia.calidad_catastro c  SET id_calidad = r.new_id FROM _rn_c r WHERE c.id_calidad = r.old_id;

ALTER TABLE hidrologia.calidad_catastro ADD CONSTRAINT calidad_catastro_pkey PRIMARY KEY (id_calidad);

COMMIT;