<?php

namespace Controllers;

use phpseclib\Net\SFTP;
use Exception;
use Model\ActiveRecord;
use MVC\Router;
use Model\Mdep;
use Mpdf\Mpdf;

class MdepController extends ActiveRecord{
    
    public static function renderizarPagina(Router $router){
        $router->render('mdep/index', []);
    }

    public static function guardarAPI()
    {
        header('Content-Type: application/json');
    
        $_POST['dep_desc_lg'] = trim(htmlspecialchars($_POST['dep_desc_lg']));
        $_POST['dep_desc_md'] = trim(htmlspecialchars($_POST['dep_desc_md']));
        $_POST['dep_desc_ct'] = trim(htmlspecialchars($_POST['dep_desc_ct']));
        $_POST['dep_clase'] = trim(htmlspecialchars($_POST['dep_clase']));
        $_POST['dep_latitud'] = trim(htmlspecialchars($_POST['dep_latitud'] ?? ''));
        $_POST['dep_longitud'] = trim(htmlspecialchars($_POST['dep_longitud'] ?? ''));
        $_POST['dep_ruta_logo'] = trim(htmlspecialchars($_POST['dep_ruta_logo'] ?? ''));
        
         // Campos con valores por defecto
        $_POST['dep_precio'] = '1';
        $_POST['dep_ejto'] = 'N';

        // Validaciones campos obligatorios
        if (strlen(empty($_POST['dep_desc_lg'])) < 10) {
            http_response_code(400);
            echo json_encode([
                'codigo' => 0,
                'mensaje' => 'La descripción larga debe tener más de 1 carácter'
            ]);
            exit;
        }
        
        if (strlen(empty($_POST['dep_desc_md'])) < 5) {
            http_response_code(400);
            echo json_encode([
                'codigo' => 0,
                'mensaje' => 'La descripción mediana debe tener más de 1 carácter'
            ]);
            exit;
        }
        
        if (strlen(empty($_POST['dep_desc_ct'])) < 3) {
            http_response_code(400);
            echo json_encode([
                'codigo' => 0,
                'mensaje' => 'La descripción corta debe tener más de 1 carácter'
            ]);
            exit;
        }
        if (empty($_POST['dep_clase'])) {
            http_response_code(400);
            echo json_encode(['codigo' => 0, 'mensaje' => 'Clase es requerida']);
            exit;
        }

        try {
            // Subir imagen si existe
            if (isset($_FILES['dep_imagen']) && $_FILES['dep_imagen']['error'] === UPLOAD_ERR_OK) {
                $rutaImagen = self::subirImagen($_FILES['dep_imagen']);
                if ($rutaImagen) {
                    $_POST['dep_ruta_logo'] = $rutaImagen;
                }
            }
            
            $mdep = new Mdep($_POST);
            $resultado = $mdep->crear();

            if($resultado['resultado'] == 1){
                http_response_code(200);
                echo json_encode([
                    'codigo' => 1,
                    'mensaje' => 'Dependencia creada correctamente'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'codigo' => 0,
                    'mensaje' => 'Error al crear dependencia'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'codigo' => 0,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    public static function buscarAPI()
    {
        header('Content-Type: application/json');

        try {
            $sql = "SELECT * FROM informix.mdep ORDER BY dep_llave DESC";
            $data = self::fetchArray($sql);

            http_response_code(200);
            echo json_encode([
                'codigo' => 1,
                'mensaje' => 'Dependencias obtenidas correctamente',
                'data' => $data
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'codigo' => 0,
                'mensaje' => 'Error al obtener dependencias'
            ]);
        }
    }

    public static function modificarAPI()
    {
        header('Content-Type: application/json');

        $id = $_POST['dep_llave'];
        
        $_POST['dep_desc_lg'] = trim(htmlspecialchars($_POST['dep_desc_lg']));
        $_POST['dep_desc_md'] = trim(htmlspecialchars($_POST['dep_desc_md']));
        $_POST['dep_desc_ct'] = trim(htmlspecialchars($_POST['dep_desc_ct']));
        $_POST['dep_clase'] = trim(htmlspecialchars($_POST['dep_clase']));
        $_POST['dep_precio'] = trim(htmlspecialchars($_POST['dep_precio'] ?? ''));
        $_POST['dep_ejto'] = trim(htmlspecialchars($_POST['dep_ejto'] ?? ''));
        $_POST['dep_latitud'] = trim(htmlspecialchars($_POST['dep_latitud'] ?? ''));
        $_POST['dep_longitud'] = trim(htmlspecialchars($_POST['dep_longitud'] ?? ''));

        try {
            $dependencia = Mdep::find($id);
            if (!$dependencia) {
                http_response_code(400);
                echo json_encode(['codigo' => 0, 'mensaje' => 'Dependencia no existe']);
                return;
            }

            // Subir nueva imagen si existe
            if (isset($_FILES['dep_imagen']) && $_FILES['dep_imagen']['error'] === UPLOAD_ERR_OK) {
                $rutaImagen = self::subirImagen($_FILES['dep_imagen']);
                if ($rutaImagen) {
                    $_POST['dep_ruta_logo'] = $rutaImagen;
                } else {
                    $_POST['dep_ruta_logo'] = $dependencia->dep_ruta_logo;
                }
            } else {
                $_POST['dep_ruta_logo'] = $dependencia->dep_ruta_logo;
            }

            $dependencia->sincronizar($_POST);
            $dependencia->actualizar();

            http_response_code(200);
            echo json_encode([
                'codigo' => 1,
                'mensaje' => 'Dependencia modificada correctamente'
            ]);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'codigo' => 0,
                'mensaje' => 'Error al modificar dependencia'
            ]);
        }
    }

    public static function deshabilitarAPI()
    {
        header('Content-Type: application/json');
        
        $id = $_POST['dep_llave'];
        $justificacion = trim(htmlspecialchars($_POST['justificacion']));

        if (strlen($justificacion) < 10) {
            http_response_code(400);
            echo json_encode([
                'codigo' => 0,
                'mensaje' => 'La justificación debe tener al menos 10 caracteres'
            ]);
            exit;
        }

        try {
            $dependencia = Mdep::find($id);
            if (!$dependencia) {
                http_response_code(400);
                echo json_encode(['codigo' => 0, 'mensaje' => 'Dependencia no existe']);
                return;
            }

            // Generar PDF
            $rutaPDF = self::generarPDF('DESHABILITACION', $dependencia->dep_desc_lg, $justificacion);
            
            // Deshabilitar
            Mdep::DeshabilitarDependencia($id);

            http_response_code(200);
            echo json_encode([
                'codigo' => 1,
                'mensaje' => 'Dependencia deshabilitada correctamente',
                'pdf_generado' => $rutaPDF
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'codigo' => 0,
                'mensaje' => 'Error al deshabilitar'
            ]);
        }
    }

    public static function habilitarAPI()
    {
        header('Content-Type: application/json');
        
        $id = $_POST['dep_llave'];
        $justificacion = trim(htmlspecialchars($_POST['justificacion']));

        if (strlen($justificacion) < 10) {
            http_response_code(400);
            echo json_encode([
                'codigo' => 0,
                'mensaje' => 'La justificación debe tener al menos 10 caracteres'
            ]);
            exit;
        }

        try {
            $dependencia = Mdep::find($id);
            if (!$dependencia) {
                http_response_code(400);
                echo json_encode(['codigo' => 0, 'mensaje' => 'Dependencia no existe']);
                return;
            }

            // Generar PDF
            $rutaPDF = self::generarPDF('HABILITACION', $dependencia->dep_desc_lg, $justificacion);
            
            // Habilitar
            Mdep::HabilitarDependencia($id);

            http_response_code(200);
            echo json_encode([
                'codigo' => 1,
                'mensaje' => 'Dependencia habilitada correctamente',
                'pdf_generado' => $rutaPDF
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'codigo' => 0,
                'mensaje' => 'Error al habilitar'
            ]);
        }
    }

    private static function subirImagen($archivo) {
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($archivo['type'], $tiposPermitidos)) {
            return false;
        }

        $sftpServidor = $_ENV['FILE_SERVER'] ?? '127.0.0.1';
        $sftpUsuario = $_ENV['FILE_USER'] ?? 'ftpuser';
        $sftpPassword = $_ENV['FILE_PASSWORD'] ?? 'ftppassword';
        $rutaRemota = '/upload/PruebaMDEP/';

        $sftp = new SFTP($sftpServidor);
        if (!$sftp->login($sftpUsuario, $sftpPassword)) {
            return false;
        }

        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombreArchivo = 'img_' . time() . '.' . $extension;
        
        $resultado = $sftp->put($rutaRemota . $nombreArchivo, $archivo['tmp_name'], SFTP::SOURCE_LOCAL_FILE);
        $sftp->disconnect();
        
        return $resultado ? $rutaRemota . $nombreArchivo : false;
    }

    private static function generarPDF($accion, $dependencia, $justificacion) {
        $mpdf = new Mpdf(['format' => 'A4']);
        
        $html = '
        <h1>Justificación de ' . $accion . '</h1>
        <p><strong>Dependencia:</strong> ' . $dependencia . '</p>
        <p><strong>Fecha:</strong> ' . date('d/m/Y H:i:s') . '</p>
        <p><strong>Justificación:</strong></p>
        <p>' . nl2br($justificacion) . '</p>
        ';
        
        $mpdf->WriteHTML($html);
        
        $nombrePDF = strtolower($accion) . '_' . time() . '.pdf';
        $rutaTemporal = sys_get_temp_dir() . '/' . $nombrePDF;
        $mpdf->Output($rutaTemporal, 'F');
        
        // Subir PDF
        $sftpServidor = $_ENV['FILE_SERVER'] ?? '127.0.0.1';
        $sftpUsuario = $_ENV['FILE_USER'] ?? 'ftpuser';
        $sftpPassword = $_ENV['FILE_PASSWORD'] ?? 'ftppassword';
        $rutaRemotaPDF = '/upload/PruebaMDEP/JUSTIFICACIONES/';

        $sftp = new SFTP($sftpServidor);
        if ($sftp->login($sftpUsuario, $sftpPassword)) {
            $sftp->put($rutaRemotaPDF . $nombrePDF, $rutaTemporal, SFTP::SOURCE_LOCAL_FILE);
            $sftp->disconnect();
        }
        
        unlink($rutaTemporal);
        return $rutaRemotaPDF . $nombrePDF;
    }
}