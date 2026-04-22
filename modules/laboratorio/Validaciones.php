<?php

/**
 * Clase Validaciones - Reutilizable para módulo Laboratorio
 * Centraliza todas las validaciones necesarias
 */
class Validaciones {
    
    /**
     * Valida un nombre o texto que debe contener letras (no solo números)
     * 
     * @param string $texto Texto a validar
     * @param int $minLength Longitud mínima
     * @param int $maxLength Longitud máxima
     * @return string|null Mensaje de error si es inválido, null si es válido
     */
    public static function validarNombre($texto, $minLength = 3, $maxLength = 100) {
        // Verificar que no esté vacío
        if (empty(trim($texto))) {
            return "Este campo es obligatorio";
        }
        
        // Verificar longitud
        $longitud = strlen(trim($texto));
        if ($longitud < $minLength) {
            return "Debe tener al menos $minLength caracteres";
        }
        if ($longitud > $maxLength) {
            return "No puede exceder $maxLength caracteres";
        }
        
        // Verificar que contenga al menos una letra
        if (!preg_match('/[a-zA-ZáéíóúñÁÉÍÓÚÑ]/i', $texto)) {
            return "Debe contener al menos una letra (no puede ser solo números)";
        }
        
        return null;
    }
    
    /**
     * Valida texto general (opcional)
     * 
     * @param string $texto Texto a validar
     * @param bool $requerido Si es obligatorio
     * @param int $maxLength Longitud máxima
     * @return string|null Mensaje de error si es inválido, null si es válido
     */
    public static function validarTexto($texto, $requerido = false, $maxLength = 500) {
        if ($requerido && empty(trim($texto))) {
            return "Este campo es obligatorio";
        }
        
        if (!empty($texto) && strlen($texto) > $maxLength) {
            return "No puede exceder $maxLength caracteres";
        }
        
        return null;
    }
    
    /**
     * Valida formato de fecha YYYY-MM-DD
     * 
     * @param string $fecha Fecha a validar
     * @param bool $requerido Si es obligatorio
     * @return string|null Mensaje de error si es inválido, null si es válido
     */
    public static function validarFecha($fecha, $requerido = false) {
        if (empty($fecha)) {
            if ($requerido) {
                return "Esta fecha es obligatoria";
            }
            return null;
        }
        
        // Validar formato YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return "Debe estar en formato YYYY-MM-DD";
        }
        
        // Validar que sea una fecha válida
        $partes = explode('-', $fecha);
        if (!checkdate((int)$partes[1], (int)$partes[2], (int)$partes[0])) {
            return "La fecha no es válida";
        }
        
        return null;
    }
    
    /**
     * Valida que la fecha final sea mayor o igual a la inicial
     * 
     * @param string $fechaInicio Fecha de inicio
     * @param string $fechaFinal Fecha final
     * @param string $nombreInicio Nombre del campo inicio
     * @param string $nombreFinal Nombre del campo final
     * @return string|null Mensaje de error si es inválido, null si es válido
     */
    public static function validarRangoFechas($fechaInicio, $fechaFinal, $nombreInicio = "Fecha inicio", $nombreFinal = "Fecha final") {
        // Si ambas están vacías, es válido
        if (empty($fechaInicio) && empty($fechaFinal)) {
            return null;
        }
        
        // Si solo una está llena, es válido (la otra es opcional)
        if (empty($fechaInicio) || empty($fechaFinal)) {
            return null;
        }
        
        // Validar cada fecha
        $error1 = self::validarFecha($fechaInicio, false);
        $error2 = self::validarFecha($fechaFinal, false);
        
        if ($error1) return $error1;
        if ($error2) return $error2;
        
        // Comparar fechas
        if (strtotime($fechaFinal) < strtotime($fechaInicio)) {
            return "$nombreFinal no puede ser anterior a $nombreInicio";
        }
        
        return null;
    }
    
    /**
     * Valida un ID numérico
     * 
     * @param mixed $id ID a validar
     * @param bool $requerido Si es obligatorio
     * @return string|null Mensaje de error si es inválido, null si es válido
     */
    public static function validarId($id, $requerido = false) {
        if (empty($id)) {
            if ($requerido) {
                return "El ID es obligatorio";
            }
            return null;
        }
        
        if (!is_numeric($id) || (int)$id <= 0) {
            return "El ID debe ser un número positivo";
        }
        
        return null;
    }
    
    /**
     * Valida que un ID exista en la base de datos
     * 
     * @param mixed $id ID a validar
     * @param string $tabla Tabla donde buscar
     * @param mixed $db Conexión a la base de datos
     * @param string $campo Campo a buscar (default: Id_)
     * @return string|null Mensaje de error si no existe, null si es válido
     */
    public static function validarIdExiste($id, $tabla, $db, $campo = null) {
        if (empty($id)) {
            return null;
        }
        
        if (!is_numeric($id) || (int)$id <= 0) {
            return "El ID debe ser un número positivo";
        }
        
        // Si no se especifica campo, construirlo automáticamente
        if ($campo === null) {
            // Obtener el nombre de la tabla sin prefijo
            $nombreTabla = basename(str_replace('.', '', $tabla));
            $campo = 'Id_' . $nombreTabla;
        }
        
        // Buscar en la base de datos
        $sql = "SELECT COUNT(*) as cnt FROM $tabla WHERE $campo = ? AND Activo = 1";
        $stmt = sqlsrv_query($db, $sql, array($id));
        
        if ($stmt === false) {
            return "Error al validar el ID";
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($row['cnt'] == 0) {
            return "Este registro no existe o está inactivo";
        }
        
        return null;
    }
    
    /**
     * Verifica si hay errores en un array de validaciones
     * 
     * @param array $validaciones Array con resultados de validaciones
     * @return bool True si hay errores
     */
    public static function hayErrores($validaciones) {
        foreach ($validaciones as $error) {
            if ($error !== null) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Obtiene solo los errores de un array de validaciones
     * 
     * @param array $validaciones Array con resultados de validaciones
     * @param array $campos Nombres de los campos (para mensaje)
     * @return array Array solo con errores
     */
    public static function obtenerErrores($validaciones, $campos = null) {
        $errores = [];
        $i = 0;
        foreach ($validaciones as $campo => $error) {
            if ($error !== null) {
                $nombreCampo = is_array($campos) && isset($campos[$campo]) ? $campos[$campo] : $campo;
                $errores[$campo] = $error;
            }
        }
        return $errores;
    }
    
    /**
     * Combina múltiples errores en un mensaje legible
     * 
     * @param array $errores Array de errores
     * @return string Mensaje combinado
     */
    public static function obtenerMensajeErrores($errores) {
        if (empty($errores)) {
            return "";
        }
        
        $mensajes = [];
        foreach ($errores as $campo => $error) {
            $mensajes[] = $error;
        }
        
        return implode("\n", $mensajes);
    }
}
