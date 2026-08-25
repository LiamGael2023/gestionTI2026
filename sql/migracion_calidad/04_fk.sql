-- 04_fk.sql - Columnas de referencia a drenes_catastro / calidad_catastro
-- id_pozo solo puede apuntar a pozos_catastro (FK existente), por eso los
-- registros de drenes/calidad usan id_dren / id_calidad y dejan id_pozo NULL.
\set ON_ERROR_STOP on

ALTER TABLE hidrologia.pozos_monitoreo
    ADD COLUMN IF NOT EXISTS id_dren     VARCHAR(20),
    ADD COLUMN IF NOT EXISTS id_calidad  VARCHAR(20);

ALTER TABLE hidrologia.pozos_monitoreo
    ADD CONSTRAINT fk_mon_dren FOREIGN KEY (id_dren)    REFERENCES hidrologia.drenes_catastro(id_dren),
    ADD CONSTRAINT fk_mon_cal  FOREIGN KEY (id_calidad) REFERENCES hidrologia.calidad_catastro(id_calidad);

CREATE INDEX IF NOT EXISTS idx_mon_dren ON hidrologia.pozos_monitoreo (id_dren);
CREATE INDEX IF NOT EXISTS idx_mon_cal  ON hidrologia.pozos_monitoreo (id_calidad);

ALTER TABLE hidrologia.calidad_agua_laboratorio
    ADD COLUMN IF NOT EXISTS id_dren     VARCHAR(20),
    ADD COLUMN IF NOT EXISTS id_calidad  VARCHAR(20);

ALTER TABLE hidrologia.calidad_agua_laboratorio
    ADD CONSTRAINT fk_lab_dren FOREIGN KEY (id_dren)    REFERENCES hidrologia.drenes_catastro(id_dren),
    ADD CONSTRAINT fk_lab_cal  FOREIGN KEY (id_calidad) REFERENCES hidrologia.calidad_catastro(id_calidad);

CREATE INDEX IF NOT EXISTS idx_lab_dren ON hidrologia.calidad_agua_laboratorio (id_dren);
CREATE INDEX IF NOT EXISTS idx_lab_cal  ON hidrologia.calidad_agua_laboratorio (id_calidad);
