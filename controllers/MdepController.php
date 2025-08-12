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

        // VALIDACIONES
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
            unset($_POST['dep_llave']);

            error_log('=== DATOS PARA INSERTAR ===');
            error_log(print_r($_POST, true));

            // CREAR DEPENDENCIA PRIMERO
            $mdep = new Mdep($_POST);
            $resultado = $mdep->crear();

            if ($resultado['resultado'] == 1) {
                $depId = $resultado['id'];
                $mensajeCompleto = 'Dependencia creada correctamente';

                // MANEJAR LOGO SI SE SUBIÓ
                if (isset($_FILES['dep_imagen']) && $_FILES['dep_imagen']['error'] === UPLOAD_ERR_OK) {
                    $resultadoLogo = self::subirLogoRobusto($_FILES['dep_imagen'], $depId);
                    
                    if ($resultadoLogo['exito']) {
                        // ACTUALIZAR LA DEPENDENCIA CON LA RUTA DEL LOGO
                        $sqlUpdate = "UPDATE informix.mdep SET dep_ruta_logo = ? WHERE dep_llave = ?";
                        $stmtUpdate = self::$db->prepare($sqlUpdate);
                        $stmtUpdate->execute([$resultadoLogo['ruta'], $depId]);
                        
                        $mensajeCompleto .= ' - Logo subido: ' . basename($resultadoLogo['ruta']);
                    } else {
                        $mensajeCompleto .= ' - Logo no pudo subirse: ' . $resultadoLogo['mensaje'];
                    }
                }

                http_response_code(200);
                echo json_encode([
                    'codigo' => 1,
                    'mensaje' => $mensajeCompleto,
                    'id' => $depId
                ]);
            } else {
                $mensaje = 'Error al crear dependencia';
                if (isset($resultado['error'])) {
                    $mensaje = $resultado['error'];
                }

                http_response_code(400);
                echo json_encode(['codigo' => 0, 'mensaje' => $mensaje]);
            }
        } catch (Exception $e) {
            error_log('Exception en guardarAPI: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['codigo' => 0, 'mensaje' => 'Error interno del servidor']);
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
            // Obtener datos existentes
            $sql = "SELECT * FROM informix.mdep WHERE dep_llave = ?";
            $stmt = self::$db->prepare($sql);
            $stmt->execute([$id]);
            $dependenciaData = $stmt->fetch();

            if (!$dependenciaData) {
                http_response_code(400);
                echo json_encode(['codigo' => 0, 'mensaje' => 'Dependencia no existe']);
                return;
            }

            $dependencia = new Mdep($dependenciaData);
            $dependencia->dep_llave = $id;
            
            $logoAnterior = $dependencia->dep_ruta_logo;
            $mensajeCompleto = 'Dependencia modificada correctamente';

            // MANEJAR LOGO SI SE SUBIÓ UNO NUEVO
            if (isset($_FILES['dep_imagen']) && $_FILES['dep_imagen']['error'] === UPLOAD_ERR_OK) {
                $resultadoLogo = self::subirLogoRobusto($_FILES['dep_imagen'], $id, $logoAnterior);
                
                if ($resultadoLogo['exito']) {
                    $_POST['dep_ruta_logo'] = $resultadoLogo['ruta'];
                    $mensajeCompleto .= ' - Logo actualizado: ' . basename($resultadoLogo['ruta']);
                } else {
                    $_POST['dep_ruta_logo'] = $logoAnterior; // Mantener el anterior
                    $mensajeCompleto .= ' - Logo no se pudo actualizar: ' . $resultadoLogo['mensaje'];
                }
            } else {
                $_POST['dep_ruta_logo'] = $logoAnterior; // Mantener el existente
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
                    'mensaje' => $mensajeCompleto
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

    // MÉTODO CORREGIDO PARA SUBIR LOGO
    public static function subirLogoRobusto($archivo, $depId = null, $logoAnterior = null)
    {
        try {
            // Validar que se recibió un archivo
            if (!isset($archivo) || $archivo['error'] !== UPLOAD_ERR_OK) {
                return [
                    'exito' => false,
                    'mensaje' => 'No se recibió archivo válido. Error: ' . ($archivo['error'] ?? 'desconocido'),
                    'ruta' => null
                ];
            }

            // VALIDACIONES DE ARCHIVO
            $nombreArchivo = $archivo['name'];
            $archivoTemporal = $archivo['tmp_name'];
            $tamanoArchivo = $archivo['size'];

            error_log("=== SUBIDA LOGO INICIADA ===");
            error_log("Archivo: $nombreArchivo, Tamaño: $tamanoArchivo bytes");

            // 1. Validar extensión
            $extensionArchivo = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
            $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

            if (!in_array($extensionArchivo, $extensionesPermitidas)) {
                return [
                    'exito' => false,
                    'mensaje' => "Solo se permiten imágenes: JPG, JPEG, PNG, GIF, WEBP, BMP",
                    'ruta' => null
                ];
            }

            // 2. Validar tamaño (máximo 5MB para logos)
            if ($tamanoArchivo > 5 * 1024 * 1024) {
                return [
                    'exito' => false,
                    'mensaje' => "El logo no puede ser mayor a 5MB",
                    'ruta' => null
                ];
            }

            // 3. Validar que sea realmente una imagen
            $infoImagen = @getimagesize($archivoTemporal);
            if ($infoImagen === false) {
                return [
                    'exito' => false,
                    'mensaje' => "El archivo no es una imagen válida",
                    'ruta' => null
                ];
            }

            // CONFIGURACIÓN SFTP CON TUS VARIABLES ENV
            $servidorSftp = $_ENV['FILE_SERVER'] ?? '127.0.0.1';
            $puertoSftp = $_ENV['FILE_PORT'] ?? 22;
            $usuarioSftp = $_ENV['FILE_USER'] ?? 'ftpuser';
            $passwordSftp = $_ENV['FILE_PASSWORD'] ?? 'ftppassword';

            error_log("Conectando SFTP: $servidorSftp:$puertoSftp con usuario: $usuarioSftp");

            // CONECTAR AL SERVIDOR SFTP
            $conexionSftp = new SFTP($servidorSftp, $puertoSftp);
            if (!$conexionSftp->login($usuarioSftp, $passwordSftp)) {
                error_log("ERROR: Fallo login SFTP");
                return [
                    'exito' => false,
                    'mensaje' => 'Error de autenticación al servidor SFTP. Verificar credenciales.',
                    'ruta' => null
                ];
            }

            error_log("LOGIN SFTP exitoso");

            // CARPETA BASE SEGÚN TU ESTRUCTURA
            $carpetaBase = '/upload/PruebaMDEP/';
            
            // Verificar que existe la carpeta base
            if (!$conexionSftp->is_dir($carpetaBase)) {
                if (!$conexionSftp->mkdir($carpetaBase, 0755, true)) {
                    error_log("No se pudo crear carpeta base: $carpetaBase");
                    $conexionSftp->disconnect();
                    return [
                        'exito' => false,
                        'mensaje' => 'No se pudo acceder a la carpeta del servidor',
                        'ruta' => null
                    ];
                }
            }

            // ELIMINAR LOGO ANTERIOR SI EXISTE
            if ($logoAnterior && $conexionSftp->file_exists($logoAnterior)) {
                $conexionSftp->delete($logoAnterior);
                error_log("Logo anterior eliminado: $logoAnterior");
            }

            // GENERAR NOMBRE ÚNICO
            $timestamp = date('YmdHis');
            $idParte = $depId ? "dep_{$depId}_" : '';
            $nombreUnico = $idParte . 'logo_' . $timestamp . '.' . $extensionArchivo;
            $rutaCompletaSftp = $carpetaBase . $nombreUnico;

            error_log("Subiendo archivo a: $rutaCompletaSftp");

            // SUBIR ARCHIVO AL SFTP
            if ($conexionSftp->put($rutaCompletaSftp, $archivoTemporal, SFTP::SOURCE_LOCAL_FILE)) {
                $conexionSftp->disconnect();
                
                error_log("SUCCESS: Logo subido exitosamente");
                
                return [
                    'exito' => true,
                    'mensaje' => 'Logo subido correctamente',
                    'ruta' => $rutaCompletaSftp
                ];
            } else {
                $errorSftp = $conexionSftp->getLastSFTPError();
                $conexionSftp->disconnect();
                
                error_log("ERROR: Fallo al subir archivo - $errorSftp");
                
                return [
                    'exito' => false,
                    'mensaje' => 'Error al subir el logo al servidor: ' . $errorSftp,
                    'ruta' => null
                ];
            }

        } catch (Exception $e) {
            error_log('EXCEPCIÓN en subirLogoRobusto: ' . $e->getMessage());
            
            return [
                'exito' => false,
                'mensaje' => 'Error interno al procesar el logo: ' . $e->getMessage(),
                'ruta' => null
            ];
        }
    }

    // MÉTODO CORREGIDO PARA DESHABILITAR CON PDF
    public static function deshabilitarAPI()
    {
        header('Content-Type: application/json');
        
        $id = $_POST['dep_llave'] ?? null;
        $justificacion = $_POST['justificacion'] ?? '';

        if (!$id || strlen($justificacion) < 10) {
            echo json_encode(['codigo' => 0, 'mensaje' => 'Datos incompletos. La justificación debe tener al menos 10 caracteres.']);
            die();
        }

        try {
            // Obtener información de la dependencia
            $sql = "SELECT dep_desc_lg FROM informix.mdep WHERE dep_llave = ?";
            $stmt = self::$db->prepare($sql);
            $stmt->execute([$id]);
            $dependencia = $stmt->fetch();

            if (!$dependencia) {
                echo json_encode(['codigo' => 0, 'mensaje' => 'Dependencia no encontrada']);
                die();
            }

            // Deshabilitar dependencia
            $sql = "UPDATE informix.mdep SET dep_situacion = 0 WHERE dep_llave = " . intval($id);
            self::$db->exec($sql);

            // Generar PDF de justificación
            $rutaPDF = self::generarPDFSeguro('DESHABILITACIÓN', $dependencia['dep_desc_lg'], $justificacion, $id);

            $mensaje = 'Dependencia deshabilitada correctamente';
            if ($rutaPDF) {
                $mensaje .= ' - PDF generado: ' . basename($rutaPDF);
            } else {
                $mensaje .= ' - PDF no se pudo generar';
            }
            
            echo json_encode(['codigo' => 1, 'mensaje' => $mensaje]);
        } catch (Exception $e) {
            error_log('Error en deshabilitarAPI: ' . $e->getMessage());
            echo json_encode(['codigo' => 0, 'mensaje' => 'Error al deshabilitar: ' . $e->getMessage()]);
        }
        die();
    }

    // MÉTODO CORREGIDO PARA HABILITAR CON PDF
    public static function habilitarAPI()
    {
        header('Content-Type: application/json');
        
        $id = $_POST['dep_llave'] ?? null;
        $justificacion = $_POST['justificacion'] ?? '';

        if (!$id || strlen($justificacion) < 10) {
            echo json_encode(['codigo' => 0, 'mensaje' => 'Datos incompletos. La justificación debe tener al menos 10 caracteres.']);
            die();
        }

        try {
            // Obtener información de la dependencia
            $sql = "SELECT dep_desc_lg FROM informix.mdep WHERE dep_llave = ?";
            $stmt = self::$db->prepare($sql);
            $stmt->execute([$id]);
            $dependencia = $stmt->fetch();

            if (!$dependencia) {
                echo json_encode(['codigo' => 0, 'mensaje' => 'Dependencia no encontrada']);
                die();
            }

            // Habilitar dependencia
            $sql = "UPDATE informix.mdep SET dep_situacion = 1 WHERE dep_llave = " . intval($id);
            self::$db->exec($sql);

            // Generar PDF de justificación
            $rutaPDF = self::generarPDFSeguro('HABILITACIÓN', $dependencia['dep_desc_lg'], $justificacion, $id);

            $mensaje = 'Dependencia habilitada correctamente';
            if ($rutaPDF) {
                $mensaje .= ' - PDF generado: ' . basename($rutaPDF);
            } else {
                $mensaje .= ' - PDF no se pudo generar';
            }
            
            echo json_encode(['codigo' => 1, 'mensaje' => $mensaje]);
        } catch (Exception $e) {
            error_log('Error en habilitarAPI: ' . $e->getMessage());
            echo json_encode(['codigo' => 0, 'mensaje' => 'Error al habilitar: ' . $e->getMessage()]);
        }
        die();
    }

    // MÉTODO PARA SERVIR IMÁGENES
    public static function servirImagenAPI()
    {
        $ruta = $_GET['ruta'] ?? '';
        
        if (empty($ruta)) {
            http_response_code(400);
            echo json_encode(['error' => 'Ruta no especificada']);
            return;
        }

        try {
            $servidor = $_ENV['FILE_SERVER'] ?? '127.0.0.1';
            $puerto = $_ENV['FILE_PORT'] ?? 22;
            $usuario = $_ENV['FILE_USER'] ?? 'ftpuser';
            $password = $_ENV['FILE_PASSWORD'] ?? 'ftppassword';

            $sftp = new SFTP($servidor, $puerto);

            if (!$sftp->login($usuario, $password)) {
                throw new Exception('No se pudo conectar al servidor SFTP');
            }

            if (!$sftp->file_exists($ruta)) {
                http_response_code(404);
                echo json_encode(['error' => 'Imagen no encontrada']);
                return;
            }

            $contenido = $sftp->get($ruta);
            $sftp->disconnect();

            if ($contenido === false) {
                throw new Exception('Error al obtener la imagen');
            }

            // Determinar tipo MIME
            $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
            $tiposMime = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'bmp' => 'image/bmp'
            ];

            $tipoMime = $tiposMime[$extension] ?? 'application/octet-stream';

            header('Content-Type: ' . $tipoMime);
            header('Content-Length: ' . strlen($contenido));
            header('Cache-Control: public, max-age=3600');
            
            echo $contenido;

        } catch (Exception $e) {
            error_log('Error en servirImagenAPI: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error interno del servidor']);
        }
    }

    // MÉTODO FALTANTE PARA OBTENER PDFs
    public static function obtenerPDFAPI()
    {
        header('Content-Type: application/json');
        
        $id = $_GET['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['codigo' => 0, 'mensaje' => 'ID de dependencia requerido']);
            return;
        }

        try {
            $servidor = $_ENV['FILE_SERVER'] ?? '127.0.0.1';
            $puerto = $_ENV['FILE_PORT'] ?? 22;
            $usuario = $_ENV['FILE_USER'] ?? 'ftpuser';
            $password = $_ENV['FILE_PASSWORD'] ?? 'ftppassword';
            $carpetaPDFs = '/upload/PruebaMDEP/justificaciones/';

            $sftp = new SFTP($servidor, $puerto);

            if (!$sftp->login($usuario, $password)) {
                throw new Exception('No se pudo conectar al servidor SFTP');
            }

            // Buscar PDFs de esta dependencia
            $archivos = $sftp->nlist($carpetaPDFs);
            $pdfEncontrado = null;

            if ($archivos) {
                foreach ($archivos as $archivo) {
                    if (strpos($archivo, "dep_{$id}_") !== false && pathinfo($archivo, PATHINFO_EXTENSION) === 'pdf') {
                        $pdfEncontrado = $carpetaPDFs . $archivo;
                        break;
                    }
                }
            }

            if (!$pdfEncontrado) {
                http_response_code(404);
                echo json_encode(['codigo' => 0, 'mensaje' => 'No se encontró documento de justificación para esta dependencia']);
                return;
            }

            $contenidoPDF = $sftp->get($pdfEncontrado);
            $sftp->disconnect();

            if ($contenidoPDF === false) {
                throw new Exception('Error al obtener el archivo PDF');
            }

            // Servir PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="justificacion_dep_' . $id . '.pdf"');
            header('Content-Length: ' . strlen($contenidoPDF));
            
            echo $contenidoPDF;

        } catch (Exception $e) {
            error_log('Error en obtenerPDFAPI: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['codigo' => 0, 'mensaje' => 'Error interno del servidor: ' . $e->getMessage()]);
        }
    }

    // MÉTODO MEJORADO PARA GENERAR PDF
    private static function generarPDFSeguro($accion, $dependencia, $justificacion, $depId)
    {
        try {
            // Verificar que mPDF esté disponible
            if (!class_exists('Mpdf\Mpdf')) {
                throw new Exception('mPDF no está disponible');
            }

            $mpdf = new Mpdf([
                'format' => 'A4',
                'tempDir' => sys_get_temp_dir()
            ]);

            $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Justificación de ' . htmlspecialchars($accion) . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .info-box { background-color: #f8f9fa; padding: 15px; margin: 20px 0; border-left: 4px solid #007bff; }
                .justification { background-color: #fff; padding: 20px; border: 1px solid #ddd; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>JUSTIFICACIÓN DE ' . strtoupper(htmlspecialchars($accion)) . '</h1>
            </div>
            
            <div class="info-box">
                <h3>Información de la Dependencia</h3>
                <p><strong>ID:</strong> ' . htmlspecialchars($depId) . '</p>
                <p><strong>Dependencia:</strong> ' . htmlspecialchars($dependencia) . '</p>
                <p><strong>Fecha:</strong> ' . date('d/m/Y H:i:s') . '</p>
                <p><strong>Acción:</strong> ' . htmlspecialchars($accion) . '</p>
            </div>
            
            <div class="justification">
                <h3>Justificación:</h3>
                <p>' . nl2br(htmlspecialchars($justificacion)) . '</p>
            </div>
            
            <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
                <p>Documento generado automáticamente - Sistema MDEP</p>
            </div>
        </body>
        </html>';

            $mpdf->WriteHTML($html);

            // Nombre único del PDF
            $nombrePDF = strtolower($accion) . '_dep_' . $depId . '_' . date('YmdHis') . '.pdf';
            $rutaTemporal = sys_get_temp_dir() . '/' . $nombrePDF;

            // Guardar PDF
            $mpdf->Output($rutaTemporal, 'F');

            // Verificar que el archivo se creó
            if (!file_exists($rutaTemporal)) {
                throw new Exception('No se pudo crear el archivo PDF');
            }

            // Subir a SFTP
            $rutaRemota = self::subirPDFSFTP($rutaTemporal, $nombrePDF);

            // Limpiar archivo temporal
            unlink($rutaTemporal);

            return $rutaRemota;
        } catch (Exception $e) {
            error_log('Error en generarPDFSeguro: ' . $e->getMessage());
            return false;
        }
    }

    // MÉTODO MEJORADO PARA SUBIR PDF
    private static function subirPDFSFTP($rutaTemporal, $nombrePDF)
    {
        try {
            $servidor = $_ENV['FILE_SERVER'] ?? '127.0.0.1';
            $puerto = $_ENV['FILE_PORT'] ?? 22;
            $usuario = $_ENV['FILE_USER'] ?? 'ftpuser';
            $password = $_ENV['FILE_PASSWORD'] ?? 'ftppassword';
            $carpetaRemota = '/upload/PruebaMDEP/justificaciones/';

            $sftp = new SFTP($servidor, $puerto);

            if (!$sftp->login($usuario, $password)) {
                throw new Exception('No se pudo conectar al servidor SFTP');
            }

            // Crear carpeta si no existe
            if (!$sftp->is_dir($carpetaRemota)) {
                $sftp->mkdir($carpetaRemota, 0755, true);
            }

            $rutaCompleta = $carpetaRemota . $nombrePDF;

            // Subir archivo
            $resultado = $sftp->put($rutaCompleta, $rutaTemporal, SFTP::SOURCE_LOCAL_FILE);

            $sftp->disconnect();

            if (!$resultado) {
                throw new Exception('Error al subir archivo al servidor');
            }

            return $rutaCompleta;
        } catch (Exception $e) {
            error_log('Error en subirPDFSFTP: ' . $e->getMessage());
            return false;
        }
    }
}