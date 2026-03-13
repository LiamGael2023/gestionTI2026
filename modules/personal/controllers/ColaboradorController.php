<?php

// En lugar de rutas relativas simples, usa __DIR__
require_once __DIR__ . "/../../../config/db.php"; 
require_once __DIR__ . "/../models/ColaboradorModel.php";
$model = new ModeloColaborador($conn);
$action = $_GET['action'] ?? 'index';

// switch ($action) {
//     case 'guardar':
//         // Lógica de guardado
//         break;
//     default:
//         include 'modules/papeletas/views/colaboradores.php';
//         break;
// }
$conn  = Conexion::conectar();
$model = new ModeloColaborador($conn);
class ControladorColaborador {

    static public function ctrMostrarColaborador($id_trabajador, $fecha){


		$respuesta = ModeloColaborador::MdlMostrarColaborador($id_trabajador, $fecha);

		return $respuesta;
                
    }
        

    static public function ctrMostrarTrabajadoresActivos()
    {

        // Lo pasas al modelo
        $respuesta = ModeloColaborador::mdlMostrarTrabajadoresActivos();

        return $respuesta;
    }
}