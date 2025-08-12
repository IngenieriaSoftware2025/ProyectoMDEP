<?php 
require_once __DIR__ . '/../includes/app.php';

use MVC\Router;
use Controllers\AppController;
use Controllers\MdepController;

$router = new Router();
$router->setBaseURL('/' . $_ENV['APP_NAME']);

//rutas MDEP
$router->get('/mdep', [MdepController::class,'renderizarPagina']);
$router->get('/mdep/ubicaciones', [MdepController::class,'renderizarUbicaciones']);
$router->post('/mdep/guardarAPI', [MdepController::class,'guardarAPI']);
$router->get('/mdep/buscarAPI', [MdepController::class,'buscarAPI']);
$router->post('/mdep/modificarAPI', [MdepController::class,'modificarAPI']);
$router->post('/mdep/deshabilitarAPI', [MdepController::class,'deshabilitarAPI']);
$router->post('/mdep/habilitarAPI', [MdepController::class,'habilitarAPI']);
$router->get('/mdep/obtenerPDFAPI', [MdepController::class,'obtenerPDFAPI']); 
$router->get('/mdep/servirImagenAPI', [MdepController::class,'servirImagenAPI']); 

// Comprueba y valida las rutas, que existan y les asigna las funciones del Controlador
$router->comprobarRutas();