<?php
/**
 * Clase que envuelve una instancia de la clase PDO
 * para el manejo de la base de controladores
 */

//require_once 'login_mysql.php';
require_once 'login_oracle.php';

class ConexionBD
{
    const ESTADO_ERROR_BD = 3;
    /**
     * Única instancia de la clase
     */
    private static $db = null;

    /**
     * Instancia de PDO
     */
    private static $pdo;
    private static $connections = [];

    final private function __construct()
    {
        try {
            self::obtenerBD();
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, "Error de conexión: " . $e->getMessage());
        }
    }

    /**
     * Retorna en la única instancia de la clase
     * @return ConexionBD|null
     */
    public static function obtenerInstancia()
    {
        if (self::$db === null) {
            self::$db = new self();
        }
        return self::$db;
    }

    /**
     * Crear una nueva conexión PDO basada
     * en las constantes de conexión
     * @return PDO Objeto PDO
     */
    public function obtenerBD()
    {
    //putenv("LD_LIBRARY_PATH=c:\windows\system32\instantclient_10_2");
        if (self::$pdo == null) {
            //self::$pdo = new PDO(
            //    'mysql:dbname=' . BASE_DE_DATOS .
            //    ';host=' . NOMBRE_HOST . ";",
            //    USUARIO,
            //    CONTRASENA,
            //    array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
            //);

           try {
             self::$pdo = new PDO(
                 "oci:dbname=" . BASE_DE_DATOS . ';charset=WE8MSWIN1252',
                 USUARIO,
                 CONTRASENA,
                 [
                     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                     PDO::ATTR_EMULATE_PREPARES => false,
                     PDO::ATTR_PERSISTENT => true
                 ]
             );
	   } catch (PDOException $e) {
              throw new ExcepcionApi(self::ESTADO_ERROR_BD, "Error de conexión: " . $e->getMessage());
            }

            // Habilitar excepciones
            //self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return self::$pdo;
    }


    public function obtenerBDDist($rut) 
    {
        if (!isset(self::$connections[$rut])) {
            if (($rut == '001') || ($rut == '999')) {
                try {
                    self::$connections[$rut] = new PDO(
                        "oci:dbname=" . BASE_DE_DATOS . ';charset=WE8MSWIN1252',
                        USUARIO,
                        CONTRASENA,
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false,
                            PDO::ATTR_PERSISTENT => true
                        ]
                    );
                } catch (PDOException $e) {
                    throw new ExcepcionApi(self::ESTADO_ERROR_BD, "Error de conexión para RUT $rut: " . $e->getMessage());
                }
            } else {
                throw new ExcepcionApi(self::ESTADO_ERROR_BD, "La base de datos $rut no está en línea");
            }
        }
        return self::$connections[$rut];
    }

    public function cerrarConexiones()
    {
        self::$pdo = null;
        foreach (self::$connections as &$conn) {
            $conn = null;
        }
        self::$connections = [];
    }

    /**
     * Evita la clonación del objeto
     */
    final protected function __clone() { }
    
    public function __destruct()
    {
        $this->cerrarConexiones();
    }
}