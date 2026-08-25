# Ruteo y Navegacion en CHAVIsystems

Esta guia explica como funciona el sistema de ruteo, como se registran nuevos modulos y como se construye la navegacion.

---

## 1. Flujo de Ruteo

El punto de entrada unico es `index.php`. El flujo es:

```
Solicitud HTTP
  --> .htaccess (Apache) o web.config (IIS) reescribe a index.php?route=<path>
  --> index.php parsea la ruta
  --> $module = primer segmento, $action = segundo segmento
  --> Se verifica si es archivo estatico (.css, .js, .png, etc.) -> 404
  --> Se despachan rutas publicas (auth/login, auth/logout)
  --> Auth::check() verifica sesion y permisos
  --> Se incluye public/header.php
  --> Se incluye el controlador del modulo
  --> Se incluye public/footer.php
```

### Formatos de URL soportados

```
URL amigable:  /CHAVIsystems/transportes
               /CHAVIsystems/transportes/crear
               
Parametros:    /CHAVIsystems/index.php?module=transportes&action=crear
```

---

## 2. Como se Registra un Nuevo Modulo

### Paso 1: Crear la estructura del modulo

```
modules/mi_modulo/
  controllers/MiModuloController.php
  models/MiModuloModel.php
  views/index.php
  ajax/mi_modulo.ajax.php
  ajax/datatable-mi_modulo.ajax.php
```

### Paso 2: Registrar en Base de Datos

```sql
INSERT INTO comun.Modulos (nombre, etiqueta, icono, orden)
VALUES ('mi_modulo', 'Mi Modulo', 'box', 15);

INSERT INTO comun.Permisos (id_usuario, id_modulo, pueden_ver, pueden_crear, pueden_editar, pueden_eliminar, pueden_exportar)
SELECT u.id_usuario, m.id_modulo, 1, 1, 1, 1, 1
FROM comun.Usuarios u, comun.Modulos m
WHERE u.rol = 'ADMIN' AND m.nombre = 'mi_modulo';
```

### Paso 3: El router lo detecta automaticamente

Si el controlador sigue la convencion `modules/<nombre>/controllers/<Nombre>Controller.php`, el router dinamico de `index.php` lo carga automaticamente **sin necesidad de modificar `index.php`**:

```php
// Logica dinamica en index.php (linea ~150):
$nombreControlador = ucfirst($module) . "Controller.php";
$pathFull = "modules/$module/controllers/$nombreControlador";

if (file_exists($pathFull)) {
    include $pathFull;
}
```

---

## 3. Modulos Estaticos vs Dinamicos

`index.php` tiene dos mecanismos de ruteo:

### Modulos Estaticos

Definidos en el array `$modulos_estaticos`. Requieren modificacion manual de `index.php`:

```php
$modulos_estaticos = ['dashboard', 'usuarios', 'sistemas', ...];

if (in_array($module, $modulos_estaticos)) {
    switch ($module) {
        case 'dashboard':
            include 'modules/dashboard/controllers/DashboardController.php';
            break;
        // ...
    }
}
```

Usar este enfoque cuando el modulo necesita:
- Logica de ruteo especial (acciones adicionales, vistas condicionales)
- Restriccion de acceso por rol
- Vistas que no siguen el patron controlador -> vista

### Modulos Dinamicos

Cualquier modulo NO listado en `$modulos_estaticos` que tenga un controlador con la convencion de nombres correcta se carga automaticamente.

**Recomendacion para modulos nuevos:** usar el enfoque dinamico si es posible. Solo agregar al array estatico si se requiere logica de ruteo especial.

---

## 4. Acciones dentro de un Modulo

El controlador determina que accion ejecutar basado en `$action`:

```php
// En MiModuloController.php
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'crear':
        // Mostrar vista de creacion o procesar POST
        include 'modules/mi_modulo/views/crear.php';
        break;
    case 'exportar':
        include 'modules/mi_modulo/views/exportar_excel.php';
        break;
    default:
        include 'modules/mi_modulo/views/index.php';
        break;
}
```

---

## 5. Estructura del Menu de Navegacion

El menu se genera dinamicamente en `public/header.php` consultando `comun.Modulos` y `comun.Permisos` para el usuario logueado.

```php
$sql_menu = "SELECT m.nombre, m.etiqueta, m.icono 
             FROM comun.Modulos m
             INNER JOIN comun.Permisos p ON m.id_modulo = p.id_modulo
             WHERE p.id_usuario = ? AND p.pueden_ver = 1
             ORDER BY m.orden ASC";
```

Caracteristicas:
- Los primeros 6 modulos se muestran como items visibles en la barra
- Los modulos adicionales se agrupan en un dropdown "Mas"
- El item activo se resalta con clase `active`
- Los iconos usan la clase `ti ti-<icono>` de Tabler Icons

### Iconos disponibles en el menu

El campo `icono` en `comun.Modulos` debe contener el nombre del icono Tabler sin el prefijo `ti-`:

| Nombre en BD | Clase CSS generada | Icono |
|-------------|-------------------|-------|
| `home` | `ti ti-home` | Inicio |
| `users` | `ti ti-users` | Usuarios |
| `truck` | `ti ti-truck` | Transportes |
| `building` | `ti ti-building` | Patrimonio |
| `file-text` | `ti ti-file-text` | Papeletas |
| `shield` | `ti ti-shield` | Vigilantes |
| `settings` | `ti ti-settings` | Sistemas |
| `box` | `ti ti-box` | Generico |

---

## 6. Redirecciones y Manejo de Errores

### Acceso denegado

Cuando `Auth::check()` detecta que el usuario no tiene permisos:

```php
header("Location: " . BASE_URL . "/dashboard?error=access_denied");
```

El `index.php` muestra un SweetAlert con el mensaje "Acceso Restringido".

### Modulo no encontrado

Si el controlador no existe en la ruta dinamica:

```php
echo '<div class="alert alert-danger">
    <h3 class="alert-title">Error 404</h3>
    <div class="text-secondary">El sistema "'.htmlspecialchars($module).'" no tiene un controlador configurado</div>
</div>';
```

---

## 7. URLs Base y Entorno

`config/config.php` define `BASE_URL` dinamicamente:

```php
$host = $_SERVER['HTTP_HOST'];
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
define('BASE_URL', $protocol . $host . '/CHAVIsystems');
```

Esto permite que el sistema funcione tanto en:
- Desarrollo local: `http://localhost/CHAVIsystems`
- Red interna: `http://10.0.100.252/CHAVIsystems`
- Produccion: `https://app.chavimochic.gob.pe/CHAVIsystems`

### Directiva `<base>` en el HTML

`public/header.php` incluye:

```html
<base href="<?php echo BASE_URL; ?>/">
```

Esto asegura que todas las rutas relativas (CSS, JS, AJAX) se resuelvan correctamente.
