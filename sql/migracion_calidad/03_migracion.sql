-- 03_migracion.sql - Migra las mediciones historicas de calidad (drenes + superficial, 2022-2026)
--   * pozos_monitoreo:        in-situ por medicion (ce, ph, std, t) + campana, tipo=drene/calidad agua
--   * calidad_agua_laboratorio: parametros extendidos por medicion, enlazados por id_medicion, con tipo
--   Los puntos se resuelven contra drenes_catastro / calidad_catastro por ubicacion+coordenadas.
\set ON_ERROR_STOP on

BEGIN;

DO $$
DECLARE
    r       RECORD;
    newmed  INTEGER;
    n_th   INTEGER := 0;
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
            (id_pozo, id_dren, id_calidad, tipo, monitoreo, fechamonitoreo, horamonitoreo,
             ce, ph, std, t, observaciones)
        VALUES
            (NULL,
             CASE WHEN r.tipo='drene' THEN r.id_catalogo END,
             CASE WHEN r.tipo='calidad agua' THEN r.id_catalogo END,
             r.tipo, r.campana, r.fechamonitoreo, r.horamonitoreo,
             r.ce, r.ph, r.std, r.t, left(nullif(r.observaciones,''),100))
        RETURNING id_medicion INTO newmed;

        INSERT INTO hidrologia.calidad_agua_laboratorio
            (id_pozo, id_medicion, id_dren, id_calidad, orden, tipo, fecha_toma_muestra,
             turbidez, durezatotal, nitratos, nitritos, sulfatos, cloruros, cloruro, amonio,
             cromohexavalente, cobre, manganeso, hierro, zinc, calcio, potasio, sodio, magnesio,
             coliformestotales, coliformestermotolerantes, escherichiacoli, observaciones)
        VALUES
            (NULL, newmed,
             CASE WHEN r.tipo='drene' THEN r.id_catalogo END,
             CASE WHEN r.tipo='calidad agua' THEN r.id_catalogo END,
             r.orden::int, r.tipo, r.fechamonitoreo,
             r.turbidez::text, r.durezatotal::text, r.nitratos::text, r.nitritos::text,
             r.sulfatos::text, r.cloruros::text, r.cloruro::text, r.amonio::text,
             r.cromohexavalente::text, r.cobre::text, r.manganeso::text, r.hierro::text,
             r.zinc::text, r.calcio::text, r.potasio::text, r.sodio::text, r.magnesio::text,
             r.coliformestotales::text, r.coliformestermotolerantes::text,
             r.escherichiacoli::text, nullif(r.observaciones,''));
        n_th := n_th + 1;
    END LOOP;

    RAISE NOTICE 'MEDICIONES MIGRADAS: %', n_th;
END $$;

COMMIT;
