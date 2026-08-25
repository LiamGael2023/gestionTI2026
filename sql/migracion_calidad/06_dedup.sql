-- 06_dedup.sql - Deduplica drenes_catastro: un registro por nombre de drene
-- Las 143 mediciones de drenes se repuntan al registro que sobrevive y se reenumera DREN-001..
\set ON_ERROR_STOP on

BEGIN;

-- 0) Funcion de normalizacion de nombre de drene (descripcion antes de ':' y sin sub-puntos "(n)")
CREATE OR REPLACE FUNCTION hidrologia._norm_dren(txt text) RETURNS text
LANGUAGE plpgsql IMMUTABLE AS $$
BEGIN
  RETURN btrim(regexp_replace(
           regexp_replace(
             split_part(translate(upper(coalesce(txt,'')), 'ÁÉÍÓÚÜÑ', 'AEIOUUN'), ':', 1),
           '\s*\(\d+\)\s*$', ''),
         '\s+', ' '));
END $$;

-- 1) Mapeo de fusion: grupos con nombre -> 1 sobreviviente (el que tiene mas monitoreos)
CREATE TEMP TABLE _merge AS
WITH grup AS (
    SELECT d.id_dren,
           hidrologia._norm_dren(d.descripcion) AS nombre,
           (SELECT count(*) FROM hidrologia.pozos_monitoreo pm WHERE pm.id_dren = d.id_dren) AS n_mon
    FROM hidrologia.drenes_catastro d
),
elig AS (
    SELECT nombre, (array_agg(id_dren ORDER BY n_mon DESC, id_dren))[1] AS survivor
    FROM grup WHERE nombre <> '' GROUP BY nombre
)
SELECT g.id_dren AS old_id, e.survivor AS new_id
FROM grup g JOIN elig e ON e.nombre = g.nombre
WHERE g.id_dren <> e.survivor;

-- 2) Repuntar las mediciones (pozos_monitoreo = in-situ; calidad_agua_laboratorio = parametros)
UPDATE hidrologia.pozos_monitoreo pm
   SET id_dren = m.new_id
  FROM _merge m WHERE pm.id_dren = m.old_id;
UPDATE hidrologia.calidad_agua_laboratorio cl
   SET id_dren = m.new_id
  FROM _merge m WHERE cl.id_dren = m.old_id;

-- 3) Borrar los registros duplicados del catalogo
DELETE FROM hidrologia.drenes_catastro d USING _merge m WHERE d.id_dren = m.old_id;

-- 4) Reenumerar secuencialmente DREN-001..N (se sueltan FKs/PK temporalmente y se reconstruyen)
ALTER TABLE hidrologia.pozos_monitoreo DROP CONSTRAINT fk_mon_dren;
ALTER TABLE hidrologia.calidad_agua_laboratorio DROP CONSTRAINT fk_lab_dren;
ALTER TABLE hidrologia.drenes_catastro DROP CONSTRAINT drenes_catastro_pkey;

CREATE TEMP TABLE _renum AS
SELECT id_dren AS old_id,
       'DREN-' || lpad(row_number() OVER (ORDER BY hidrologia._norm_dren(descripcion), id_dren)::text,3,'0') AS new_id
FROM hidrologia.drenes_catastro;

UPDATE hidrologia.pozos_monitoreo pm SET id_dren = r.new_id FROM _renum r WHERE pm.id_dren = r.old_id;
UPDATE hidrologia.calidad_agua_laboratorio cl SET id_dren = r.new_id FROM _renum r WHERE cl.id_dren = r.old_id;
UPDATE hidrologia.drenes_catastro d SET id_dren = r.new_id, observaciones = NULL FROM _renum r WHERE d.id_dren = r.old_id;

ALTER TABLE hidrologia.drenes_catastro ADD CONSTRAINT drenes_catastro_pkey PRIMARY KEY (id_dren);
ALTER TABLE hidrologia.pozos_monitoreo ADD CONSTRAINT fk_mon_dren FOREIGN KEY (id_dren) REFERENCES hidrologia.drenes_catastro(id_dren);
ALTER TABLE hidrologia.calidad_agua_laboratorio ADD CONSTRAINT fk_lab_dren FOREIGN KEY (id_dren) REFERENCES hidrologia.drenes_catastro(id_dren);

COMMIT;
