<?php

/**
 * Excepción personalizada para el envío del estado
 */
class ExcepcionApi extends PDOException
{
    public $estado;

    public function __construct($estado, $mensaje, $codigo = 400)
    {
        $this->estado = $estado;
        $this->message = $mensaje;
        $this->code = $codigo;
    }

}