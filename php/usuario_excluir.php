<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
$id_user = ensureLoggedInUser();

/* ===== PERMISSÃO ===== */
if (!usuarioEhAdmin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error'   => 'Acesso negado. Apenas administradores podem excluir usuários.'
    ]);
    die();
}

include $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";

/* ===== VALIDAÇÃO ===== */
if (!isset($_POST['usuario_id']) || empty($_POST['usuario_id'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error'   => 'ID do usuário não fornecido.'
    ]);
    die();
}

$usuario_id = intval($_POST['usuario_id']);

// Não permitir deletar a si mesmo
if ($usuario_id === $id_user) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error'   => 'Você não pode excluir sua própria conta.'
    ]);
    die();
}

/* ===== BUSCAR USUÁRIO ===== */
$stmtBusca = $cone->prepare(
    "SELECT id, nome, email FROM usuarios WHERE id = ?"
);
$stmtBusca->bind_param("i", $usuario_id);
$stmtBusca->execute();
$result = $stmtBusca->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error'   => 'Usuário não encontrado.'
    ]);
    die();
}

$usuario = $result->fetch_assoc();
$nome_usuario = $usuario['nome'];
$stmtBusca->close();

/* ===== EXCLUIR USUÁRIO ===== */
$stmt = $cone->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);

if ($stmt->execute()) {
    // Log de exclusão
    $detalhe = "Usuário '$nome_usuario' excluído do sistema";
    $stmtLog = $cone->prepare(
        "INSERT INTO logs (usuario_id, acao, detalhes) VALUES (?, 'EXCLUSÃO DE USUÁRIO', ?)"
    );
    $stmtLog->bind_param("is", $id_user, $detalhe);
    $stmtLog->execute();
    $stmtLog->close();

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => "Usuário '$nome_usuario' removido com sucesso."
    ]);
} else {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error'   => 'Erro ao excluir usuário: ' . $stmt->error
    ]);
}

$stmt->close();
$cone->close();
