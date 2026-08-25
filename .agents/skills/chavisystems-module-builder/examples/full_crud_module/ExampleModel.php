<?php
class ExampleModel
{
    /**
     * Listar todos los registros invocando Stored Procedure o SQL directo
     */
    static public function mdlListar()
    {
        $conn = Conexion::conectar();
        $sql = "EXEC [dbo].[usp_Ejemplo_Listar]";
        $stmt = sqlsrv_query($conn, $sql);

        if ($stmt === false) {
            error_log('mdlListar SQL Error: ' . print_r(sqlsrv_errors(), true));
            return [];
        }

        $resultados = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $resultados[] = $row;
        }

        sqlsrv_free_stmt($stmt);
        return $resultados;
    }

    /**
     * Crear un nuevo registro
     */
    static public function mdlCrear($datos)
    {
        $conn = Conexion::conectar();
        $sql = "EXEC [dbo].[usp_Ejemplo_Insertar] ?, ?, ?";
        $params = [
            trim($datos['codigo']),
            trim($datos['descripcion']),
            (int)$datos['created_by']
        ];

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS) ?: [];
            error_log('mdlCrear Error: ' . print_r($errors, true));
            return [
                'status'  => 'error',
                'message' => 'Error al guardar el registro en la base de datos.'
            ];
        }

        sqlsrv_free_stmt($stmt);
        return [
            'status'  => 'success',
            'message' => 'Registro creado satisfactoriamente.'
        ];
    }

    /**
     * Editar un registro existente
     */
    static public function mdlEditar($datos)
    {
        $conn = Conexion::conectar();
        $sql = "EXEC [dbo].[usp_Ejemplo_Actualizar] ?, ?, ?";
        $params = [
            (int)$datos['id'],
            trim($datos['codigo']),
            trim($datos['descripcion'])
        ];

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS) ?: [];
            error_log('mdlEditar Error: ' . print_r($errors, true));
            return [
                'status'  => 'error',
                'message' => 'Error al actualizar el registro.'
            ];
        }

        sqlsrv_free_stmt($stmt);
        return [
            'status'  => 'success',
            'message' => 'Registro actualizado satisfactoriamente.'
        ];
    }

    /**
     * Eliminar un registro por ID
     */
    static public function mdlEliminar($id)
    {
        $conn = Conexion::conectar();
        $sql = "EXEC [dbo].[usp_Ejemplo_Eliminar] ?";
        $stmt = sqlsrv_query($conn, $sql, array((int)$id));

        if ($stmt === false) {
            $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS) ?: [];
            error_log('mdlEliminar Error: ' . print_r($errors, true));
            return [
                'status'  => 'error',
                'message' => 'Error al eliminar el registro.'
            ];
        }

        sqlsrv_free_stmt($stmt);
        return [
            'status'  => 'success',
            'message' => 'Registro eliminado satisfactoriamente.'
        ];
    }

    /**
     * Obtener un unico registro por ID
     */
    static public function mdlObtenerPorId($id)
    {
        $conn = Conexion::conectar();
        $sql = "EXEC [dbo].[usp_Ejemplo_ObtenerPorId] ?";
        $stmt = sqlsrv_query($conn, $sql, array($id));

        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            sqlsrv_free_stmt($stmt);
            return $row;
        }

        return null;
    }
}
