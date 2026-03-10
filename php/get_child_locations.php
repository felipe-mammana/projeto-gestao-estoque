<?php
/**
 * get_child_locations.php
 * Retorna locais filhos em JSON para Cascading Select
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

$parent_id = $_POST['parent_id'] ?? null;

if (!$parent_id || !is_numeric($parent_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'parent_id inválido']);
    exit;
}

$locations = getChildLocations($cone, (int)$parent_id);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => $locations
]);

mysqli_close($cone);
exit;
?>
