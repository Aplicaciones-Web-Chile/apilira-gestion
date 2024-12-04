<?php
/**
 * Provee las constantes para conectarse a la base de datos
 * Oracle.
 */

//ACC
define("NOMBRE_HOST", "distribuidoralira.ddns.net"); // Nombre del host (desde server API)
define("PUERTO", "1521"); // Puerto
//define("NOMBRE_HOST", "190.153.251.147"); // Nombre del host (desde localhost)
//define("PUERTO", "1522"); // Puerto
define("SID", "XE"); // Sid
$tns = "(DESCRIPTION =
    (ADDRESS = (PROTOCOL = TCP)(HOST = ".NOMBRE_HOST.")(PORT = ".PUERTO."))
    (CONNECT_DATA =
      (SERVER = DEDICATED)
      (SERVICE_NAME = ".SID.")
    )
  )";
define("BASE_DE_DATOS", $tns ); // Cadena de Conexión
define("USUARIO", "system"); // Nombre del usuario
define("CONTRASENA", "SR1302571094"); // Contraseña
?>