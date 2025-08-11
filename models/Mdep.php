<?php

namespace Model;

class Mdep extends ActiveRecord
{

    public static $tabla = 'informix.mdep';

    public static $columnasDB = [
        'dep_llave',
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

    public function __construct($args = [])
    {
         if (isset($args['dep_llave']) && !empty($args['dep_llave']) && is_numeric($args['dep_llave'])) {
        $this->dep_llave = $args['dep_llave'];
    }

        $this->dep_desc_lg = $args['dep_desc_lg'] ?? '';
        $this->dep_desc_md = $args['dep_desc_md'] ?? '';
        $this->dep_desc_ct = $args['dep_desc_ct'] ?? '';
        $this->dep_clase = $args['dep_clase'] ?? '';
        $this->dep_precio = $args['dep_precio'] ?? '1';
        $this->dep_ejto = $args['dep_ejto'] ?? 'N';

        $this->dep_latitud = !empty($args['dep_latitud']) ? $args['dep_latitud'] : null;
        $this->dep_longitud = !empty($args['dep_longitud']) ? $args['dep_longitud'] : null;
        $this->dep_ruta_logo = !empty($args['dep_ruta_logo']) ? $args['dep_ruta_logo'] : null;

        $this->dep_situacion = $args['dep_situacion'] ?? 1;
    }

    public function crear()
{
    error_log('=== CREAR MDEP - INCREMENTO +5 CON WHILE ===');

    try {
        
        $queryMaxId = "SELECT MAX(dep_llave) FROM informix.mdep";
        $stmt = self::$db->prepare($queryMaxId);
        $stmt->execute();
        
        $maxId = $stmt->fetchColumn();
        
        $maxId = intval($maxId);
        if ($maxId <= 0) {
            $maxId = 10050; 
        }
        
        error_log("MAX ID encontrado: $maxId");

        $siguienteId = $maxId + 5;
        error_log("Siguiente ID calculado: $siguienteId");
        
        if ($siguienteId > 32767) {
            return [
                'resultado' => 0,
                'error' => 'Se alcanzó el límite máximo de SMALLINT (32,767)'
            ];
        }

        $queryVerificar = "SELECT COUNT(*) FROM informix.mdep WHERE dep_llave = ?";
        $stmtVerificar = self::$db->prepare($queryVerificar);
        $stmtVerificar->execute([$siguienteId]);
        $existe = $stmtVerificar->fetchColumn();

        if ($existe > 0) {
            error_log("ID $siguienteId ya existe, buscando siguiente disponible...");
            
            while ($existe > 0 && $siguienteId <= 32767) {
                $siguienteId += 5;
                $stmtVerificar->execute([$siguienteId]);
                $existe = $stmtVerificar->fetchColumn();
            }
        }

        if ($siguienteId > 32767) {
            return [
                'resultado' => 0,
                'error' => 'No hay IDs disponibles en el rango SMALLINT'
            ];
        }

        error_log("ID final asignado: $siguienteId");

        $this->dep_llave = $siguienteId;

            $insertQuery = "INSERT INTO informix.mdep (
            dep_llave, dep_desc_lg, dep_desc_md, dep_desc_ct, dep_clase, 
            dep_precio, dep_ejto, dep_latitud, dep_longitud, 
            dep_ruta_logo, dep_situacion
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $insertStmt = self::$db->prepare($insertQuery);

            $parametros = [
                $this->dep_llave,
                $this->dep_desc_lg,
                $this->dep_desc_md,
                $this->dep_desc_ct,
                $this->dep_clase,
                $this->dep_precio,
                $this->dep_ejto,
                $this->dep_latitud,
                $this->dep_longitud,
                $this->dep_ruta_logo,
                $this->dep_situacion
            ];

            $resultado = $insertStmt->execute($parametros);

            if ($resultado && $insertStmt->rowCount() > 0) {
                error_log("=== INSERT EXITOSO ===");
                error_log("ID asignado: {$this->dep_llave}");
                error_log("Próximo ID será: " . ($this->dep_llave + 5));

                return [
                    'resultado' => $insertStmt->rowCount(),
                    'id' => $this->dep_llave
                ];
            } else {
                $errorInfo = $insertStmt->errorInfo();
                return [
                    'resultado' => 0,
                    'error' => 'Error INSERT: ' . ($errorInfo[2] ?? 'Error desconocido')
                ];
            }
        } catch (\Exception $e) { 
            error_log('Error: ' . $e->getMessage());
            return [
                'resultado' => 0,
                'error' => 'Error: ' . $e->getMessage()
            ];
        }
    }

  public function actualizar() {
    if(!$this->dep_llave) {
        throw new \Exception('No se puede actualizar sin dep_llave');
    }
    
    try {
        $query = "UPDATE informix.mdep SET 
            dep_desc_lg = ?, dep_desc_md = ?, dep_desc_ct = ?, dep_clase = ?, 
            dep_precio = ?, dep_ejto = ?, dep_latitud = ?, dep_longitud = ?, 
            dep_ruta_logo = ?, dep_situacion = ?
            WHERE dep_llave = ?";
        
        $stmt = self::$db->prepare($query);
        
        $parametros = [
            $this->dep_desc_lg,
            $this->dep_desc_md,
            $this->dep_desc_ct,
            $this->dep_clase,
            $this->dep_precio,
            $this->dep_ejto,
            $this->dep_latitud,
            $this->dep_longitud,
            $this->dep_ruta_logo,
            $this->dep_situacion,
            $this->dep_llave
        ];
        
        $resultado = $stmt->execute($parametros);
        
        return [
            'resultado' => $stmt->rowCount(),
            'id' => $this->dep_llave
        ];
        
    } catch (\Exception $e) { 
        error_log('Error en actualizar: ' . $e->getMessage());
        return [
            'resultado' => 0,
            'error' => $e->getMessage()
        ];
    }
}

    public static function DeshabilitarDependencia($id)
{
    $sql = "UPDATE informix.mdep SET dep_situacion = 0 WHERE dep_llave = ?";
    $stmt = self::$db->prepare($sql);
    return $stmt->execute([$id]);
}

public static function HabilitarDependencia($id)
{
    $sql = "UPDATE informix.mdep SET dep_situacion = 1 WHERE dep_llave = ?";
    $stmt = self::$db->prepare($sql);
    return $stmt->execute([$id]);
}
}

