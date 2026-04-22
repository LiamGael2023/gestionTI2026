<?php
/**
 * LaboratorioModel
 * Modelo del módulo Laboratorio
 * Buenas prácticas: Consultas parametrizadas, separación de responsabilidades
 */

class LaboratorioModel {
    private $db;

    public function __construct($db) {
        if (!$db) {
            throw new Exception("Error: No se pudo establecer la conexión a la base de datos");
        }
        $this->db = $db;
    }

    /**
     * Obtiene la información del usuario logueado
     * @param int $id_usuario ID del usuario
     * @return array Datos del usuario
     */
    public function obtenerUsuario($id_usuario) {
        $sql = "SELECT id_usuario, documento, nombres, apellidos, usuario, correo, rol, sede_id 
                FROM comun.Usuarios 
                WHERE id_usuario = ? AND activo = 1";
        
        $stmt = sqlsrv_query($this->db, $sql, array($id_usuario));
        
        if ($stmt === false) {
            return null;
        }
        
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }

    /**
     * Obtiene las responsabilidades del usuario en el módulo Laboratorio
     * Responsabilidades: Control de Equipos, Reactivos, Parámetros, Servicios, Ventas, Muestras
     * @param int $id_usuario ID del usuario
     * @return array Lista de responsabilidades
     */
    public function obtenerResponsabilidades($id_usuario) {
        // Definir las responsabilidades disponibles en laboratorio
        $responsabilidades = array(
            array(
                'nombre' => 'Equipos',
                'icono' => 'microscope',
                'descripcion' => 'Control de Equipos',
                'url' => '?module=laboratorio&action=equipo',
                'color' => 'primary'
            ),
            array(
                'nombre' => 'Reactivos',
                'icono' => 'flask',
                'descripcion' => 'Control de Reactivos',
                'url' => '?module=laboratorio&action=reactivo',
                'color' => 'primary'
            ),
            array(
                'nombre' => 'Parámetros',
                'icono' => 'binary',
                'descripcion' => 'Control de Parámetros',
                'url' => '?module=laboratorio&action=parametro',
                'color' => 'primary'
            ),
            array(
                'nombre' => 'Servicios',
                'icono' => 'stethoscope',
                'descripcion' => 'Control de Servicios',
                'url' => '?module=laboratorio&action=servicio',
                'color' => 'primary'
            ),
            array(
                'nombre' => 'Ventas',
                'icono' => 'shopping-cart',
                'descripcion' => 'Control de Ventas',
                'url' => '?module=laboratorio&action=venta',
                'color' => 'primary'
            ),
            array(
                'nombre' => 'Muestras',
                'icono' => 'test-pipe',
                'descripcion' => 'Control de Muestras',
                'url' => '?module=laboratorio&action=muestra',
                'color' => 'primary'
            ),
            array(
                'nombre' => 'Residuos',
                'icono' => 'trash',
                'descripcion' => 'Control de Residuos',
                'url' => '?module=laboratorio&action=residuo',
                'color' => 'primary'
            ),
            array(
                'nombre' => 'Reportes',
                'icono' => 'report-analytics',
                'descripcion' => 'Reportes de Residuos, Muestras y Kardex',
                'url' => '?module=laboratorio&action=reportes',
                'color' => 'primary'
            )
        );

        // Validar permisos del usuario para cada responsabilidad
        $responsabilidades_permitidas = array();
        
        foreach ($responsabilidades as $resp) {
            // Por ahora, si tiene acceso al módulo laboratorio, tiene acceso a todos
            // Puedes implementar validación de permisos más granulares si lo necesitas
            $responsabilidades_permitidas[] = $resp;
        }

        return $responsabilidades_permitidas;
    }

    /**
     * Obtiene listado de equipos (para futuras estadísticas)
     * @return mixed Resultado de la consulta
     */
    public function listarEquipos() {
        $sql = "SELECT * FROM laboratorio_equipos WHERE activo = 1";
        return sqlsrv_query($this->db, $sql);
    }

    /**
     * Obtiene listado de reactivos (para futuras estadísticas)
     * @return mixed Resultado de la consulta
     */
    public function listarReactivos() {
        $sql = "SELECT * FROM laboratorio_reactivos WHERE activo = 1";
        return sqlsrv_query($this->db, $sql);
    }

    /**
     * Obtiene listado de servicios (para futuras estadísticas)
     * @return mixed Resultado de la consulta
     */
    public function listarServicios() {
        $sql = "SELECT * FROM laboratorio_servicios WHERE activo = 1";
        return sqlsrv_query($this->db, $sql);
    }
}
