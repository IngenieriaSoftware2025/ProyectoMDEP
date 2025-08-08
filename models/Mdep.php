<?php

namespace Model;

class Mdep extends ActiveRecord {

    public static $tabla = 'informix.mdep';
    public static $columnasDB = [
        'dep_desc_lg',
        'dep_desc_md',
        'dep_desc_ct',
        'dep_clase',
        'dep_precio',
        'dep_ejto',
        'dep_latitud',
        'dep_longitud',
        'dep_ruta_logo',
        'dep_situacion'
    ];

    public static $idTabla = 'dep_llave';
    public $dep_llave;
    public $dep_desc_lg;
    public $dep_desc_md;
    public $dep_desc_ct;
    public $dep_clase;
    public $dep_precio;
    public $dep_ejto;
    public $dep_latitud;
    public $dep_longitud;
    public $dep_ruta_logo;
    public $dep_situacion;

    public function __construct($args = []){
        $this->dep_llave = $args['dep_llave'] ?? null;
        $this->dep_desc_lg = $args['dep_desc_lg'] ?? '';
        $this->dep_desc_md = $args['dep_desc_md'] ?? '';
        $this->dep_desc_ct = $args['dep_desc_ct'] ?? '';
        $this->dep_clase = $args['dep_clase'] ?? '';
        $this->dep_precio = $args['dep_precio'] ?? '1';
        $this->dep_ejto = $args['dep_ejto'] ?? 'N';
        $this->dep_latitud = $args['dep_latitud'] ?? '';
        $this->dep_longitud = $args['dep_longitud'] ?? '';
        $this->dep_ruta_logo = $args['dep_ruta_logo'] ?? '';
        $this->dep_situacion = $args['dep_situacion'] ?? 1;
    }

    public static function DeshabilitarDependencia($id){
        $sql = "UPDATE informix.mdep SET dep_situacion = 0 WHERE dep_llave = $id";
        return self::SQL($sql);
    }

    public static function HabilitarDependencia($id){
        $sql = "UPDATE informix.mdep SET dep_situacion = 1 WHERE dep_llave = $id";
        return self::SQL($sql);
    }
}