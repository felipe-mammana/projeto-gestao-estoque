<?php
// helpers.php - shared helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Detecta se a requisição é AJAX / fetch
 */
function isAjaxRequest(): bool
{
    if (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) {
        return true;
    }

    if (
        !empty($_SERVER['HTTP_ACCEPT']) &&
        strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
    ) {
        return true;
    }

    return false;
}

/**
 * Garante login do USUÁRIO
 * Retorna o ID do usuário se logado
 */
function ensureLoggedInUser(): int
{
    if (!isset($_SESSION['id_user']) || empty($_SESSION['id_user'])) {

        if (isAjaxRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error'   => 'not_logged_in_user'
            ]);
            exit;
        }

        header('Location: /estoquemh/pages/login.php');
        exit;
    }

    return (int) $_SESSION['id_user'];
}

function checkSetor($setorPermitido) {
    if ($_SESSION['setor_id'] != $setorPermitido) {
        header("Location: sem_permissao.php");
        exit;
    }
}

function usuarioEhAdmin() {
    return isset($_SESSION['usuario']['tipo']) 
        && $_SESSION['usuario']['tipo'] === 'admin';
}

/**
 * Obtém o nome do usuário pelo ID
 */
function obterNomeUsuario($userId) {
    global $cone;
    $stmt = $cone->prepare("SELECT nome FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['nome'] ?? 'Desconhecido';
}

/**
 * Obtém lista de destinatários de email ativos
 * Retorna array com emails dos usuários do mesmo setor
 */
function obterDestinatariosEmail() {
    global $cone;
    $setor_id = $_SESSION['usuario']['setor_id'] ?? 0;
    
    $stmt = $cone->prepare("
        SELECT DISTINCT email 
        FROM usuarios 
        WHERE setor_id = ? AND email IS NOT NULL AND email != ''
        ORDER BY nome
    ");
    $stmt->bind_param("i", $setor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $emails = [];
    while ($row = $result->fetch_assoc()) {
        $emails[] = $row['email'];
    }
    $stmt->close();
    
    return $emails;
}