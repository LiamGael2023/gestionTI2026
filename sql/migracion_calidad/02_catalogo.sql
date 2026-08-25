-- 02_catalogo.sql - Pobla drenes_catastro y calidad_catastro (DREN-001.. / CAL-001..)
-- Los puntos unicos salen de la union de todas las tablas base (todos los anios).
\set ON_ERROR_STOP on

-- ============================================================
-- drenes_catastro
-- ============================================================
WITH un AS (
\i union_drenes.sql
),
dedup AS (
    SELECT DISTINCT ON (clv) *
    FROM un
    WHERE clv IS NOT NULL
    ORDER BY clv,
        (cooreste IS NOT NULL) DESC,
        (fechamonitoreo IS NOT NULL) DESC,
        fechamonitoreo
),
ordered AS (
    SELECT *, row_number() OVER (ORDER BY clv) AS rn FROM dedup
)
INSERT INTO hidrologia.drenes_catastro
    (id_dren, geom, cooreste, coornorte, zona, cota, departamento, provincia, distrito,
     ubicacion, descripcion, aaa, ala, uh, fechainventario, observaciones)
SELECT 'DREN-' || lpad(rn::text, 3, '0'),
       geom, cooreste, coornorte, zona, cota, departamento, provincia, distrito,
       ubicacion, descripcion, aaa, ala, uh, fechamonitoreo, observaciones
FROM ordered;

-- ============================================================
-- calidad_catastro
-- ============================================================
WITH un AS (
\i union_superficial.sql
),
dedup AS (
    SELECT DISTINCT ON (clv) *
    FROM un
    WHERE clv IS NOT NULL
    ORDER BY clv,
        (cooreste IS NOT NULL) DESC,
        (fechamonitoreo IS NOT NULL) DESC,
        fechamonitoreo
),
ordered AS (
    SELECT *, row_number() OVER (ORDER BY clv) AS rn FROM dedup
)
INSERT INTO hidrologia.calidad_catastro
    (id_calidad, geom, cooreste, coornorte, zona, cota, departamento, provincia, distrito,
     ubicacion, descripcion, aaa, ala, uh, fechainventario, observaciones)
SELECT 'CAL-' || lpad(rn::text, 3, '0'),
       geom, cooreste, coornorte, zona, cota, departamento, provincia, distrito,
       ubicacion, descripcion, aaa, ala, uh, fechamonitoreo, observaciones
FROM ordered;
