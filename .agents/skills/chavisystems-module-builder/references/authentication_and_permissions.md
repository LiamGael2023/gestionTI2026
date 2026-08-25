# Autenticacion y Permisos en CHAVIsystems

Esta guia explica el sistema de autenticacion y control de permisos basado en sesiones PHP y las tablas `comun.Modulos` y `comun.Permisos` en SQL Server.

---

## 1. Flujo de Autenticacion

### Login

El proceso de login se maneja en `modules/auth/controllers/AuthController.php`:

1. El usuario envia credenciales desde `modules/auth/views/login.php`
2. `AuthController` valida contra la base de datos
3. Si es exitoso, `Auth::login($id, $nombre, $rol)` guarda en sesion:
   - `$_SESSION['usuario_id']`
   - `$_SESSION['usuario_nombre']`
   - `$_SESSION['usuario_rol']` (ej: `'ADMIN'`, `'USUARIO'`)
4. Redirige al dashboard

### Logout

```php
Auth::logout();
// Destruye la sesion y redirige a BASE_URL/login
```

### Verificacion de Sesion (`Auth::check()`)

Llamado en `index.php` antes de cargar cualquier modulo. Verifica:

1. Que `$_SESSION['usuario_id']` exista. Si no, redirige al login.
2. Que el usuario tenga permiso `pueden_ver = 1` para el modulo actual en `comun.Permisos`.
3. Excepciones: `dashboard`, `auth`, `perfil`, `login`, `logout` y assets son publicos.
4. Las peticiones AJAX (URL contiene `Ajax`) se permiten sin verificacion de modulo.
5. Si no tiene permiso, redirige a `dashboard?error=access_denied`.

---

## 2. Estructura de Permisos en Base de Datos

### Tabla `comun.Modulos`

```sql
CREATE TABLE comun.Modulos (
    id_modulo   INT IDENTITY(1,1) PRIMARY KEY,
    nombre      VARCHAR(50) NOT NULL,    -- Nombre interno (ej: 'transportes')
    etiqueta    VARCHAR(100) NOT NULL,   -- Nombre visible en menu (ej: 'Transportes')
    icono       VARCHAR(50) DEFAULT 'box', -- Icono Tabler sin prefijo (ej: 'truck')
    orden       INT DEFAULT 0,           -- Orden en el menu de navegacion
    activo      BIT DEFAULT 1
);
```

### Tabla `comun.Permisos`

```sql
CREATE TABLE comun.Permisos (
    id_permiso     INT IDENTITY(1,1) PRIMARY KEY,
    id_usuario     INT NOT NULL,
    id_modulo      INT NOT NULL,
    pueden_ver     BIT DEFAULT 0,
    pueden_crear   BIT DEFAULT 0,
    pueden_editar  BIT DEFAULT 0,
    pueden_eliminar BIT DEFAULT 0,
    pueden_exportar BIT DEFAULT 0,
    FOREIGN KEY (id_usuario) REFERENCES comun.Usuarios(id_usuario),
    FOREIGN KEY (id_modulo) REFERENCES comun.Modulos(id_modulo)
);
```

---

## 3. Uso de `Auth::permisosModulo()`

Retorna un array asociativo con los 5 niveles de permiso para el modulo solicitado:

```php
$permisos = Auth::permisosModulo('transportes');

// $permisos = [
//     'pueden_ver'      => 1,
//     'pueden_crear'    => 1,
//     'pueden_editar'   => 0,
//     'pueden_eliminar' => 0,
//     'pueden_exportar' => 1,
// ];
```

Si el usuario no tiene registro en `comun.Permisos` para ese modulo, retorna todo en `0`.

### Uso en vistas (PHP)

```php
<?php
$permisos = Auth::permisosModulo('mi_modulo');
?>

<?php if ($permisos['pueden_crear'] == 1): ?>
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalRegistro">
    <i class="ti ti-plus me-1"></i> Nuevo Registro
</button>
<?php endif; ?>
```

### Uso en DataTables (PHP en `datatable-*.ajax.php`)

```php
$permisos = Auth::permisosModulo('mi_modulo');

$acciones = '<div class="btn-list flex-nowrap">';

if ($permisos['pueden_editar'] == 1) {
    $acciones .= '<button class="btn btn-icon btn-outline-primary btnEditar" idItem="' . $id . '">
        <i class="ti ti-edit"></i>
    </button>';
}

if ($permisos['pueden_eliminar'] == 1) {
    $acciones .= '<button class="btn btn-icon btn-outline-danger btnEliminar" idItem="' . $id . '">
        <i class="ti ti-trash"></i>
    </button>';
}

$acciones .= '</div>';
```

---

## 4. Registro de Permisos para un Nuevo Modulo

Al crear un nuevo modulo, se deben insertar los permisos para los roles existentes:

```sql
-- 1. Insertar el modulo
INSERT INTO comun.Modulos (nombre, etiqueta, icono, orden)
VALUES ('mi_modulo', 'Mi Modulo', 'box', 15);

-- 2. Otorgar permisos completos al rol ADMIN
INSERT INTO comun.Permisos (id_usuario, id_modulo, pueden_ver, pueden_crear, pueden_editar, pueden_eliminar, pueden_exportar)
SELECT u.id_usuario, m.id_modulo, 1, 1, 1, 1, 1
FROM comun.Usuarios u, comun.Modulos m
WHERE u.rol = 'ADMIN' AND m.nombre = 'mi_modulo';

-- 3. Otorgar permisos limitados a usuarios especificos
INSERT INTO comun.Permisos (id_usuario, id_modulo, pueden_ver, pueden_crear, pueden_editar, pueden_eliminar, pueden_exportar)
SELECT u.id_usuario, m.id_modulo, 1, 0, 0, 0, 0
FROM comun.Usuarios u, comun.Modulos m
WHERE u.id_usuario IN (5, 12, 20) AND m.nombre = 'mi_modulo';
```

---

## 5. Restriccion por Rol en el Router

En `index.php`, algunos modulos tienen restriccion adicional por rol:

```php
case 'sistemas':
    if ($_SESSION['usuario_rol'] != 'ADMIN') {
        echo "Acceso Denegado";
    } else {
        include 'modules/sistemas/controllers/SistemasController.php';
    }
    break;
```

Para modulos nuevos, puedes agregar esta validacion si solo ciertos roles deben acceder.

---

## 6. Menu de Navegacion Dinamico

El `public/header.php` construye el menu consultando `comun.Modulos` y `comun.Permisos`:

```php
$sql_menu = "SELECT m.nombre, m.etiqueta, m.icono 
             FROM comun.Modulos m
             INNER JOIN comun.Permisos p ON m.id_modulo = p.id_modulo
             WHERE p.id_usuario = ? AND p.pueden_ver = 1
             ORDER BY m.orden ASC";
```

Los primeros 6 modulos se muestran visibles, el resto se agrupan en un dropdown "Mas".

---

## 7. Recuperacion de Contrasena

El flujo de recuperacion usa PHPMailer (ver `references/email_and_notifications.md`):

1. `modules/auth/views/login.php` -> enlace "Olvidaste tu contrasena?"
2. `AuthController::recuperar_password()` -> genera token y envia email
3. `utils/mailer.php` -> `sendResetPasswordMail()` envia enlace con token
4. `AuthController::nueva_password()` -> muestra formulario de nueva contrasena
5. `AuthController::procesar_nueva_password()` -> valida token y actualiza contrasena

---

## 8. Sesiones y Seguridad

- Las sesiones usan el dominio configurado en `config/config.php` via `ini_set('session.cookie_domain', ...)`
- No se usa HTTPS en desarrollo local, pero en produccion el protocolo se detecta automaticamente
- Los permisos se verifican en cada carga de pagina (no solo en el login)
- Las acciones AJAX se identifican por tener `Ajax` en el parametro `action` para evitar verificacion redundante de modulo
