<?php

require __DIR__.'/../datos/ConexionBD.php';

class usuarios
{
    // Datos de la tabla "usr"
    const NOMBRE_TABLA = "USRAPI";
    const ID_USUARIO = "KUSR";
    const NOMBRE = "DUSR";
    const CONTRASENA = "PAPI";
    const CLAVE_API = "KAPI";

    const ESTADO_CREACION_EXITOSA = 1;
    const ESTADO_CREACION_FALLIDA = 2;
    const ESTADO_ERROR_BD = 3;
    const ESTADO_AUSENCIA_CLAVE_API = 4;
    const ESTADO_CLAVE_NO_AUTORIZADA = 5;
    const ESTADO_URL_INCORRECTA = 6;
    const ESTADO_FALLA_DESCONOCIDA = 7;
    const ESTADO_PARAMETROS_INCORRECTOS = 8;

    private static function validarDatosUsuario($usuario) {
        if (!isset($usuario->id_usuario) || strlen($usuario->id_usuario) < 3) {
            throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS, 
                "El ID de usuario debe tener al menos 3 caracteres");
        }
        if (!isset($usuario->contrasena) || strlen($usuario->contrasena) < 6) {
            throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS, 
                "La contraseña debe tener al menos 6 caracteres");
        }
        if (!isset($usuario->nombre) || empty(trim($usuario->nombre))) {
            throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS, 
                "El nombre es requerido");
        }
        return true;
    }

    public static function post($peticion)
    {
        if ($peticion[0] == 'registro') {
            return self::registrar();
        } else if ($peticion[0] == 'login') {
            return self::loguear();
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "URL mal formada", 400);
        }
    }

    private function registrar()
    {
        try {
            $cuerpo = file_get_contents('php://input');
            $usuario = json_decode($cuerpo);
            
            if ($usuario === null) {
                throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS, 
                    "Error en el formato JSON");
            }

            self::validarDatosUsuario($usuario);
            
            if (self::usuarioExiste($usuario->id_usuario)) {
                throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS, 
                    "El usuario ya existe");
            }

            $resultado = self::crear($usuario);

            if ($resultado === self::ESTADO_CREACION_EXITOSA) {
                http_response_code(201);
                return [
                    "estado" => self::ESTADO_CREACION_EXITOSA,
                    "mensaje" => utf8_encode("¡Registro exitoso!")
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, 
                    "Error al crear el usuario");
            }
        } catch (ExcepcionApi $e) {
            throw $e;
        } catch (Exception $e) {
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, 
                $e->getMessage(), $e->getCode());
        }
    }

    private static function usuarioExiste($id_usuario) {
        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
            $comando = "SELECT COUNT(*) FROM " . self::NOMBRE_TABLA . 
                      " WHERE " . self::ID_USUARIO . " = ?";
            
            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $id_usuario);
            $sentencia->execute();
            
            return $sentencia->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private function crear($datosUsuario)
    {
        $id_usuario = $datosUsuario->id_usuario;
        $contrasena = $datosUsuario->contrasena;
        $nombre = $datosUsuario->nombre;

        $contrasenaEncriptada = self::encriptarContrasena($contrasena);
        $claveApi = self::generarClaveApi();

        try {
            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();
            $pdo->beginTransaction();

            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::ID_USUARIO . "," .
                self::NOMBRE . "," .
                self::CONTRASENA . "," .
                self::CLAVE_API . ")" .
                " VALUES(?, ?, ?, ?)";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $id_usuario);
            $sentencia->bindParam(2, $nombre);
            $sentencia->bindParam(3, $contrasenaEncriptada);
            $sentencia->bindParam(4, $claveApi);

            $resultado = $sentencia->execute();
            
            if ($resultado) {
                $pdo->commit();
                return self::ESTADO_CREACION_EXITOSA;
            }
            
            $pdo->rollBack();
            return self::ESTADO_CREACION_FALLIDA;

        } catch (PDOException $e) {
            if (isset($pdo)) $pdo->rollBack();
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private function encriptarContrasena($contrasenaPlana)
    {
        if ($contrasenaPlana) {
            return password_hash($contrasenaPlana, PASSWORD_DEFAULT, ['cost' => 12]);
        }
        return null;
    }

    private function generarClaveApi()
    {
        return bin2hex(random_bytes(32));
    }

    private function loguear()
    {
        try {
            $respuesta = array();
            $body = file_get_contents('php://input');
            $usuario = json_decode($body);

            if ($usuario === null) {
                throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS, 
                    "Error en el formato JSON");
            }

            if (!isset($usuario->id_usuario) || !isset($usuario->contrasena)) {
                throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS,
                    "Usuario y contraseña son requeridos");
            }

            $id_usuario = $usuario->id_usuario;
            $contrasena = $usuario->contrasena;

            if (self::autenticar($id_usuario, $contrasena)) {
                $usuarioBD = self::obtenerUsuarioPorID($id_usuario);

                if ($usuarioBD != NULL) {
                    http_response_code(200);
                    $respuesta["nombre"] = $usuarioBD["DUSR"];
                    $respuesta["claveApi"] = $usuarioBD["KAPI"];
                    return ["estado" => 1, "usuario" => $respuesta];
                }
            }
            
            throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS,
                utf8_encode("Usuario o contraseña inválidos"));

        } catch (ExcepcionApi $e) {
            throw $e;
        } catch (Exception $e) {
            throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, 
                $e->getMessage(), $e->getCode());
        }
    }

    private function autenticar($id_usuario, $contrasena)
    {
        $comando = "SELECT PAPI FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::ID_USUARIO . "=?";
        try {
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $id_usuario);

            $sentencia->execute();

            if ($sentencia) {
                $resultado = $sentencia->fetch();

                if (self::validarContrasena($contrasena, $resultado['PAPI'])) {
                    return true;
                } else return false;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private function validarContrasena($contrasenaPlana, $contrasenaHash)
    {
         //return $contrasenaPlana = $contrasenaHash;
        return password_verify($contrasenaPlana, $contrasenaHash);
    }

    private function obtenerUsuarioPorID($id_usuario)
    {
        $comando = "SELECT " .
            self::NOMBRE . "," .
            self::CONTRASENA . "," .
            self::CLAVE_API .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::ID_USUARIO . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $id_usuario);

        if ($sentencia->execute())
            return $sentencia->fetch(PDO::FETCH_ASSOC);
        else
            return null;
    }

    /**
     * Otorga los permisos a un usuario para que acceda a los recursos
     * @return null o el id del usuario autorizado
     * @throws Exception
     */
    public static function autorizar()
    {
        $cabeceras = apache_request_headers();

        if (isset($cabeceras["Authorization"])) {

            $claveApi = $cabeceras["Authorization"];
            
            if (usuarios::validarClaveApi($claveApi)) {
                return usuarios::obtenerIdUsuario($claveApi);
            } else {
                throw new ExcepcionApi(
                    self::ESTADO_CLAVE_NO_AUTORIZADA, "Clave de API no autorizada", 401);
            }

        } else {
            throw new ExcepcionApi(
                self::ESTADO_AUSENCIA_CLAVE_API,
                utf8_encode("Se requiere Clave del API para autenticación"));
        }
    }

    /**
     * Comprueba la existencia de la clave para la api
     * @param $claveApi
     * @return bool true si existe o false en caso contrario
     */
    private function validarClaveApi($claveApi)
    {
        $comando = "SELECT COUNT(" . self::ID_USUARIO . ")" .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CLAVE_API . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $claveApi);

        $sentencia->execute();

        return $sentencia->fetchColumn(0) > 0;
    }

    /**
     * Obtiene el valor de la columna "KUSR" basado en la clave de api
     * @param $claveApi
     * @return null si este no fue encontrado
     */
    private function obtenerIdUsuario($claveApi)
    {
        $comando = "SELECT " . self::ID_USUARIO .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CLAVE_API . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $claveApi);

        if ($sentencia->execute()) {
            $resultado = $sentencia->fetch();
            return $resultado['KUSR'];
        } else
            return null;
    }
}

//DATA
//{"contrasena":"1223345",
//"id_usuario":"SUPERVISOR"}