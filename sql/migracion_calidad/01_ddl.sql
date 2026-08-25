-- 01_ddl.sql — Migracion Calidad: esquema nuevo (local PostgreSQL 16, BDG_SGDA)
-- Crea drenes_catastro, calidad_catastro y agrega columna tipo a pozos_monitoreo / calidad_agua_laboratorio.

-- ============================================================
-- 1) Tabla nueva: drenes_catastro (identidad unica de puntos de drenes)
-- ============================================================
CREATE TABLE IF NOT EXISTS hidrologia.drenes_catastro (
    id_dren         VARCHAR(20)  PRIMARY KEY,
    geom            geometry,
    cooreste        double precision,
    coornorte       double precision,
    zona            double precision,
    cota            double precision,
    departamento    VARCHAR(100),
    provincia       VARCHAR(100),
    distrito        VARCHAR(100),
    ubicacion       VARCHAR(255),
    descripcion     TEXT,
    aaa             VARCHAR(20),
    ala             VARCHAR(20),
    uh              VARCHAR(20),
    fechainventario DATE,
    observaciones   TEXT
);
CREATE INDEX IF NOT EXISTS idx_drenes_geom    ON hidrologia.drenes_catastro USING gist (geom);
CREATE INDEX IF NOT EXISTS idx_drenes_coords  ON hidrologia.drenes_catastro (cooreste, coornorte);
CREATE INDEX IF NOT EXISTS idx_drenes_ubic    ON hidrologia.drenes_catastro (ubicacion);

-- ============================================================
-- 2) Tabla nueva: calidad_catastro (identidad unica de puntos de calidad de agua)
-- ============================================================
CREATE TABLE IF NOT EXISTS hidrologia.calidad_catastro (
    id_calidad      VARCHAR(20)  PRIMARY KEY,
    geom            geometry,
    cooreste        double precision,
    coornorte       double precision,
    zona            double precision,
    cota            double precision,
    departamento    VARCHAR(100),
    provincia       VARCHAR(100),
    distrito        VARCHAR(100),
    ubicacion       VARCHAR(255),
    descripcion     TEXT,
    aaa             VARCHAR(20),
    ala             VARCHAR(20),
    uh              VARCHAR(20),
    fechainventario DATE,
    observaciones   TEXT
);
CREATE INDEX IF NOT EXISTS idx_calidad_geom   ON hidrologia.calidad_catastro USING gist (geom);
CREATE INDEX IF NOT EXISTS idx_calidad_coords ON hidrologia.calidad_catastro (cooreste, coornorte);
CREATE INDEX IF NOT EXISTS idx_calidad_ubic   ON hidrologia.calidad_catastro (ubicacion);

-- ============================================================
-- 3) Columna TIPO en las tablas de medicion (los registros actuales son de pozos = comportamiento freatico)
-- ============================================================
ALTER TABLE hidrologia.pozos_monitoreo
    ADD COLUMN IF NOT EXISTS tipo VARCHAR(30) NOT NULL DEFAULT 'comportamiento freatico';
ALTER TABLE hidrologia.calidad_agua_laboratorio
    ADD COLUMN IF NOT EXISTS tipo VARCHAR(30) NOT NULL DEFAULT 'comportamiento freatico';

CREATE INDEX IF NOT EXISTS idx_pozos_tipo      ON hidrologia.pozos_monitoreo (tipo);
CREATE INDEX IF NOT EXISTS idx_calidadlab_tipo ON hidrologia.calidad_agua_laboratorio (tipo);
