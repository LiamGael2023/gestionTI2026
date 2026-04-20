<?php
function repararPermisosUploads(){
        $ruta = "C:\\inetpub\\wwwroot\\gestionTI1\\gestionti\\modules\\uploads";

    if(!file_exists($ruta)){
       return;
    }

    // activar herencia
    exec('icacls "'.$ruta.'" /inheritance:e /T');

    // permisos IIS
    exec('icacls "'.$ruta.'" /grant IUSR:(OI)(CI)RX /T');
    exec('icacls "'.$ruta.'" /grant IIS_IUSRS:(OI)(CI)RX /T');

    //permisos usuarios del sistema
    exec('icacls "'.$ruta.'" /grant Usuarios:(OI)(CI)RX /T');
    // Carpeta principal de uploads
    $rutas = [
        "C:\\inetpub\\wwwroot\\gestionTI1\\gestionti\\modules\\uploads",
        "C:\\inetpub\\wwwroot\\gestionTI1\\gestionti\\modules\\certificados\\controllers\\boletas"
    ];

    foreach($rutas as $ruta){
        if(!file_exists($ruta)){
            mkdir($ruta, 0777, true);
        }

        // Activar herencia
        exec('icacls "'.$ruta.'" /inheritance:e /T');

        // Permisos IIS
        exec('icacls "'.$ruta.'" /grant IUSR:(OI)(CI)RX /T');
        exec('icacls "'.$ruta.'" /grant IIS_IUSRS:(OI)(CI)RX /T');

        // Permisos usuarios del sistema
        exec('icacls "'.$ruta.'" /grant Usuarios:(OI)(CI)RX /T');
    }
}