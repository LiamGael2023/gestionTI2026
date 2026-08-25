-- 08_reconstruir.sql - Revierte la fusion de drenes: 31 puntos (ubicacion+coordenadas)
-- y re-migra las 269 mediciones con id_pozo = DREN-xxx / CAL-xxx por punto especifico.
\set ON_ERROR_STOP on

BEGIN;

-- 1) Eliminar las 269 mediciones migradas (drene + calidad agua), dejando los 10626/2936 pozos
DELETE FROM hidrologia.calidad_agua_laboratorio WHERE tipo <> 'comportamiento freatico';
DELETE FROM hidrologia.pozos_monitoreo          WHERE tipo <> 'comportamiento freatico';

-- 2) Reconstruir drenes_catastro a 31 puntos (un punto unico por ubicacion+coordenadas)
DELETE FROM hidrologia.drenes_catastro;

WITH un AS (
\i union_drenes.sql
),
dedup AS (
    SELECT DISTINCT ON (clv) *
    FROM un
    WHERE clv IS NOT NULL
    ORDER BY clv, (cooreste IS NOT NULL) DESC, (fechamonitoreo IS NOT NULL) DESC, fechamonitoreo
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

-- 3) Re-migrar las 269 mediciones: id_pozo = id del catalogo por punto (drene o calidad)
DO $$
DECLARE
    r      RECORD;
    newmed INTEGER;
    n      INTEGER := 0;
BEGIN
    FOR r IN
        SELECT u.*,
               CASE WHEN u.tipo = 'drene' THEN d.id_dren ELSE c.id_calidad END AS id_catalogo
        FROM ( \i union_drenes.sql
               UNION ALL
               \i union_superficial.sql ) u
        LEFT JOIN hidrologia.drenes_catastro d
          ON btrim(upper(coalesce(nullif(d.ubicacion,''),'(SIN NOMBRE)'))) || '|'
             || coalesce(d.cooreste::text,'') || '|' || coalesce(d.coornorte::text,'') = u.clv
        LEFT JOIN hidrologia.calidad_catastro c
          ON btrim(upper(coalesce(nullif(c.ubicacion,''),'(SIN NOMBRE)'))) || '|'
             || coalesce(c.cooreste::text,'') || '|' || coalesce(c.coornorte::text,'') = u.clv
    LOOP
        IF r.id_catalogo IS NULL THEN
            RAISE NOTICE 'SIN CATALOGO: %', r.clv;
            CONTINUE;
        END IF;

        INSERT INTO hidrologia.pozos_monitoreo
            (id_pozo, tipo, monitoreo, fechamonitoreo, horamonitoreo, ce, ph, std, t, observaciones)
        VALUES
            (r.id_catalogo, r.tipo, r.campana, r.fechamonitoreo, r.horamonitoreo,
             r.ce, r.ph, r.std, r.t, left(nullif(r.observaciones,''),100))
        RETURNING id_medicion INTO newmed;

        INSERT INTO hidrologia.calidad_agua_laboratorio
            (id_pozo, id_medicion, orden, tipo, fecha_toma_muestra,
             turbidez, durezatotal, nitratos, nitritos, sulfatos, cloruros, cloruro, amonio,
             cromohexavalente, cobre, manganeso, hierro, zinc, calcio, potasio, sodio, magnesio,
             coliformestotales, coliformestermotolerantes, escherichiacoli, observaciones)
        VALUES
            (r.id_catalogo, newmed, r.orden::int, r.tipo, r.fechamonitoreo,
             r.turbidez::text, r.durezatotal::text, r.nitratos::text, r.nitritos::text,
             r.sulfatos::text, r.cloruros::text, r.cloruro::text, r.amonio::text,
             r.cromohexavalente::text, r.cobre::text, r.manganeso::text, r.hierro::text,
             r.zinc::text, r.calcio::text, r.potasio::text, r.sodio::text, r.magnesio::text,
             r.coliformestotales::text, r.coliformestermotolerantes::text,
             r.escherichiacoli::text, nullif(r.observaciones,''));
        n := n + 1;
    END LOOP;

    RAISE NOTICE 'RE-MIGRADAS: %', n;
END $$;

COMMIT;
