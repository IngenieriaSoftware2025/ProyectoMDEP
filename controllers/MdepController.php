<?php

namespace Controllers;

use phpseclib3\Net\SFTP;
use Exception;
use Model\ActiveRecord;
use MVC\Router;
use Model\Mdep;
use Mpdf\Mpdf;

class MdepController extends ActiveRecord
{

    public static function renderizarPagina(Router $router)
    {
        $router->render('mdep/index', []);
    }

    public static function renderizarUbicaciones(Router $router)
    {
        $router->render('ubicaciones/index', []);
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
        $_POST['dep_ruta_logo'] = '';

        $_POST['dep_precio'] = '1';
        $_POST['dep_ejto'] = 'N';


        if (empty($_POST['dep_desc_lg']) || strlen($_POST['dep_desc_lg']) < 10) {
            http_response_code(400);
            echo json_encode(['codigo' => 0, 'mensaje' => 'La descripción larga debe tener más de 10 caracteres']);
            exit;
        }

        if (empty($_POST['dep_desc_md']) || strlen($_POST['dep_desc_md']) < 5) {
            http_response_code(400);
            echo json_encode(['codigo' => 0, 'mensaje' => 'La descripción mediana debe tener más de 5 caracteres']);
            exit;
        }

        if (empty($_POST['dep_desc_ct']) || strlen($_POST['dep_desc_ct']) < 3) {
            http_response_code(400);
            echo json_encode(['codigo' => 0, 'mensaje' => 'La descripción corta debe tener más de 3 caracteres']);
            exit;
        }

        if (empty($_POST['dep_clase'])) {
            http_response_code(400);
            echo json_encode(['codigo' => 0, 'mensaje' => 'Clase es requerida']);
            exit;
        }

        try {

            // Manejar imagen
            if (isset($_FILES['dep_imagen']) && $_FILES['dep_imagen']['error'] === UPLOAD_ERR_OK) {
                $rutaImagen = self::subirImagen($_FILES['dep_imagen']);
                if ($rutaImagen) {
                    $_POST['dep_ruta_logo'] = $rutaImagen;
                } else {
                    error_log('SFTP no disponible, dependencia creada sin imagen');
                }
            }

            unset($_POST['dep_llave']);

            error_log('=== DATOS PARA INSERTAR ===');
            error_log(print_r($_POST, true));

            $mdep = new Mdep($_POST);
            $resultado = $mdep->crear();

            if ($resultado['resultado'] == 1) {
                http_response_code(200);
                echo json_encode([
                    'codigo' => 1,
                    'mensaje' => 'Dependencia creada correctamente' .
                        (empty($_POST['dep_ruta_logo']) && isset($_FILES['dep_imagen']) ?
                            ' (imagen no pudo ser subida - servidor no disponible)' : ''),
                    'id' => $resultado['id'] ?? null
                ]);
            } else {
                $mensaje = 'Error al crear dependencia';
                if (isset($resultado['error'])) {
                    if (strpos($resultado['error'], 'ID generado ya existe') !== false) {
                        $mensaje = 'Error de concurrencia, intente nuevamente';
                    } elseif (strpos($resultado['error'], 'Unique constraint') !== false) {
                        $mensaje = 'Datos duplicados. Verifique que la información no exista previamente';
                    } else {
                        $mensaje = $resultado['error'];
                    }
                }

                http_response_code(400);
                echo json_encode(['codigo' => 0, 'mensaje' => $mensaje]);
            }
        } catch (Exception $e) {
            error_log('Exception en guardarAPI: ' . $e->getMessage());

            $mensaje = 'Error interno del servidor';
            if (strpos($e->getMessage(), 'Unique constraint') !== false) {
                $mensaje = 'Datos duplicados. Verifique que la información no exista previamente';
            } elseif (strpos($e->getMessage(), 'ID generado ya existe') !== false) {
                $mensaje = 'Error de concurrencia, intente nuevamente';
            }

            http_response_code(500);
            echo json_encode(['codigo' => 0, 'mensaje' => $mensaje]);
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
        $_POST['dep_latitud'] = trim(htmlspecialchars($_POST['dep_latitud'] ?? ''));
        $_POST['dep_longitud'] = trim(htmlspecialchars($_POST['dep_longitud'] ?? ''));

        $_POST['dep_precio'] = '1';
        $_POST['dep_ejto'] = 'N';

        try {
            // Consulta directa para obtener los datos
            $sql = "SELECT * FROM informix.mdep WHERE dep_llave = ?";
            $stmt = self::$db->prepare($sql);
            $stmt->execute([$id]);
            $dependenciaData = $stmt->fetch();

            if (!$dependenciaData) {
                http_response_code(400);
                echo json_encode(['codigo' => 0, 'mensaje' => 'Dependencia no existe']);
                return;
            }

            // Crear objeto Mdep con los datos existentes
            $dependencia = new Mdep($dependenciaData);

            // IMPORTANTE: Asegurarse que dep_llave esté asignado
            $dependencia->dep_llave = $id; // ← ESTA LÍNEA ES CRUCIAL

            // Manejar imagen
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

            // Actualizar propiedades
            $dependencia->dep_desc_lg = $_POST['dep_desc_lg'];
            $dependencia->dep_desc_md = $_POST['dep_desc_md'];
            $dependencia->dep_desc_ct = $_POST['dep_desc_ct'];
            $dependencia->dep_clase = $_POST['dep_clase'];
            $dependencia->dep_latitud = $_POST['dep_latitud'] ?: null;
            $dependencia->dep_longitud = $_POST['dep_longitud'] ?: null;
            $dependencia->dep_ruta_logo = $_POST['dep_ruta_logo'];

            $resultado = $dependencia->actualizar();

            if ($resultado['resultado'] > 0) {
                http_response_code(200);
                echo json_encode([
                    'codigo' => 1,
                    'mensaje' => 'Dependencia modificada correctamente'
                ]);
            } else {
                throw new Exception('No se pudo actualizar la dependencia');
            }
        } catch (Exception $e) {
            error_log('Error en modificarAPI: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'codigo' => 0,
                'mensaje' => 'Error al modificar dependencia: ' . $e->getMessage()
            ]);
        }
    }


    private static function subirImagen($archivo)
    {
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($archivo['type'], $tiposPermitidos)) {
            return false;
        }

        try {
            $sftpServidor = $_ENV['FILE_SERVER'] ?? '127.0.0.1';
            $sftpUsuario = $_ENV['FILE_USER'] ?? 'ftpuser';
            $sftpPassword = $_ENV['FILE_PASSWORD'] ?? 'ftppassword';
            $rutaRemota = '/upload/PruebaMDEP/';

            $sftp = new SFTP($sftpServidor);
            if (!$sftp->login($sftpUsuario, $sftpPassword)) {
                error_log('Error SFTP: No conexión a ' . $sftpServidor);
                return false;
            }

            $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
            $nombreArchivo = 'img_' . time() . '.' . $extension;

            $resultado = $sftp->put($rutaRemota . $nombreArchivo, $archivo['tmp_name'], SFTP::SOURCE_LOCAL_FILE);
            $sftp->disconnect();

            return $resultado ? $rutaRemota . $nombreArchivo : false;
        } catch (Exception $e) {
            error_log('SFTP Exception: ' . $e->getMessage());
            return false;
        }
    }

public static function deshabilitarAPI()
{
    header('Content-Type: application/json');

    $id = $_POST['dep_llave'] ?? null;
    $justificacion = $_POST['justificacion'] ?? '';

    if (!$id || strlen($justificacion) < 10) {
        echo json_encode(['codigo' => 0, 'mensaje' => 'Datos incompletos']);
        die();
    }

    try {
        // PASO 1: Deshabilitar
        $sql = "UPDATE informix.mdep SET dep_situacion = 0 WHERE dep_llave = " . intval($id);
        self::$db->exec($sql);

        // PASO 2: Obtener datos para PDF
        $sqlSelect = "SELECT dep_desc_lg FROM informix.mdep WHERE dep_llave = " . intval($id);
        $stmt = self::$db->query($sqlSelect);
        $dependencia = $stmt->fetch();

        // PASO 3: Generar PDF OBLIGATORIAMENTE
        if ($dependencia && $dependencia['dep_desc_lg']) {
            $rutaPDF = self::generarYGuardarPDF('DESHABILITACION', $dependencia['dep_desc_lg'], $justificacion, $id);
            if ($rutaPDF) {
                echo json_encode(['codigo' => 1, 'mensaje' => 'Dependencia deshabilitada - PDF: ' . basename($rutaPDF)]);
            } else {
                echo json_encode(['codigo' => 1, 'mensaje' => 'Dependencia deshabilitada (PDF no generado)']);
            }
        } else {
            echo json_encode(['codigo' => 1, 'mensaje' => 'Dependencia deshabilitada (sin datos para PDF)']);
        }

    } catch (Exception $e) {
        error_log('Error en deshabilitarAPI: ' . $e->getMessage());
        echo json_encode(['codigo' => 0, 'mensaje' => 'Error al deshabilitar']);
    }
    die();
}

public static function habilitarAPI()
{
    header('Content-Type: application/json');

    $id = $_POST['dep_llave'] ?? null;
    $justificacion = $_POST['justificacion'] ?? '';

    if (!$id || strlen($justificacion) < 10) {
        echo json_encode(['codigo' => 0, 'mensaje' => 'Datos incompletos']);
        die();
    }

    try {
        // PASO 1: Habilitar
        $sql = "UPDATE informix.mdep SET dep_situacion = 1 WHERE dep_llave = " . intval($id);
        self::$db->exec($sql);

        // PASO 2: Obtener datos para PDF
        $sqlSelect = "SELECT dep_desc_lg FROM informix.mdep WHERE dep_llave = " . intval($id);
        $stmt = self::$db->query($sqlSelect);
        $dependencia = $stmt->fetch();

        // PASO 3: Generar PDF OBLIGATORIAMENTE
        if ($dependencia && $dependencia['dep_desc_lg']) {
            $rutaPDF = self::generarYGuardarPDF('HABILITACION', $dependencia['dep_desc_lg'], $justificacion, $id);
            if ($rutaPDF) {
                echo json_encode(['codigo' => 1, 'mensaje' => 'Dependencia habilitada - PDF: ' . basename($rutaPDF)]);
            } else {
                echo json_encode(['codigo' => 1, 'mensaje' => 'Dependencia habilitada (PDF no generado)']);
            }
        } else {
            echo json_encode(['codigo' => 1, 'mensaje' => 'Dependencia habilitada (sin datos para PDF)']);
        }

    } catch (Exception $e) {
        error_log('Error en habilitarAPI: ' . $e->getMessage());
        echo json_encode(['codigo' => 0, 'mensaje' => 'Error al habilitar']);
    }
    die();
}

// MÉTODO MEJORADO Y GARANTIZADO PARA GENERAR PDF
public static function generarYGuardarPDF($accion, $dependencia, $justificacion, $depId)
{
    try {
        // CREAR PDF CON MPDF
        $mpdf = new Mpdf([
            'format' => 'A4',
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15
        ]);

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; }
                .header { text-align: center; margin-bottom: 30px; }
                .info-box { background-color: #f8f9fa; padding: 15px; margin: 20px 0; }
                .justification { padding: 15px; border: 1px solid #ddd; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>JUSTIFICACIÓN DE ' . strtoupper($accion) . '</h1>
            </div>
            
            <div class="info-box">
                <h3>Información de la Dependencia</h3>
                <p><strong>ID Dependencia:</strong> ' . $depId . '</p>
                <p><strong>Descripción:</strong> ' . $dependencia . '</p>
                <p><strong>Fecha:</strong> ' . date('d/m/Y H:i:s') . '</p>
                <p><strong>Acción Realizada:</strong> ' . $accion . '</p>
            </div>
            
            <div class="justification">
                <h3>Justificación:</h3>
                <p>' . nl2br(htmlspecialchars($justificacion)) . '</p>
            </div>
            
            <div style="margin-top: 50px; text-align: center; font-size: 12px;">
                <p>Sistema MDEP - Generado el ' . date('d/m/Y H:i:s') . '</p>
            </div>
        </body>
        </html>';

        $mpdf->WriteHTML($html);

        // NOMBRE ÚNICO DEL PDF
        $nombrePDF = 'dep_' . $depId . '_' . strtolower($accion) . '_' . date('YmdHis') . '.pdf';
        
        // CREAR PDF EN SERVIDOR LOCAL PRIMERO
        $rutaLocal = sys_get_temp_dir() . '/' . $nombrePDF;
        $mpdf->Output($rutaLocal, 'F');

        // VERIFICAR QUE SE CREÓ
        if (!file_exists($rutaLocal)) {
            error_log("ERROR: PDF no se creó en $rutaLocal");
            return false;
        }

        error_log("PDF creado localmente: $rutaLocal (" . filesize($rutaLocal) . " bytes)");

        // SUBIR A SERVIDOR SFTP
        $servidor = $_ENV['FILE_SERVER'] ?? '127.0.0.1';
        $usuario = $_ENV['FILE_USER'] ?? 'ftpuser';
        $password = $_ENV['FILE_PASSWORD'] ?? 'ftppassword';
        $carpetaRemota = '/upload/PruebaMDEP/JUSTIFICACIONES/';

        $sftp = new SFTP($servidor);
        
        if (!$sftp->login($usuario, $password)) {
            error_log("ERROR: No se pudo conectar a SFTP");
            unlink($rutaLocal);
            return false;
        }

        // CREAR CARPETA SI NO EXISTE
        if (!$sftp->is_dir($carpetaRemota)) {
            if (!$sftp->mkdir($carpetaRemota, 0755, true)) {
                error_log("ERROR: No se pudo crear carpeta $carpetaRemota");
                $sftp->disconnect();
                unlink($rutaLocal);
                return false;
            }
        }

        // SUBIR ARCHIVO
        $rutaRemotaCompleta = $carpetaRemota . $nombrePDF;
        $resultado = $sftp->put($rutaRemotaCompleta, $rutaLocal, SFTP::SOURCE_LOCAL_FILE);
        
        if ($resultado) {
            error_log("SUCCESS: PDF subido a $rutaRemotaCompleta");
            $sftp->disconnect();
            unlink($rutaLocal);
            return $rutaRemotaCompleta;
        } else {
            error_log("ERROR: No se pudo subir PDF");
            $sftp->disconnect();
            unlink($rutaLocal);
            return false;
        }

    } catch (Exception $e) {
        error_log('ERROR FATAL generando PDF: ' . $e->getMessage());
        return false;
    }
}

// MÉTODO CORREGIDO PARA OBTENER PDF
public static function obtenerPDFAPI()
{
    error_log("=== obtenerPDFAPI INICIADO ===");
    
    $id = $_GET['id'] ?? null;
    error_log("ID recibido: " . ($id ?? 'NULL'));

    if (!$id) {
        error_log("ERROR: ID no proporcionado");
        http_response_code(400);
        echo json_encode(['codigo' => 0, 'mensaje' => 'ID de dependencia requerido']);
        return;
    }

    try {
        $servidor = $_ENV['FILE_SERVER'] ?? '127.0.0.1';
        $usuario = $_ENV['FILE_USER'] ?? 'ftpuser';  
        $password = $_ENV['FILE_PASSWORD'] ?? 'ftppassword';
        $carpetaRemota = '/upload/PruebaMDEP/JUSTIFICACIONES/';

        error_log("Conectando a SFTP: $servidor con usuario: $usuario");

        $sftp = new SFTP($servidor);
        if (!$sftp->login($usuario, $password)) {
            error_log("ERROR: Fallo login SFTP");
            http_response_code(500);
            echo json_encode(['codigo' => 0, 'mensaje' => 'Error conexión servidor']);
            return;
        }

        error_log("LOGIN SFTP exitoso");

        // VERIFICAR QUE LA CARPETA EXISTE
        if (!$sftp->is_dir($carpetaRemota)) {
            error_log("ERROR: Carpeta no existe: $carpetaRemota");
            $sftp->disconnect();
            http_response_code(404);
            echo json_encode(['codigo' => 0, 'mensaje' => 'Carpeta de PDFs no encontrada']);
            return;
        }

        // LISTAR ARCHIVOS
        $archivos = $sftp->nlist($carpetaRemota);
        error_log("Archivos encontrados: " . print_r($archivos, true));

        $pdfEncontrado = null;

        if (is_array($archivos)) {
            foreach ($archivos as $archivo) {
                // BUSCAR POR PATRÓN: dep_{ID}_
                if (strpos($archivo, 'dep_' . $id . '_') !== false && strpos($archivo, '.pdf') !== false) {
                    $pdfEncontrado = $carpetaRemota . $archivo;
                    error_log("PDF ENCONTRADO: $pdfEncontrado");
                    break;
                }
            }
        }

        if (!$pdfEncontrado) {
            error_log("No se encontró PDF para dependencia $id");
            $sftp->disconnect();
            http_response_code(404);
            echo json_encode(['codigo' => 0, 'mensaje' => 'No hay PDF de justificación para esta dependencia']);
            return;
        }

        // DESCARGAR PDF
        $contenidoPDF = $sftp->get($pdfEncontrado);
        $sftp->disconnect();

        if ($contenidoPDF === false || empty($contenidoPDF)) {
            error_log("ERROR: No se pudo descargar PDF o está vacío");
            http_response_code(500);
            echo json_encode(['codigo' => 0, 'mensaje' => 'Error al descargar PDF']);
            return;
        }

        error_log("PDF descargado exitosamente: " . strlen($contenidoPDF) . " bytes");

        // SERVIR PDF AL NAVEGADOR
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="justificacion_dep_' . $id . '.pdf"');
        header('Content-Length: ' . strlen($contenidoPDF));
        echo $contenidoPDF;

        error_log("PDF enviado al navegador exitosamente");

    } catch (Exception $e) {
        error_log('ERROR CRÍTICO en obtenerPDFAPI: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['codigo' => 0, 'mensaje' => 'Error interno del servidor']);
    }
}
}
