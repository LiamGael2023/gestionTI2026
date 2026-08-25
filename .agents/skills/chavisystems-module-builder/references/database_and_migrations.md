# Base de Datos y Migraciones en CHAVIsystems

Esta guia explica las convenciones de base de datos, stored procedures y como gestionar scripts SQL de migracion para nuevos modulos.

---

## 1. Configuracion de Conexion

La conexion se define en `config/db.php` usando el driver `sqlsrv` de PHP para SQL Server:

```php
class Conexion {
    static public function conectar() {
        $serverName = "localhost";
        $connectionOptions = [
            "Database" => "BD_GESTIONTI",
            "Uid" => "sa",
            "PWD" => "...",
            "Encrypt" => false,
            "TrustServerCertificate" => true,
            "CharacterSet" => "UTF-8"
        ];
        return sqlsrv_connect($serverName, $connectionOptions);
    }
}
```

Usar siempre `Conexion::conectar()` para obtener la conexion. **No crear nuevas conexiones manualmente.**

---

## 2. Convencion de Nombres

### Tablas

- Usar el prefijo del esquema: `comun.`, `dbo.`, etc.
- Nombres en singular o plural segun el contexto del modulo.
- Ejemplos existentes: `comun.Modulos`, `comun.Permisos`, `comun.Usuarios`.

### Stored Procedures

- Prefijo `usp_` seguido del nombre del modulo y la accion.
- Formato: `usp_<Modulo>_<Accion>`
- Ejemplos:

| Procedimiento | Proposito |
|---------------|-----------|
| `usp_Ejemplo_Listar` | Listar todos los registros |
| `usp_Ejemplo_Insertar` | Insertar nuevo registro |
| `usp_Ejemplo_Actualizar` | Actualizar registro existente |
| `usp_Ejemplo_Eliminar` | Eliminar registro (logico o fisico) |
| `usp_Ejemplo_ObtenerPorId` | Obtener un registro por ID |

---

## 3. Estructura de Stored Procedures

### Template de SP para Listar

```sql
CREATE PROCEDURE [dbo].[usp_MiModulo_Listar]
AS
BEGIN
    SET NOCOUNT ON;
    SELECT id, codigo, descripcion, estado, fecha_registro
    FROM dbo.MiModulo
    WHERE estado = 1
    ORDER BY id DESC;
END;
```

### Template de SP para Insertar

```sql
CREATE PROCEDURE [dbo].[usp_MiModulo_Insertar]
    @codigo VARCHAR(50),
    @descripcion VARCHAR(255),
    @created_by INT
AS
BEGIN
    SET NOCOUNT ON;
    INSERT INTO dbo.MiModulo (codigo, descripcion, created_by, fecha_registro)
    VALUES (@codigo, @descripcion, @created_by, GETDATE());
    
    SELECT SCOPE_IDENTITY() AS nuevo_id;
END;
```

### Template de SP para Actualizar

```sql
CREATE PROCEDURE [dbo].[usp_MiModulo_Actualizar]
    @id INT,
    @codigo VARCHAR(50),
    @descripcion VARCHAR(255)
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE dbo.MiModulo
    SET codigo = @codigo,
        descripcion = @descripcion,
        fecha_modificacion = GETDATE()
    WHERE id = @id;
END;
```

### Template de SP para Eliminar (logico)

```sql
CREATE PROCEDURE [dbo].[usp_MiModulo_Eliminar]
    @id INT
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE dbo.MiModulo
    SET estado = 0,
        fecha_eliminacion = GETDATE()
    WHERE id = @id;
END;
```

### Template de SP para Obtener por ID

```sql
CREATE PROCEDURE [dbo].[usp_MiModulo_ObtenerPorId]
    @id INT
AS
BEGIN
    SET NOCOUNT ON;
    SELECT id, codigo, descripcion, estado, fecha_registro
    FROM dbo.MiModulo
    WHERE id = @id;
END;
```

---

## 4. Llamada a Stored Procedures desde PHP

Usar parametros posicionales con `?`:

```php
$conn = Conexion::conectar();
$sql = "EXEC [dbo].[usp_Ejemplo_Insertar] ?, ?, ?";
$params = [$codigo, $descripcion, $created_by];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    error_log('Error SQL: ' . print_r(sqlsrv_errors(), true));
    return ['status' => 'error', 'message' => 'Error en la base de datos.'];
}

sqlsrv_free_stmt($stmt);
```

### Buenas practicas

- Siempre usar `sqlsrv_free_stmt($stmt)` despues de cada consulta
- Siempre verificar `$stmt === false` y loguear errores con `error_log()`
- Usar `SET NOCOUNT ON` en todos los stored procedures
- Retornar arrays asociativos con `SQLSRV_FETCH_ASSOC`
- Sanitizar salida HTML con `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')`

---

## 5. Archivos SQL de Migracion

Cada modulo debe incluir sus scripts de base de datos en la carpeta `sql/`:

```
modules/<modulo>/sql/
  esquema.sql              -- Creacion de tablas, indices, constraints
  stored_procedures.sql    -- Creacion de todos los SPs del modulo
  datos_iniciales.sql      -- Inserts de datos semilla (opcional)
  patch_<descripcion>.sql  -- Parches y actualizaciones posteriores
```

### Ejemplo real del proyecto

```
modules/transportes/sql/
  NuevoEsquemaTransportes.sql
  Patch Transportes.sql
  patch_bitacora_vehicular.sql

modules/patrimonio/sql/
  (4 archivos SQL)
```

---

## 6. Conexion desde Modelos

Siempre obtener la conexion desde `Conexion::conectar()` al inicio de cada metodo:

```php
class MiModuloModel
{
    static public function mdlListar()
    {
        $conn = Conexion::conectar();
        $sql = "EXEC [dbo].[usp_MiModulo_Listar]";
        $stmt = sqlsrv_query($conn, $sql);

        if ($stmt === false) {
            error_log('mdlListar Error: ' . print_r(sqlsrv_errors(), true));
            return [];
        }

        $resultados = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $resultados[] = $row;
        }

        sqlsrv_free_stmt($stmt);
        return $resultados;
    }
}
```

**No reutilizar la conexion entre metodos.** Cada llamada a `Conexion::conectar()` crea una nueva conexion. Para operaciones que requieren transaccion, manejar la conexion manualmente.

---

## 7. Manejo de Errores de Base de Datos

```php
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS) ?: [];
    error_log('Error en consulta: ' . print_r($errors, true));
    return [
        'status'  => 'error',
        'message' => 'Error al procesar la solicitud en la base de datos.'
    ];
}
```

- **En produccion:** no exponer detalles del error SQL al usuario. Usar mensajes genericos.
- **En desarrollo:** `ini_set('display_errors', 1)` muestra los errores directamente.
- Usar `error_log()` para registrar errores en el log del servidor.

---

## 8. Transacciones

Para operaciones que modifican multiples tablas:

```php
$conn = Conexion::conectar();
sqlsrv_begin_transaction($conn);

try {
    $stmt1 = sqlsrv_query($conn, "EXEC usp_InsertarPrincipal ?, ?", $params1);
    if ($stmt1 === false) throw new Exception("Error en insercion principal");

    $stmt2 = sqlsrv_query($conn, "EXEC usp_InsertarDetalle ?, ?", $params2);
    if ($stmt2 === false) throw new Exception("Error en insercion detalle");

    sqlsrv_commit($conn);
    return ['status' => 'success', 'message' => 'Operacion completada.'];
} catch (Exception $e) {
    sqlsrv_rollback($conn);
    error_log('Transaccion fallida: ' . $e->getMessage());
    return ['status' => 'error', 'message' => 'Error en la operacion.'];
}
```
