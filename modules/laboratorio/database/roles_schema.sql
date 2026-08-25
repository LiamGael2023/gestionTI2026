-- ============================================================
-- laboratorio/database/roles_schema.sql
-- Replica el esquema del sistema de roles y permisos del módulo
-- Laboratorio (schema: laboratorio)
--
-- Uso: ejecutar manualmente contra BD_GESTION_TI (SQL Server)
--       Script idempotente: solo crea tablas y PKs/FKs que no existan.
-- Nota: los permisos USE/SCHEMA deben estar creados previamente.
-- ============================================================

-- ------------------------------------------------------------------
-- 1) laboratorio.Rol  — Roles de laboratorio (Encargado, Analista Jefe, ...)
-- ------------------------------------------------------------------
IF OBJECT_ID('laboratorio.Rol', 'U') IS NULL
BEGIN
    CREATE TABLE laboratorio.Rol (
        Id_Rol           INT IDENTITY(1,1) NOT NULL,
        Nombre           NVARCHAR(100)     NOT NULL,
        Descripcion      NVARCHAR(MAX)     NULL,
        Activo           BIT               NOT NULL DEFAULT 1,
        Fecha_Creacion   DATETIME          NOT NULL DEFAULT GETDATE(),
        Usuario_Creacion INT               NULL,
        CONSTRAINT PK_Rol PRIMARY KEY (Id_Rol)
    );
END

-- ------------------------------------------------------------------
-- 2) laboratorio.Submodulo — Submódulos del laboratorio (equipo, reactivo, ...)
-- ------------------------------------------------------------------
IF OBJECT_ID('laboratorio.Submodulo', 'U') IS NULL
BEGIN
    CREATE TABLE laboratorio.Submodulo (
        Id_Submodulo INT IDENTITY(1,1) NOT NULL,
        Nombre       NVARCHAR(100)  NOT NULL,
        Icono        NVARCHAR(50)   NULL,
        Descripcion  NVARCHAR(MAX)  NULL,
        Url          NVARCHAR(200)  NULL,
        Activo       BIT            NOT NULL DEFAULT 1,
        CONSTRAINT PK_Submodulo PRIMARY KEY (Id_Submodulo)
    );
END

-- ------------------------------------------------------------------
-- 3) laboratorio.Permiso_Rol — Matriz de permisos por rol y submódulo
--    (Pueden_* = flags de ver/crear/editar/eliminar/exportar/firmar)
-- ------------------------------------------------------------------
IF OBJECT_ID('laboratorio.Permiso_Rol', 'U') IS NULL
BEGIN
    CREATE TABLE laboratorio.Permiso_Rol (
        Id_Permiso_Rol   INT IDENTITY(1,1) NOT NULL,
        Id_Rol           INT NOT NULL,
        Id_Submodulo     INT NOT NULL,
        Pueden_Ver       BIT NOT NULL DEFAULT 0,
        Pueden_Crear     BIT NOT NULL DEFAULT 0,
        Pueden_Editar    BIT NOT NULL DEFAULT 0,
        Pueden_Eliminar  BIT NOT NULL DEFAULT 0,
        Pueden_Exportar  BIT NOT NULL DEFAULT 0,
        Pueden_Firmar    BIT NOT NULL DEFAULT 0,
        Activo           BIT NOT NULL DEFAULT 1,
        Usuario_Creacion INT NULL,
        CONSTRAINT PK_Permiso_Rol PRIMARY KEY (Id_Permiso_Rol),
        CONSTRAINT FK_Permiso_Rol_Rol       FOREIGN KEY (Id_Rol)       REFERENCES laboratorio.Rol (Id_Rol),
        CONSTRAINT FK_Permiso_Rol_Submodulo FOREIGN KEY (Id_Submodulo) REFERENCES laboratorio.Submodulo (Id_Submodulo)
    );
END

-- ------------------------------------------------------------------
-- 4) laboratorio.Usuario_Rol — Asignación de rol de laboratorio a un usuario comun
-- ------------------------------------------------------------------
IF OBJECT_ID('laboratorio.Usuario_Rol', 'U') IS NULL
BEGIN
    CREATE TABLE laboratorio.Usuario_Rol (
        Id_Usuario_Rol    INT IDENTITY(1,1) NOT NULL,
        Id_Usuario        INT NOT NULL,               -- FK -> comun.Usuarios (id_usuario)
        Id_Rol            INT NOT NULL,
        Fecha_Asignacion  DATETIME NOT NULL DEFAULT GETDATE(),
        Usuario_Asignador INT NULL,
        CONSTRAINT PK_Usuario_Rol PRIMARY KEY (Id_Usuario_Rol),
        CONSTRAINT FK_Usuario_Rol_Rol FOREIGN KEY (Id_Rol) REFERENCES laboratorio.Rol (Id_Rol)
    );
    -- Un usuario solo puede tener un rol de laboratorio activo
    CREATE UNIQUE INDEX UQ_Usuario_Rol_Usuario ON laboratorio.Usuario_Rol (Id_Usuario);
END

-- ------------------------------------------------------------------
-- 5) laboratorio.Usuario_Lab_Firma — Firma digital del usuario (base64 NVARCHAR(MAX))
-- ------------------------------------------------------------------
IF OBJECT_ID('laboratorio.Usuario_Lab_Firma', 'U') IS NULL
BEGIN
    CREATE TABLE laboratorio.Usuario_Lab_Firma (
        Id_Usuario          INT NOT NULL,              -- FK -> comun.Usuarios (id_usuario)
        Img_Firma           NVARCHAR(MAX) NULL,
        Activo              BIT           NOT NULL DEFAULT 1,
        Fecha_Creacion      DATETIME      NOT NULL DEFAULT GETDATE(),
        Fecha_Modificacion  DATETIME      NULL,
        Usuario_Creacion    INT           NULL,
        CONSTRAINT PK_Usuario_Lab_Firma PRIMARY KEY (Id_Usuario)
    );
END

-- ------------------------------------------------------------------
-- 6) Índices recomendados
-- ------------------------------------------------------------------
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_Permiso_Rol_Rol_Sub' AND object_id = OBJECT_ID('laboratorio.Permiso_Rol'))
    CREATE NONCLUSTERED INDEX IX_Permiso_Rol_Rol_Sub ON laboratorio.Permiso_Rol (Id_Rol, Id_Submodulo) INCLUDE (Pueden_Ver, Pueden_Crear, Pueden_Editar, Pueden_Eliminar, Pueden_Exportar, Pueden_Firmar);