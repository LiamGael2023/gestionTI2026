-- ============================================================
-- dashboard_config — Configuración personalizada del Dashboard
-- Soporta layout por usuario con widgets de tipo KPI, gráfico y tabla
-- ============================================================

IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = 'dashboard_config')
BEGIN
    CREATE TABLE BD_PRODUCCIONDESARROLLO.dbo.dashboard_config (
        id_config INT IDENTITY(1,1) NOT NULL,
        usuario_id INT NOT NULL,
        posicion INT NOT NULL DEFAULT 0,
        widget_tipo VARCHAR(50) NOT NULL,
        widget_titulo VARCHAR(100) NULL,
        widget_tamano VARCHAR(20) DEFAULT 'medium',
        widget_config NVARCHAR(MAX) NULL,
        activo BIT DEFAULT 1,
        fecha_creacion DATETIME DEFAULT GETDATE(),
        fecha_modificacion DATETIME DEFAULT GETDATE(),
        CONSTRAINT PK_dashboard_config PRIMARY KEY (id_config)
    );

    CREATE INDEX IX_dashboard_config_usuario ON BD_PRODUCCIONDESARROLLO.dbo.dashboard_config (usuario_id, activo, posicion);
END;
