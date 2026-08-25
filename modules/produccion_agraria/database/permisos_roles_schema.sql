-- ============================================================
-- produccion_agraria/database/permisos_roles_schema.sql
-- Sistema de Roles y Permisos del módulo Producción Agraria
--
-- Tablas creadas en BD_PRODUCCIONDESARROLLO.dbo
-- Script idempotente: solo crea lo que no existe.
-- ============================================================

-- ------------------------------------------------------------------
-- 1) rol_pa — Roles de Producción Agraria (Encargado, Operador, ...)
-- ------------------------------------------------------------------
IF OBJECT_ID('BD_PRODUCCIONDESARROLLO.dbo.rol_pa', 'U') IS NULL
BEGIN
    CREATE TABLE BD_PRODUCCIONDESARROLLO.dbo.rol_pa (
        Id_Rol_PA        INT IDENTITY(1,1) NOT NULL,
        Nombre           NVARCHAR(100)     NOT NULL,
        Descripcion      NVARCHAR(MAX)     NULL,
        Activo           BIT               NOT NULL DEFAULT 1,
        Fecha_Creacion   DATETIME          NOT NULL DEFAULT GETDATE(),
        Usuario_Creacion INT               NULL,
        CONSTRAINT PK_rol_pa PRIMARY KEY (Id_Rol_PA)
    );
END

-- ------------------------------------------------------------------
-- 2) submodulo_pa — Submódulos de Producción Agraria (inventario, ...)
-- ------------------------------------------------------------------
IF OBJECT_ID('BD_PRODUCCIONDESARROLLO.dbo.submodulo_pa', 'U') IS NULL
BEGIN
    CREATE TABLE BD_PRODUCCIONDESARROLLO.dbo.submodulo_pa (
        Id_Submodulo_PA INT IDENTITY(1,1) NOT NULL,
        Nombre          NVARCHAR(100)  NOT NULL,
        Icono           NVARCHAR(50)   NULL,
        Descripcion     NVARCHAR(MAX)  NULL,
        Url             NVARCHAR(200)  NULL,
        Activo          BIT            NOT NULL DEFAULT 1,
        CONSTRAINT PK_submodulo_pa PRIMARY KEY (Id_Submodulo_PA)
    );
END

-- ------------------------------------------------------------------
-- 3) permiso_rol_pa — Matriz de permisos por rol y submódulo
--    NOTA: el sistema solo gestiona VISIBILIDAD (Pueden_Ver).
--    Las demás flags (Crear/Editar/Eliminar/Exportar/Firmar) se dejan
--    en 0 y no se usan: los permisos solo muestran u ocultan submódulos.
-- ------------------------------------------------------------------
IF OBJECT_ID('BD_PRODUCCIONDESARROLLO.dbo.permiso_rol_pa', 'U') IS NULL
BEGIN
    CREATE TABLE BD_PRODUCCIONDESARROLLO.dbo.permiso_rol_pa (
        Id_Permiso_Rol_PA INT IDENTITY(1,1) NOT NULL,
        Id_Rol_PA         INT NOT NULL,
        Id_Submodulo_PA   INT NOT NULL,
        Pueden_Ver        BIT NOT NULL DEFAULT 0,
        Pueden_Crear      BIT NOT NULL DEFAULT 0,
        Pueden_Editar     BIT NOT NULL DEFAULT 0,
        Pueden_Eliminar   BIT NOT NULL DEFAULT 0,
        Pueden_Exportar   BIT NOT NULL DEFAULT 0,
        Pueden_Firmar     BIT NOT NULL DEFAULT 0,
        Activo            BIT NOT NULL DEFAULT 1,
        Usuario_Creacion  INT NULL,
        CONSTRAINT PK_permiso_rol_pa PRIMARY KEY (Id_Permiso_Rol_PA),
        CONSTRAINT FK_permiso_rol_pa_rol       FOREIGN KEY (Id_Rol_PA)       REFERENCES BD_PRODUCCIONDESARROLLO.dbo.rol_pa (Id_Rol_PA),
        CONSTRAINT FK_permiso_rol_pa_submodulo FOREIGN KEY (Id_Submodulo_PA) REFERENCES BD_PRODUCCIONDESARROLLO.dbo.submodulo_pa (Id_Submodulo_PA)
    );
END

-- ------------------------------------------------------------------
-- 4) usuario_rol_pa — Asignación de rol de PA a un usuario comun
-- ------------------------------------------------------------------
IF OBJECT_ID('BD_PRODUCCIONDESARROLLO.dbo.usuario_rol_pa', 'U') IS NULL
BEGIN
    CREATE TABLE BD_PRODUCCIONDESARROLLO.dbo.usuario_rol_pa (
        Id_Usuario_Rol_PA INT IDENTITY(1,1) NOT NULL,
        Id_Usuario        INT NOT NULL,               -- FK -> comun.Usuarios
        Id_Rol_PA         INT NOT NULL,
        Fecha_Asignacion  DATETIME NOT NULL DEFAULT GETDATE(),
        Usuario_Asignador INT NULL,
        CONSTRAINT PK_usuario_rol_pa PRIMARY KEY (Id_Usuario_Rol_PA),
        CONSTRAINT FK_usuario_rol_pa_rol FOREIGN KEY (Id_Rol_PA) REFERENCES BD_PRODUCCIONDESARROLLO.dbo.rol_pa (Id_Rol_PA)
    );
    CREATE UNIQUE INDEX UQ_usuario_rol_pa_usuario ON BD_PRODUCCIONDESARROLLO.dbo.usuario_rol_pa (Id_Usuario);
END

-- ------------------------------------------------------------------
-- 5) Índice recomendado para la matriz
-- ------------------------------------------------------------------
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_permiso_rol_pa_rol_sub' AND object_id = OBJECT_ID('BD_PRODUCCIONDESARROLLO.dbo.permiso_rol_pa'))
    CREATE NONCLUSTERED INDEX IX_permiso_rol_pa_rol_sub ON BD_PRODUCCIONDESARROLLO.dbo.permiso_rol_pa (Id_Rol_PA, Id_Submodulo_PA)
        INCLUDE (Pueden_Ver, Pueden_Crear, Pueden_Editar, Pueden_Eliminar, Pueden_Exportar, Pueden_Firmar);

-- ------------------------------------------------------------------
-- 6) Seed de submódulos de Producción Agraria (idempotente)
--    (se ignora si ya existen)
-- ------------------------------------------------------------------
IF NOT EXISTS (SELECT 1 FROM BD_PRODUCCIONDESARROLLO.dbo.submodulo_pa WHERE Url = '?module=produccion_agraria&action=inventario')
    INSERT INTO BD_PRODUCCIONDESARROLLO.dbo.submodulo_pa (Nombre, Icono, Descripcion, Url, Activo) VALUES
    (N'Inventario',   N'package',        N'Gestión de productos, lotes y existencias',           N'?module=produccion_agraria&action=inventario',   1),
    (N'Punto de Venta', N'shopping-cart', N'Registro de ventas y proformas',                    N'?module=produccion_agraria&action=punto_venta',  1),
    (N'Bandeja',      N'inbox',          N'Proformas pendientes y vouchers',                    N'?module=produccion_agraria&action=bandeja',      1),
    (N'Tablas',       N'table',          N'Catálogos y datos maestros',                         N'?module=produccion_agraria&action=tablas',       1),
    (N'Reportes',     N'file-report',    N'Informes y estadísticas',                            N'?module=produccion_agraria&action=reportes',     1),
    (N'Dashboard',    N'dashboard',      N'Panel de indicadores',                               N'?module=produccion_agraria&action=dashboard',    1),
    (N'Consultas IA', N'message',        N'Chatbot de consultas',                               N'?module=produccion_agraria&action=consultas',    1);