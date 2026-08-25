-- ============================================================
-- ESQUEMA COMPLETO — BD_PRODUCCIONDESARROLLO (módulo produccion_agraria)
-- Sistema: gestionTI2026 — Subgerencia de Desarrollo Agrícola — PECH
-- Última actualización: 2026-07-31
--
-- INSTRUCCIONES:
--   1. Ejecutar en SSMS conectado a 10.0.100.252
--   2. Script IDEMPOTENTE: usa IF NOT EXISTS
-- ============================================================

USE BD_PRODUCCIONDESARROLLO;
GO

-- 1. clase
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='clase')
BEGIN
    CREATE TABLE dbo.clase (
        id_clase     INT          IDENTITY(1,1) NOT NULL,
        nombre_clase VARCHAR(100) NOT NULL,
        activo       BIT          NOT NULL DEFAULT 1,
        CONSTRAINT PK_clase PRIMARY KEY (id_clase)
    );
END;
GO

-- 2. centro_produccion
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='centro_produccion')
BEGIN
    CREATE TABLE dbo.centro_produccion (
        id_centro     INT          IDENTITY(1,1) NOT NULL,
        nombre_centro VARCHAR(100) NOT NULL,
        ubicacion     VARCHAR(200) NULL,
        encargado     VARCHAR(100) NULL,
        activo        BIT          NOT NULL DEFAULT 1,
        CONSTRAINT PK_centro_produccion PRIMARY KEY (id_centro)
    );
END;
GO

-- 3. clase_centro (N:M)
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='clase_centro')
BEGIN
    CREATE TABLE dbo.clase_centro (
        id_clase  INT NOT NULL,
        id_centro INT NOT NULL,
        CONSTRAINT PK_clase_centro PRIMARY KEY (id_clase, id_centro),
        CONSTRAINT FK_cc_clase    FOREIGN KEY (id_clase)  REFERENCES dbo.clase(id_clase),
        CONSTRAINT FK_cc_centro   FOREIGN KEY (id_centro) REFERENCES dbo.centro_produccion(id_centro)
    );
END;
GO

-- 4. uit (Unidad Impositiva Tributaria por año)
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='uit')
BEGIN
    CREATE TABLE dbo.uit (
        anio   INT           NOT NULL,
        valor  DECIMAL(10,2) NOT NULL,
        activo BIT           NOT NULL DEFAULT 1,
        CONSTRAINT PK_uit PRIMARY KEY (anio)
    );
END;
GO

-- 5. producto (con imagen BLOB y tipo_precio)
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='producto')
BEGIN
    CREATE TABLE dbo.producto (
        id_producto    INT            IDENTITY(1,1) NOT NULL,
        nombre         VARCHAR(150)   NOT NULL,
        descripcion    NVARCHAR(500)  NULL,
        unidad_medida  VARCHAR(30)    NOT NULL,
        maneja_stock   BIT            NOT NULL DEFAULT 1,
        tipo_precio    VARCHAR(20)    NOT NULL DEFAULT 'Variable',  -- 'UIT' | 'Variable'
        porcentaje_uit DECIMAL(10,4)  NULL,
        id_clase       INT            NULL,
        id_centro      INT            NULL,
        imagen_nombre  VARCHAR(255)   NULL,
        imagen_blob    VARBINARY(MAX) NULL,
        activo         BIT            NOT NULL DEFAULT 1,
        fecha_creacion DATETIME       NOT NULL DEFAULT GETDATE(),
        CONSTRAINT PK_producto          PRIMARY KEY (id_producto),
        CONSTRAINT FK_prod_clase        FOREIGN KEY (id_clase)  REFERENCES dbo.clase(id_clase),
        CONSTRAINT FK_prod_centro       FOREIGN KEY (id_centro) REFERENCES dbo.centro_produccion(id_centro),
        CONSTRAINT CK_producto_tipo_precio CHECK (tipo_precio IN ('UIT', 'Variable'))
    );
END;
GO

-- 6. historial_precio
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='historial_precio')
BEGIN
    CREATE TABLE dbo.historial_precio (
        id_precio      INT           IDENTITY(1,1) NOT NULL,
        id_producto    INT           NOT NULL,
        precio_oficial DECIMAL(10,2) NOT NULL,
        fecha_registro DATETIME      NOT NULL DEFAULT GETDATE(),
        CONSTRAINT PK_historial_precio PRIMARY KEY (id_precio),
        CONSTRAINT FK_hp_producto      FOREIGN KEY (id_producto) REFERENCES dbo.producto(id_producto)
    );
    CREATE INDEX IX_hp_producto_fecha ON dbo.historial_precio (id_producto, fecha_registro DESC);
END;
GO

-- 7. lote (base del FIFO)
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='lote')
BEGIN
    CREATE TABLE dbo.lote (
        id_lote        INT           IDENTITY(1,1) NOT NULL,
        id_producto    INT           NOT NULL,
        id_centro      INT           NULL,
        codigo_lote    VARCHAR(50)   NULL,
        stock_inicial  DECIMAL(10,3) NOT NULL DEFAULT 0,
        stock_actual   DECIMAL(10,3) NOT NULL DEFAULT 0,
        costo_unitario DECIMAL(10,4) NULL,
        observaciones  NVARCHAR(500) NULL,
        fecha_creacion DATETIME      NOT NULL DEFAULT GETDATE(),
        activo         BIT           NOT NULL DEFAULT 1,
        CONSTRAINT PK_lote          PRIMARY KEY (id_lote),
        CONSTRAINT FK_lote_producto FOREIGN KEY (id_producto) REFERENCES dbo.producto(id_producto),
        CONSTRAINT FK_lote_centro   FOREIGN KEY (id_centro)   REFERENCES dbo.centro_produccion(id_centro)
    );
    CREATE INDEX IX_lote_producto_activo ON dbo.lote (id_producto, activo, stock_actual);
END;
GO

-- 8. kardex
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='kardex')
BEGIN
    CREATE TABLE dbo.kardex (
        id_kardex       INT           IDENTITY(1,1) NOT NULL,
        id_lote         INT           NOT NULL,
        id_transaccion  INT           NULL,
        tipo_movimiento VARCHAR(20)   NOT NULL,  -- 'INGRESO'|'VENTA'|'MERMA'|'REINTEGRO'
        cantidad        DECIMAL(10,3) NOT NULL,
        saldo_final     DECIMAL(10,3) NOT NULL,
        fecha           DATETIME      NOT NULL DEFAULT GETDATE(),
        observacion     NVARCHAR(300) NULL,
        CONSTRAINT PK_kardex      PRIMARY KEY (id_kardex),
        CONSTRAINT FK_kardex_lote FOREIGN KEY (id_lote) REFERENCES dbo.lote(id_lote),
        CONSTRAINT CK_kardex_tipo CHECK (tipo_movimiento IN ('INGRESO','VENTA','MERMA','REINTEGRO'))
    );
    CREATE INDEX IX_kardex_lote_fecha ON dbo.kardex (id_lote, fecha DESC);
END;
GO

-- 9. cliente (0=Planilla, 1=Externo)
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='cliente')
BEGIN
    CREATE TABLE dbo.cliente (
        id_cliente   INT          IDENTITY(1,1) NOT NULL,
        dni_ruc      VARCHAR(15)  NOT NULL,
        nombre_rs    VARCHAR(200) NOT NULL,
        tipo_cliente INT          NOT NULL DEFAULT 1,  -- 0=Planilla, 1=Externo
        activo       BIT          NOT NULL DEFAULT 1,
        CONSTRAINT PK_cliente     PRIMARY KEY (id_cliente),
        CONSTRAINT UQ_cliente_doc UNIQUE (dni_ruc)
    );
    CREATE INDEX IX_cliente_nombre ON dbo.cliente (nombre_rs);
END;
GO

-- 10. voucher_deposito (con BLOB para comprobante adjunto)
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='voucher_deposito')
BEGIN
    CREATE TABLE dbo.voucher_deposito (
        id_voucher     INT            IDENTITY(1,1) NOT NULL,
        num_operacion  VARCHAR(50)    NOT NULL,
        monto_total    DECIMAL(12,2)  NOT NULL,
        fecha_deposito DATE           NOT NULL,
        url_imagen     VARCHAR(255)   NULL,        -- Nombre original del archivo
        archivo_blob   VARBINARY(MAX) NULL,        -- Archivo binario (PDF/imagen)
        activo         BIT            NOT NULL DEFAULT 1,
        fecha_creacion DATETIME       NOT NULL DEFAULT GETDATE(),
        CONSTRAINT PK_voucher_deposito PRIMARY KEY (id_voucher)
    );
END;
GO

-- 11. transaccion (encabezado de ventas/proformas)
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='transaccion')
BEGIN
    CREATE TABLE dbo.transaccion (
        id_transaccion          INT           IDENTITY(1,1) NOT NULL,
        id_cliente              INT           NOT NULL,
        id_centro               INT           NULL,
        id_voucher              INT           NULL,
        responsable_venta       VARCHAR(150)  NULL,
        tipo_op                 VARCHAR(20)   NOT NULL DEFAULT 'VENTA',
        metodo_pago             VARCHAR(20)   NULL,       -- 'VENTA'|'DONACION'
        estado                  VARCHAR(20)   NULL,       -- NULL|'PENDIENTE'|'PROCESADO'|'RECHAZADO'
        fecha_creacion          DATETIME      NOT NULL DEFAULT GETDATE(),
        total                   DECIMAL(12,2) NOT NULL DEFAULT 0,
        serie_comprobante       VARCHAR(10)   NULL,
        correlativo_comprobante VARCHAR(20)   NULL,
        doc_justificante        VARCHAR(100)  NULL,
        descuento_planilla      BIT           NULL DEFAULT 0,
        num_grupo               VARCHAR(50)   NULL,
        CONSTRAINT PK_transaccion          PRIMARY KEY (id_transaccion),
        CONSTRAINT FK_trans_cliente        FOREIGN KEY (id_cliente) REFERENCES dbo.cliente(id_cliente),
        CONSTRAINT FK_trans_centro         FOREIGN KEY (id_centro)  REFERENCES dbo.centro_produccion(id_centro),
        CONSTRAINT FK_trans_voucher        FOREIGN KEY (id_voucher) REFERENCES dbo.voucher_deposito(id_voucher),
        CONSTRAINT CK_transaccion_metodo_pago CHECK (metodo_pago IN ('VENTA','DONACION'))
    );
    CREATE INDEX IX_trans_cliente_estado ON dbo.transaccion (id_cliente, estado, fecha_creacion DESC);
    CREATE INDEX IX_trans_fecha          ON dbo.transaccion (fecha_creacion DESC);
END;
GO

-- 12. transaccion_detalle
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='transaccion_detalle')
BEGIN
    CREATE TABLE dbo.transaccion_detalle (
        id_detalle      INT           IDENTITY(1,1) NOT NULL,
        id_transaccion  INT           NOT NULL,
        id_producto     INT           NOT NULL,
        id_lote         INT           NULL,
        cantidad        DECIMAL(10,3) NOT NULL,
        precio_unitario DECIMAL(10,4) NOT NULL,
        subtotal        DECIMAL(12,2) NOT NULL,
        CONSTRAINT PK_transaccion_detalle PRIMARY KEY (id_detalle),
        CONSTRAINT FK_td_transaccion      FOREIGN KEY (id_transaccion) REFERENCES dbo.transaccion(id_transaccion),
        CONSTRAINT FK_td_producto         FOREIGN KEY (id_producto)    REFERENCES dbo.producto(id_producto),
        CONSTRAINT FK_td_lote             FOREIGN KEY (id_lote)        REFERENCES dbo.lote(id_lote)
    );
    CREATE INDEX IX_td_transaccion ON dbo.transaccion_detalle (id_transaccion);
END;
GO

-- 13. dashboard_config
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='dashboard_config')
BEGIN
    CREATE TABLE dbo.dashboard_config (
        id_config          INT           IDENTITY(1,1) NOT NULL,
        usuario_id         INT           NOT NULL,
        posicion           INT           NOT NULL DEFAULT 0,
        widget_tipo        VARCHAR(50)   NOT NULL,
        widget_titulo      VARCHAR(100)  NULL,
        widget_tamano      VARCHAR(20)   NOT NULL DEFAULT 'medium',
        widget_config      NVARCHAR(MAX) NULL,
        activo             BIT           NOT NULL DEFAULT 1,
        fecha_creacion     DATETIME      NOT NULL DEFAULT GETDATE(),
        fecha_modificacion DATETIME      NOT NULL DEFAULT GETDATE(),
        CONSTRAINT PK_dashboard_config PRIMARY KEY (id_config)
    );
    CREATE INDEX IX_dashboard_config_usuario ON dbo.dashboard_config (usuario_id, activo, posicion);
END;
GO

-- FIN DEL ESQUEMA
-- Para parches incrementales, ver alter_*.sql en esta misma carpeta.
