-- 1. Tabla Catastro_Pozo (Clonada)
CREATE TABLE laboratorio.Catastro_Pozo (
    Id_Pozo VARCHAR(15) PRIMARY KEY,       -- Consistencia en formato (Mayúsculas)
    codigopech VARCHAR(25),
    codigo VARCHAR(100),
    valle VARCHAR(30),
    geom VARCHAR(MAX),
    coord_este FLOAT,                      -- Corregido
    coord_norte FLOAT,                     -- Corregido
    zona FLOAT,
    cota FLOAT,
    departamento VARCHAR(25),
    provincia VARCHAR(25),
    distrito VARCHAR(25),
    ubicacion VARCHAR(50),
    aaa VARCHAR(35),
    ala VARCHAR(35),
    uh VARCHAR(35),
    fechainventario DATE,
    propietario VARCHAR(80),
    tipopozo VARCHAR(25),
    pr FLOAT,
    foto_pozo VARCHAR(MAX),
    Fecha_Sincronizacion DATETIME DEFAULT GETDATE()
);
GO

-- 2. Tabla de Asignaciones
CREATE TABLE laboratorio.Monitoreo_Pozo_Asignacion (
    Id_Asignacion INT IDENTITY(1,1) PRIMARY KEY,
    Id_Proyecto INT NOT NULL,
    Id_Pozo VARCHAR(15) NOT NULL,          -- Hace match exacto con el PK
    Numero_Muestra INT NOT NULL,
    Orden INT NULL,                         -- Ronda de monitoreo (viene de calidad_agua_laboratorio.orden)
    Es_Analisis_Laboratorio BIT DEFAULT 0,
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),

    CONSTRAINT FK_MonAsig_Proy FOREIGN KEY (Id_Proyecto) 
        REFERENCES laboratorio.Proyecto_Monitoreo(Id_Proyecto),
    CONSTRAINT FK_MonAsig_Pozo FOREIGN KEY (Id_Pozo) 
        REFERENCES laboratorio.Catastro_Pozo(Id_Pozo)
);
GO

-- 3. Restricción (Índice Único) - unico por (Proyecto, Orden, Muestra)
CREATE UNIQUE INDEX IX_MonAsig_Activa_Orden
ON laboratorio.Monitoreo_Pozo_Asignacion(Id_Proyecto, Orden, Numero_Muestra)
WHERE Activo = 1;
GO

-- 4. Modificaciones a tablas existentes
ALTER TABLE laboratorio.Proyecto_Monitoreo
ADD Es_Pozos BIT DEFAULT 0;
GO

ALTER TABLE laboratorio.Muestra_Lab
ADD Id_Pozo VARCHAR(15) NULL;
GO

ALTER TABLE laboratorio.Muestra_Lab
ADD CONSTRAINT FK_MueLab_CatastroPozo 
FOREIGN KEY (Id_Pozo) REFERENCES laboratorio.Catastro_Pozo(Id_Pozo);
GO 

-- Relación directa Muestra ↔ Asignacion (trazabilidad de análisis por pozo)
ALTER TABLE laboratorio.Muestra_Lab
ADD Id_Asignacion INT NULL;
GO

ALTER TABLE laboratorio.Muestra_Lab
ADD CONSTRAINT FK_Muestra_Asignacion 
FOREIGN KEY (Id_Asignacion) REFERENCES laboratorio.Monitoreo_Pozo_Asignacion(Id_Asignacion);
GO

-- 5. Nuevas columnas y tabla para Flujo Progresivo de Pozos
ALTER TABLE laboratorio.Parametro_Analisis
ADD Posgre_Nombre NVARCHAR(100) NULL;
GO

ALTER TABLE laboratorio.Proyecto_Monitoreo
ADD Id_Proyecto_Pozos_Origen INT NULL;
GO

ALTER TABLE laboratorio.Proyecto_Monitoreo
ADD CONSTRAINT FK_ProyOrigen_Proy 
FOREIGN KEY (Id_Proyecto_Pozos_Origen) REFERENCES laboratorio.Proyecto_Monitoreo(Id_Proyecto);
GO

ALTER TABLE laboratorio.Monitoreo_Pozo_Asignacion
ADD Id_Producto_Lab INT NULL;
GO

ALTER TABLE laboratorio.Monitoreo_Pozo_Asignacion
ADD CONSTRAINT FK_MonAsig_ProdLab 
FOREIGN KEY (Id_Producto_Lab) REFERENCES laboratorio.Producto_Venta(Id_Producto);
GO

ALTER TABLE laboratorio.Muestra_Lab
ADD Lab_Habilitado BIT DEFAULT 0;
GO

CREATE TABLE laboratorio.Consumo_Resultado (
    Id_Consumo_Resultado INT IDENTITY(1,1) PRIMARY KEY,
    Id_Resultado INT NOT NULL CONSTRAINT FK_ConRes_Res FOREIGN KEY REFERENCES laboratorio.Resultado_Analisis(Id_Resultado),
    Id_Movimiento INT NOT NULL CONSTRAINT FK_ConRes_Mov FOREIGN KEY REFERENCES laboratorio.Movimiento_Kardex(Id_Movimiento),
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Usuario_Creacion INT
);
GO

Y planea la conexión con la base de datos que será en postgresql tambien, considera que todo lo que tengo en mi base de datos es esto, aunque no a la perfeccion, porque fue cambiando
-- =============================================
-- 0. CREACIÓN DEL ESQUEMA
-- =============================================
IF NOT EXISTS (SELECT * FROM sys.schemas WHERE name = 'laboratorio')
BEGIN
    EXEC('CREATE SCHEMA laboratorio')
END
GO

-- =============================================
-- 1. TABLAS DE SEGURIDAD (EXTENSIÓN)
-- =============================================

-- Esta tabla guarda la firma que no está en comun.Usuarios
CREATE TABLE laboratorio.Usuario_Lab_Firma (
    Id_Usuario INT PRIMARY KEY CONSTRAINT FK_Firm_User FOREIGN KEY REFERENCES comun.Usuarios(id_usuario),
    Img_Firma NVARCHAR(MAX) NULL,
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_Firm_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

-- 1. Catálogo de Sub-módulos
-- Aquí registrarás tus 9 secciones (Muestras, Equipos, Reactivos, etc.)
CREATE TABLE laboratorio.Submodulo (
    Id_Submodulo INT IDENTITY(1,1) PRIMARY KEY,
    Nombre NVARCHAR(100) NOT NULL,
	Descripcion NVARCHAR(255),
    Url NVARCHAR(255),
    Icono NVARCHAR(50), -- Nombre del icono (ej: 'flask', 'settings')
    Activo BIT DEFAULT 1
);
GO

INSERT INTO laboratorio.Submodulo (Nombre, Icono, Descripcion, Url, Activo)
VALUES 
('Equipos', 'microscope', 'Control de Equipos', '?module=laboratorio&action=equipo', 1),
('Reactivos', 'flask', 'Control de Reactivos', '?module=laboratorio&action=reactivo', 1),
('Parámetros', 'binary', 'Control de Parámetros', '?module=laboratorio&action=parametro', 1),
('Servicios', 'stethoscope', 'Control de Servicios', '?module=laboratorio&action=servicio', 1),
('Paquetes', 'archive', 'Control de Paquetes de Servicios', '?module=laboratorio&action=venta', 1),
('Muestras', 'test-pipe', 'Control de Muestras', '?module=laboratorio&action=muestra', 1),
('Residuos', 'trash', 'Control de Residuos', '?module=laboratorio&action=residuo', 1),
('Proveedores / Clientes', 'building', 'Gestión de Proveedores y Clientes', '?module=laboratorio&action=proveedor', 1),
('Reportes', 'report-analytics', 'Reportes de Residuos, Muestras y Kardex', '?module=laboratorio&action=reportes', 1);
GO

-- 2. Roles del Laboratorio
-- Perfiles específicos para tu área
CREATE TABLE laboratorio.Rol (
    Id_Rol INT IDENTITY(1,1) PRIMARY KEY,
    Nombre NVARCHAR(100) NOT NULL, -- Ej: 'Analista de Suelos', 'Visualizador'
    Descripcion NVARCHAR(MAX),
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Usuario_Creacion INT CONSTRAINT FK_LabRol_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

INSERT INTO laboratorio.Rol (Nombre, Descripcion, Usuario_Creacion)
VALUES 
('Encargado de Laboratorio', 'Administrador total del área de laboratorio', 1),
('Analista Jefe', 'Supervisión operativa de muestras, reactivos y parámetros', 1),
('Asistente Analista', 'Registro operativo básico de muestras y uso de equipos', 1);
GO

-- 3. Permisos Detallados (La clave de tu requerimiento)
-- Esta tabla decide qué Rol ve qué Sub-módulo
CREATE TABLE laboratorio.Permiso_Rol (
    Id_Permiso_Rol INT IDENTITY(1,1) PRIMARY KEY,
    Id_Rol INT NOT NULL CONSTRAINT FK_LabPerm_Rol FOREIGN KEY REFERENCES laboratorio.Rol(Id_Rol),
    Id_Submodulo INT NOT NULL CONSTRAINT FK_LabPerm_Sub FOREIGN KEY REFERENCES laboratorio.Submodulo(Id_Submodulo),
    Pueden_Ver BIT DEFAULT 0,
    Pueden_Crear BIT DEFAULT 0,
    Pueden_Editar BIT DEFAULT 0,
    Pueden_Eliminar BIT DEFAULT 0,
    Pueden_Exportar BIT DEFAULT 0,
    Activo BIT DEFAULT 1,
    Usuario_Creacion INT CONSTRAINT FK_LabPerm_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

-- Le damos el permiso de firma al Encargado (1) y al Analista Jefe (2)
-- para el submódulo de Muestras (Id_Submodulo = 6)
UPDATE laboratorio.Permiso_Rol
SET Pueden_Firmar = 1
WHERE Id_Rol IN (1, 2) AND Id_Submodulo = 6;
GO

-- Le damos acceso a los 9 submódulos con todos los permisos (CRUD completo)
INSERT INTO laboratorio.Permiso_Rol (Id_Rol, Id_Submodulo, Pueden_Ver, Pueden_Crear, Pueden_Editar, Pueden_Eliminar, Usuario_Creacion)
SELECT 
    1, -- ID del Encargado
    Id_Submodulo, 
    1, 1, 1, 1, 
    1  -- ID de Usuario que crea
FROM laboratorio.Submodulo;
GO

-- Submódulos: 1(Equipos), 2(Reactivos), 3(Parámetros), 6(Muestras), 7(Residuos), 9(Reportes)
INSERT INTO laboratorio.Permiso_Rol (Id_Rol, Id_Submodulo, Pueden_Ver, Pueden_Crear, Pueden_Editar, Pueden_Eliminar, Usuario_Creacion)
VALUES 
(2, 1, 1, 1, 1, 0, 1), -- Equipos (Sin permiso para eliminar)
(2, 2, 1, 1, 1, 0, 1), -- Reactivos
(2, 3, 1, 1, 1, 0, 1), -- Parámetros
(2, 6, 1, 1, 1, 1, 1), -- Muestras (Sí puede eliminar/anular)
(2, 7, 1, 1, 1, 0, 1), -- Residuos
(2, 9, 1, 0, 0, 0, 1); -- Reportes (Solo ver, no aplica crear/editar)
GO

-- Submódulos: 1(Equipos), 2(Reactivos), 6(Muestras)
INSERT INTO laboratorio.Permiso_Rol (Id_Rol, Id_Submodulo, Pueden_Ver, Pueden_Crear, Pueden_Editar, Pueden_Eliminar, Usuario_Creacion)
VALUES 
(3, 1, 1, 0, 0, 0, 1), -- Equipos (Solo ver qué equipos hay)
(3, 2, 1, 0, 1, 0, 1), -- Reactivos (Ver y editar para registrar consumo)
(3, 6, 1, 1, 1, 0, 1); -- Muestras (Ver, crear y editar sus resultados, pero NO eliminar)
GO

ALTER TABLE laboratorio.Permiso_Rol
ADD Pueden_Firmar BIT DEFAULT 0;
GO

ALTER TABLE laboratorio.Muestra_Lab
ADD Es_Drene BIT DEFAULT 0;
GO

-- 4. Asignación de Roles a Usuarios
-- Vincula a un usuario de tu tabla 'comun.Usuarios' con un rol de laboratorio
CREATE TABLE laboratorio.Usuario_Rol (
    Id_Usuario_Rol INT IDENTITY(1,1) PRIMARY KEY,
    Id_Usuario INT NOT NULL CONSTRAINT FK_LabUR_User FOREIGN KEY REFERENCES comun.Usuarios(id_usuario),
    Id_Rol INT NOT NULL CONSTRAINT FK_LabUR_Rol FOREIGN KEY REFERENCES laboratorio.Rol(Id_Rol),
    Fecha_Asignacion DATETIME DEFAULT GETDATE(),
    Usuario_Asignador INT CONSTRAINT FK_LabUR_Admin FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Unidad_Medida (
    Id_Unidad_Medida INT IDENTITY(1,1) PRIMARY KEY,
    Nombre NVARCHAR(50) NOT NULL,    -- Ejemplo: 'Kilogramos', 'Litros', 'Mililitros'
    Abreviatura NVARCHAR(10) NOT NULL, -- Ejemplo: 'kg', 'L', 'ml'
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Usuario_Creacion INT CONSTRAINT FK_UMed_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

-- =============================================
-- 2. TABLAS COMERCIALES (CATÁLOGOS)
-- =============================================

CREATE TABLE laboratorio.Producto_Venta (
    Id_Producto INT IDENTITY(1,1) PRIMARY KEY,
    Nombre_Comercial NVARCHAR(100) NOT NULL,
    Precio_Venta DECIMAL(10, 2) NOT NULL,
    Tipo NVARCHAR(20) NOT NULL, 
	Descripcion NVARCHAR(255),
	Tipo_Vista NVARCHAR (10) CHECK (Tipo_Vista IN ('INTERNO', 'GENERAL')),
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_PVenta_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Proveedor (
    Id_Proveedor INT IDENTITY(1,1) PRIMARY KEY,
    Razon_Social NVARCHAR(150) NOT NULL,
    Ruc NVARCHAR(20) UNIQUE, -- Registro Único de Contribuyente
    Nombre_Contacto NVARCHAR(100),
    Telefono NVARCHAR(20),
    Email NVARCHAR(100),
    Direccion NVARCHAR(255),
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_Prov_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

ALTER TABLE laboratorio.Reactivo_Lab
ADD Id_Proveedor INT NULL;
GO

ALTER TABLE laboratorio.Reactivo_Lab
ADD CONSTRAINT FK_Reac_Prov 
FOREIGN KEY (Id_Proveedor) REFERENCES laboratorio.Proveedor(Id_Proveedor);
GO

-- 1. Añadimos la nueva columna de relación (FK)
ALTER TABLE laboratorio.Equipo_Lab
ADD Id_Proveedor INT NULL;
GO

-- 2. Creamos la relación formal
ALTER TABLE laboratorio.Equipo_Lab
ADD CONSTRAINT FK_Eq_Prov 
FOREIGN KEY (Id_Proveedor) REFERENCES laboratorio.Proveedor(Id_Proveedor);
GO

-- NOTA: Una vez que pases los nombres de la columna 'Proveedor' (texto) 
-- a la nueva tabla, podrías borrar la columna vieja para limpiar la tabla:
-- ALTER TABLE laboratorio.Equipo_Lab DROP COLUMN Proveedor;

CREATE TABLE laboratorio.Servicio_Tecnico (
    Id_Servicio INT IDENTITY(1,1) PRIMARY KEY,
    Nombre NVARCHAR(100) NOT NULL,      
    Descripcion NVARCHAR(255),
    Tipo_Muestra NVARCHAR (5) CHECK (Tipo_Muestra IN ('AGUA', 'SUELO')),
	Tipo_Vista NVARCHAR (10) CHECK (Tipo_Vista IN ('INTERNO', 'GENERAL')),
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_STec_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Producto_Servicio (
    Id_Producto_Servicio INT IDENTITY(1,1) PRIMARY KEY,
    Id_Producto INT NOT NULL CONSTRAINT FK_PServ_Prod FOREIGN KEY REFERENCES laboratorio.Producto_Venta(Id_Producto),
    Id_Servicio INT NOT NULL CONSTRAINT FK_PServ_Serv FOREIGN KEY REFERENCES laboratorio.Servicio_Tecnico(Id_Servicio),
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_PServ_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO
ALTER TABLE laboratorio.Producto_Servicio
DROP COLUMN Descripcion;
GO

-- =============================================
-- 3. TABLAS DE NORMATIVA Y PARÁMETROS
-- =============================================

CREATE TABLE laboratorio.Normativa_Legal (
    Id_Normativa INT IDENTITY(1,1) PRIMARY KEY,
    Nombre NVARCHAR(100) NOT NULL, 
    Descripcion NVARCHAR(255),
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_NormL_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Parametro_Analisis (
    Id_Parametro INT IDENTITY(1,1) PRIMARY KEY,
    Id_Servicio INT NOT NULL CONSTRAINT FK_Param_Serv FOREIGN KEY REFERENCES laboratorio.Servicio_Tecnico(Id_Servicio),
    Nombre NVARCHAR(100) NOT NULL, 
    Unidad_Medida NVARCHAR(20),     
    Categoria NVARCHAR(50),         
    Metodo_Utilizado NVARCHAR(100),
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_Param_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO
ALTER TABLE laboratorio.Parametro_Analisis
ALTER COLUMN Id_Servicio INT NULL;
GO

CREATE TABLE laboratorio.Limite_Legal (
    Id_Limite_Legal INT IDENTITY(1,1) PRIMARY KEY,
    Id_Parametro INT NOT NULL CONSTRAINT FK_Lim_Par FOREIGN KEY REFERENCES laboratorio.Parametro_Analisis(Id_Parametro),
    Id_Normativa INT NOT NULL CONSTRAINT FK_Lim_Nor FOREIGN KEY REFERENCES laboratorio.Normativa_Legal(Id_Normativa),
    Valor_Max DECIMAL(18, 4), 
    Valor_Min DECIMAL(18, 4),
    Unidad_Medida NVARCHAR(20),
	Descripcion NVARCHAR(50),
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_Lim_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

-- =============================================
-- 4. TABLAS DE INVENTARIO Y EQUIPOS
-- =============================================

-- 1. Añadimos la columna para la relación
ALTER TABLE laboratorio.Reactivo_Lab
ADD Id_Unidad_Medida INT NULL; -- Permitimos NULL inicialmente para no romper datos existentes
GO

-- 2. Creamos la llave foránea
ALTER TABLE laboratorio.Reactivo_Lab
ADD CONSTRAINT FK_Reac_UnidadMedida 
FOREIGN KEY (Id_Unidad_Medida) REFERENCES laboratorio.Unidad_Medida(Id_Unidad_Medida);
GO

-- NOTA: Una vez configurado, podrías eliminar la columna antigua:
-- ALTER TABLE laboratorio.Reactivo_Lab DROP COLUMN Unidad_Medida;

CREATE TABLE laboratorio.Reactivo_Lab (
    Id_Reactivo INT IDENTITY(1,1) PRIMARY KEY,
    Nombre NVARCHAR(100) NOT NULL,
    Fecha_Vencimiento DATE,
	Tipo NVARCHAR (20),
    Cantidad_Stock DECIMAL(10, 4) DEFAULT 0,
    Cantidad_Reservada DECIMAL(10, 4) DEFAULT 0,
    Fecha_Ingreso DATE DEFAULT GETDATE(),
	Cantidad_Inicial DECIMAL(10, 4),
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_Reac_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

-- 1. Añadimos la columna para la relación
ALTER TABLE laboratorio.Residuo_Catalogo
ADD Id_Unidad_Medida INT NULL;
GO

-- 2. Creamos la llave foránea
ALTER TABLE laboratorio.Residuo_Catalogo
ADD CONSTRAINT FK_RCat_UnidadMedida 
FOREIGN KEY (Id_Unidad_Medida) REFERENCES laboratorio.Unidad_Medida(Id_Unidad_Medida);
GO

-- NOTA: Una vez configurado, podrías eliminar la columna antigua:
-- ALTER TABLE laboratorio.Residuo_Catalogo DROP COLUMN Unidad_Referencia;

CREATE TABLE laboratorio.Receta_Servicio (
    Id_Receta_Servicio INT IDENTITY(1,1) PRIMARY KEY,
    Id_Reactivo INT NOT NULL CONSTRAINT FK_Rec_Rea FOREIGN KEY REFERENCES laboratorio.Reactivo_Lab(Id_Reactivo),
    Id_Servicio INT NOT NULL CONSTRAINT FK_Rec_Ser FOREIGN KEY REFERENCES laboratorio.Servicio_Tecnico(Id_Servicio),
    Cantidad_Necesaria DECIMAL(10, 4), 
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_Rec_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Equipo_Estado (
    Id_Estado INT IDENTITY(1,1) PRIMARY KEY,
    Nombre NVARCHAR(50) NOT NULL, 
    Descripcion NVARCHAR(100),
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_EqE_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Equipo_Lab (
    Id_Equipo INT IDENTITY(1,1) PRIMARY KEY,
    Id_Estado INT NOT NULL CONSTRAINT FK_Eq_Est FOREIGN KEY REFERENCES laboratorio.Equipo_Estado(Id_Estado),
    Nombre NVARCHAR(100) NOT NULL,
    Proveedor NVARCHAR(100),
	Fecha_Adquisicion DATE,
    Fecha_Ultima_Calibracion DATE,
    Fecha_Proxima_Calibracion DATE,
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_Eq_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Observacion_Calibracion (
    Id_Observacion_Cal INT IDENTITY(1,1) PRIMARY KEY,
    Id_Equipo INT NOT NULL CONSTRAINT FK_ObsC_Eq FOREIGN KEY REFERENCES laboratorio.Equipo_Lab(Id_Equipo),
    Fecha_Observacion DATETIME DEFAULT GETDATE(),
    Observacion NVARCHAR(MAX) NOT NULL,
    
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_ObsC_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Requisito_Equipo (
    Id_Requisito_Equipo INT IDENTITY(1,1) PRIMARY KEY,
    Id_Servicio INT NOT NULL CONSTRAINT FK_Req_Ser FOREIGN KEY REFERENCES laboratorio.Servicio_Tecnico(Id_Servicio),
    Id_Equipo INT NOT NULL CONSTRAINT FK_Req_Eq FOREIGN KEY REFERENCES laboratorio.Equipo_Lab(Id_Equipo),
    Es_Bloqueante BIT DEFAULT 1, 
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_Req_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

-- =============================================
-- 5. TABLAS DE PROYECTOS Y MONITOREO
-- =============================================

CREATE TABLE laboratorio.Proyecto_Monitoreo (
    Id_Proyecto INT IDENTITY(1,1) PRIMARY KEY,
    Nombre_Proyecto NVARCHAR(150) NOT NULL,
    Valle NVARCHAR(50) NOT NULL,           
    Temporada NVARCHAR(20),                
    Fecha_Inicio DATE,
    Id_Responsable INT CONSTRAINT FK_Proy_Res FOREIGN KEY REFERENCES comun.Usuarios(id_usuario),
    Estado NVARCHAR(20) DEFAULT 'Planificado',
	Tipo_Muestra NVARCHAR(20),
    Uso_Agua NVARCHAR(100),
    Fuente_Agua NVARCHAR(100),
    Nivel_Agua NVARCHAR(100),
	Es_Control_Calidad BIT DEFAULT 0,
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_Proy_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO


CREATE TABLE laboratorio.Proyecto_Detalle_Analisis (
    Id_Detalle_Proyecto INT IDENTITY(1,1) PRIMARY KEY,
    Id_Proyecto INT NOT NULL CONSTRAINT FK_DetP_Pro FOREIGN KEY REFERENCES laboratorio.Proyecto_Monitoreo(Id_Proyecto),
    Id_Producto_Venta INT NOT NULL CONSTRAINT FK_DetP_ProV FOREIGN KEY REFERENCES laboratorio.Producto_Venta(Id_Producto),
    Cantidad_Planificada INT NOT NULL, 
    Cantidad_Ejecutada INT DEFAULT 0,  
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_DetP_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

-- =============================================
-- 6. TABLAS DE MUESTRAS Y FLUJO
-- =============================================

CREATE TABLE laboratorio.Muestra_Lab (
    Id_Muestra INT IDENTITY(1,1) PRIMARY KEY,
    Id_Cliente INT NULL CONSTRAINT FK_Mue_Cli FOREIGN KEY REFERENCES laboratorio.Cliente(Id_Cliente),
	Id_Receptor INT NULL CONSTRAINT FK_Mue_Rec FOREIGN KEY REFERENCES comun.Usuarios(id_usuario),
    Id_Especialista INT NULL CONSTRAINT FK_Mue_Esp FOREIGN KEY REFERENCES comun.Usuarios(id_usuario),
    Id_Proyecto INT NULL CONSTRAINT FK_Mue_Pro FOREIGN KEY REFERENCES laboratorio.Proyecto_Monitoreo(Id_Proyecto),
    Valle NVARCHAR(100),
    Eje_X NVARCHAR(50), 
    Eje_Y NVARCHAR(50),
    Fecha_Recepcion DATETIME NULL,
    Estado NVARCHAR(20), 
    Tipo_Servicio NVARCHAR(50),       
    Observacion_Muestra NVARCHAR(MAX),
    Ruta_Imagen NVARCHAR(MAX),
    Id_Jefe_Lab INT NULL CONSTRAINT FK_Mue_Jef FOREIGN KEY REFERENCES comun.Usuarios(id_usuario),
    Fecha_Validacion DATETIME NULL, 
    Es_Control_Calidad BIT DEFAULT 0,
	Fecha_Toma DATETIME DEFAULT GETDATE(),
	Fecha_Analisis DATETIME NULL,
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_Mue_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Cliente (
	Id_Cliente INT IDENTITY(1,1) PRIMARY KEY,
	Dni NVARCHAR(12) NULL,
	Nombres NVARCHAR (25) NOT NULL,
	Apellido_Paterno NVARCHAR (25) NOT NULL,
	Apellido_Materno NVARCHAR (25) NULL,
	Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE()
);
GO

CREATE TABLE laboratorio.Muestra_Producto (
    Id_Muestra_Producto INT IDENTITY(1,1) PRIMARY KEY,
    Id_Muestra INT NOT NULL CONSTRAINT FK_MPr_Mue FOREIGN KEY REFERENCES laboratorio.Muestra_Lab(Id_Muestra),
    Id_Producto_Venta INT NOT NULL CONSTRAINT FK_MPr_Pro FOREIGN KEY REFERENCES laboratorio.Producto_Venta(Id_Producto),
   -- Relación con el Cliente (Redundancia controlada para reportes rápidos)
    Id_Cliente INT NULL CONSTRAINT FK_MPr_Cli FOREIGN KEY REFERENCES laboratorio.Cliente(Id_Cliente),
   -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_MPr_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Detalle_Suelo (
    Id_Muestra INT PRIMARY KEY CONSTRAINT FK_DetS_Mue FOREIGN KEY REFERENCES laboratorio.Muestra_Lab(Id_Muestra),
    Fuente_Riego NVARCHAR(100),
    Profundidad NVARCHAR(50),
    Numero_Submuestras INT,
    Cantidad_Muestra NVARCHAR(50) DEFAULT '1 Kg',
	Cultivo_Anterior NVARCHAR(100),
	Cultivo_Implementado NVARCHAR(100),
	Cultivo_Por_Implementar NVARCHAR(100),
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_DSue_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Detalle_Agua (
    Id_Muestra INT PRIMARY KEY CONSTRAINT FK_DetA_Mue FOREIGN KEY REFERENCES laboratorio.Muestra_Lab(Id_Muestra),
    Uso_Agua NVARCHAR(100),
    Fuente_Agua NVARCHAR(100),
    Cantidad_Muestra NVARCHAR(50) DEFAULT '1 Litro',
	Nivel_Agua NVARCHAR(50),
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_DAgu_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Solicitud_Analisis (
    Id_Solicitud_Analisis INT IDENTITY(1,1) PRIMARY KEY,
    Id_Muestra INT NOT NULL CONSTRAINT FK_Sol_Mue FOREIGN KEY REFERENCES laboratorio.Muestra_Lab(Id_Muestra),
    Id_Servicio INT NOT NULL CONSTRAINT FK_Sol_Ser FOREIGN KEY REFERENCES laboratorio.Servicio_Tecnico(Id_Servicio),
    Id_Analista INT CONSTRAINT FK_Sol_Ana FOREIGN KEY REFERENCES comun.Usuarios(id_usuario),
    Estado NVARCHAR(20) DEFAULT 'Pendiente',
    Fecha_Asignacion DATETIME,
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_SolA_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Resultado_Analisis (
    Id_Resultado INT IDENTITY(1,1) PRIMARY KEY,
    Id_Solicitud_Analisis INT NOT NULL CONSTRAINT FK_Res_Sol FOREIGN KEY REFERENCES laboratorio.Solicitud_Analisis(Id_Solicitud_Analisis),
    Id_Parametro INT NOT NULL CONSTRAINT FK_Res_Par FOREIGN KEY REFERENCES laboratorio.Parametro_Analisis(Id_Parametro),
    Id_Normativa INT CONSTRAINT FK_Res_Nor FOREIGN KEY REFERENCES laboratorio.Normativa_Legal(Id_Normativa),
    Valor_Hallado DECIMAL(18, 4),
    Fecha_Emision DATETIME DEFAULT GETDATE(),
    Observacion NVARCHAR(255),
    Interpretacion NVARCHAR(50), 
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_ResA_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

-- =============================================
-- 7. TABLAS DE KARDEX Y MOVIMIENTOS
-- =============================================

CREATE TABLE laboratorio.Ingreso_Reactivo (
    Id_Ingreso INT IDENTITY(1,1) PRIMARY KEY,
    Id_Reactivo INT NOT NULL CONSTRAINT FK_Ing_Rea FOREIGN KEY REFERENCES laboratorio.Reactivo_Lab(Id_Reactivo),
    Id_Usuario INT NOT NULL CONSTRAINT FK_Ing_Use FOREIGN KEY REFERENCES comun.Usuarios(id_usuario),
    Cantidad DECIMAL(10, 2) NOT NULL,
    Fecha_Ingreso DATETIME DEFAULT GETDATE(),
    Factura_Referencia NVARCHAR(50),
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_IRea_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Movimiento_Kardex (
    Id_Movimiento INT IDENTITY(1,1) PRIMARY KEY,
    Id_Reactivo INT NOT NULL CONSTRAINT FK_Kar_Rea FOREIGN KEY REFERENCES laboratorio.Reactivo_Lab(Id_Reactivo),
    Fecha_Registro DATETIME DEFAULT GETDATE(),
    Tipo_Movimiento CHAR(1) CHECK (Tipo_Movimiento IN ('E', 'S')), 
    Cantidad DECIMAL(10, 4) NOT NULL,
    Concepto NVARCHAR(100), 
    Saldo_Resultante DECIMAL(10, 4), 
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_KarM_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Consumo_Reaccion (
    Id_Consumo INT IDENTITY(1,1) PRIMARY KEY,
    Id_Movimiento INT NOT NULL UNIQUE CONSTRAINT FK_Con_Kar FOREIGN KEY REFERENCES laboratorio.Movimiento_Kardex(Id_Movimiento),
    Id_Muestra_Producto INT NOT NULL CONSTRAINT FK_Con_MPr FOREIGN KEY REFERENCES laboratorio.Muestra_Producto(Id_Muestra_Producto),
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_CRea_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Ajuste_Inventario (
    Id_Ajuste INT IDENTITY(1,1) PRIMARY KEY,
    Id_Reactivo INT NOT NULL CONSTRAINT FK_Aju_Rea FOREIGN KEY REFERENCES laboratorio.Reactivo_Lab(Id_Reactivo),
    Id_Usuario INT NOT NULL CONSTRAINT FK_Aju_Use FOREIGN KEY REFERENCES comun.Usuarios(id_usuario),
    Tipo_Ajuste NVARCHAR(50) NOT NULL, 
    Cantidad DECIMAL(10, 4) NOT NULL,  
    Fecha_Ajuste DATETIME DEFAULT GETDATE(),
    Notas NVARCHAR(MAX),           
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_AInv_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

-- =============================================
-- 8. GESTIÓN DE RESIDUOS Y BITÁCORA
-- =============================================

CREATE TABLE laboratorio.Residuo_Catalogo (
    Id_Residuo_Cat INT IDENTITY(1,1) PRIMARY KEY,
    Codigo_Item INT UNIQUE,
    Nombre_Item NVARCHAR(100) NOT NULL,
    Tipo_Principal NVARCHAR(20) CHECK (Tipo_Principal IN ('PELIGROSO', 'NO PELIGROSO')),
    Subcategoria NVARCHAR(50),
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_RCat_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Normativa_SST (
    Id_Normativa_SST INT IDENTITY(1,1) PRIMARY KEY,
    Nombre_Ley NVARCHAR(100), 
    Descripcion NVARCHAR(MAX),
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_NSST_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Servicio_Residuo_Def (
    Id_Serv_Res INT IDENTITY(1,1) PRIMARY KEY,
    Id_Servicio INT NOT NULL CONSTRAINT FK_SRes_Ser FOREIGN KEY REFERENCES laboratorio.Servicio_Tecnico(Id_Servicio),
    Id_Residuo_Cat INT NOT NULL CONSTRAINT FK_SRes_RCa FOREIGN KEY REFERENCES laboratorio.Residuo_Catalogo(Id_Residuo_Cat),
    Cantidad_Estimada_Por_Muestra DECIMAL(10,4),
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_SRDef_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Registro_Residuos_Log (
    Id_Registro_Res INT IDENTITY(1,1) PRIMARY KEY,
    Mes INT,
    Anio INT,
    Ubicacion NVARCHAR(100), 
    Id_Responsable INT CONSTRAINT FK_RLog_Res FOREIGN KEY REFERENCES comun.Usuarios(id_usuario),
    Codigo_SST NVARCHAR(20) DEFAULT 'SST-16',
	Observacion NVARCHAR(100),
	-- Auditoria
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_RRLog_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);

CREATE TABLE laboratorio.Reporte_Normativa_Asociada (
    Id_Registro_Res INT NOT NULL,
    Id_Normativa_SST INT NOT NULL,
    Usuario_Creacion INT,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    
    -- La llave primaria es la combinación de ambos para no repetir la misma ley en el mismo reporte
    PRIMARY KEY (Id_Registro_Res, Id_Normativa_SST),
    
    CONSTRAINT FK_Link_Reporte FOREIGN KEY (Id_Registro_Res) 
        REFERENCES laboratorio.Registro_Residuos_Log(Id_Registro_Res),
    CONSTRAINT FK_Link_Normativa FOREIGN KEY (Id_Normativa_SST) 
        REFERENCES laboratorio.Normativa_SST(Id_Normativa_SST)
);
GO

CREATE TABLE laboratorio.Detalle_Residuos_Log (
    Id_Detalle_Res INT IDENTITY(1,1) PRIMARY KEY,
    Id_Registro_Res INT NOT NULL CONSTRAINT FK_DLog_Reg FOREIGN KEY REFERENCES laboratorio.Registro_Residuos_Log(Id_Registro_Res),
    Id_Residuo_Cat INT NOT NULL CONSTRAINT FK_DLog_RCa FOREIGN KEY REFERENCES laboratorio.Residuo_Catalogo(Id_Residuo_Cat),
    Fecha_Dia DATE NOT NULL,
    Peso_Valor DECIMAL(10,4) NOT NULL,
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_DRLog_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Bitacora_Control_PTA (
    Id_Bitacora INT IDENTITY(1,1) PRIMARY KEY,
    Fecha_Registro DATE NOT NULL,
    Turno NVARCHAR(10) CHECK (Turno IN ('Mañana', 'Tarde')), 
    Observacion_General NVARCHAR(MAX),
    Id_Responsable INT CONSTRAINT FK_Bit_Res FOREIGN KEY REFERENCES comun.Usuarios(id_usuario),
    -- Auditoría
    Activo BIT DEFAULT 1,
    Fecha_Creacion DATETIME DEFAULT GETDATE(),
    Fecha_Modificacion DATETIME NULL,
    Usuario_Creacion INT CONSTRAINT FK_BCPTA_UserC FOREIGN KEY REFERENCES comun.Usuarios(id_usuario)
);
GO

CREATE TABLE laboratorio.Muestra_Bitacora (
    Id_Muestra INT PRIMARY KEY,
    Id_Bitacora INT NOT NULL,
    Turno NVARCHAR(10),
    Punto_Toma NVARCHAR(100),
    Muestra_Original INT NULL, -- ID de la muestra física de donde proviene
    Id_Producto_Venta INT NULL, -- <--- El nuevo campo para la duplicación automática
    Ubicacion_Punto NVARCHAR(100),

    CONSTRAINT FK_MueBit_Muestra FOREIGN KEY (Id_Muestra) 
        REFERENCES laboratorio.Muestra_Lab(Id_Muestra),
    CONSTRAINT FK_MueBit_Bitacora FOREIGN KEY (Id_Bitacora) 
        REFERENCES laboratorio.Bitacora_Control_PTA(Id_Bitacora),
    CONSTRAINT FK_MueBit_Producto FOREIGN KEY (Id_Producto_Venta)
        REFERENCES laboratorio.Producto_Venta(Id_Producto_Venta)
);
GO 
ALTER TABLE Muestra_Lab
ADD 
    Turno NVARCHAR(10) CHECK (Turno IN ('Mañana', 'Tarde')), -- Diferencia los dos bloques del reporte
    Es_Control_Calidad BIT DEFAULT 0, -- Identifica si es la muestra original o el "duplicado"
    Id_Muestra_Original INT NULL, -- Vincula el duplicado con su origen
    CONSTRAINT FK_Muestra_Original FOREIGN KEY (Id_Muestra_Original) REFERENCES Muestra_Lab(Id_Muestra);
GO

CREATE TABLE Bitacora_Control_PTA (
    Id_Bitacora INT IDENTITY(1,1) PRIMARY KEY,
    Fecha_Registro DATE NOT NULL,
    Turno NVARCHAR(10) CHECK (Turno IN ('Mañana', 'Tarde')), --
    Observacion_General NVARCHAR(MAX),
    Id_Responsable INT,
    CONSTRAINT FK_Bitacora_User FOREIGN KEY (Id_Responsable) REFERENCES Usuario_Lab(Id_Usuario)
); 
  ALTER TABLE laboratorio.Proyecto_Monitoreo ADD Es_Drene BIT DEFAULT 0;

