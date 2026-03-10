<?php
/**
 * get_location_info.php
 * Retorna informações sobre um local específico
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/locations_crud.php';

$id_user = ensureLoggedInUser();
include $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

$location_id = $_POST['location_id'] ?? null;

if (!$location_id || !is_numeric($location_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'location_id inválido']);
    exit;
}

$location = getLocationById($cone, (int)$location_id);

if (!$location) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Local não encontrado']);
    exit;
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => $location
]);

mysqli_close($cone);
exit;
?>
