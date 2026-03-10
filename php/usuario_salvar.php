<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
$id_user = ensureLoggedInUser();

require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/cone.php';

/* ===== DADOS ===== */
$nome     = trim($_POST['nome'] ?? '');
$email    = trim($_POST['email'] ?? '');
$cargo    = trim($_POST['cargo'] ?? '');
$setor_id = 1;
$ativo    = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;
$id       = (int)($_POST['id'] ?? 0);
$senha    = trim($_POST['senha'] ?? '');

/* ===== TIPO ===== */
$tipo = 'user';
if (($_SESSION['usuario']['tipo'] ?? '') === 'admin' && isset($_POST['tipo'])) {
    $tipo = $_POST['tipo'] === 'admin' ? 'admin' : 'user';
}

/* ===== FOTO ===== */
$foto = null;
if (!empty($_FILES['foto']['tmp_name'])) {
    $foto = file_get_contents($_FILES['foto']['tmp_name']);
}

/* =========================
   UPDATE
========================= */
if ($id > 0) {

    // Verificar email duplicado
    $chk = $cone->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $chk->bind_param("si", $email, $id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        die('Erro: email já cadastrado.');
    }
    $chk->close();

    $fields = [];
    $types  = '';
    $values = [];
    $fotoIndex = null;

    $fields[] = 'nome = ?';
    $types .= 's';
    $values[] = $nome;

    $fields[] = 'email = ?';
    $types .= 's';
    $values[] = $email;

    $fields[] = 'cargo = ?';
    $types .= 's';
    $values[] = $cargo;

    $fields[] = 'setor_id = ?';
    $types .= 'i';
    $values[] = $setor_id;

    $fields[] = 'ativo = ?';
    $types .= 'i';
    $values[] = $ativo;

    if (!empty($senha)) {
        $fields[] = 'senha = ?';
        $types .= 's';
        $values[] = password_hash($senha, PASSWORD_DEFAULT);
    }

    if (($_SESSION['usuario']['tipo'] ?? '') === 'admin') {
        $fields[] = 'tipo = ?';
        $types .= 's';
        $values[] = $tipo;
    }

    if ($foto !== null) {
        $fields[] = 'foto = ?';
        $types .= 'b';
        $fotoIndex = count($values);
        $values[] = null;
    }

    $types .= 'i';
    $values[] = $id;

    $sql = "UPDATE usuarios SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $cone->prepare($sql);
    $stmt->bind_param($types, ...$values);

    if ($foto !== null && $fotoIndex !== null) {
        $stmt->send_long_data($fotoIndex, $foto);
    }

    $stmt->execute();
    $stmt->close();

    // LOG
    $detalhe = "Usuário '$nome' atualizado";
    $log = $cone->prepare(
        "INSERT INTO logs (usuario_id, acao, detalhes) VALUES (?, 'EDIÇÃO DE USUÁRIO', ?)"
    );
    $log->bind_param("is", $id_user, $detalhe);
    $log->execute();
    $log->close();

    header("Location: /estoquemh/pages/usuario.php");
    exit;
}

/* =========================
   INSERT
========================= */

$chk = $cone->prepare("SELECT id FROM usuarios WHERE email = ?");
$chk->bind_param("s", $email);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    die('Erro: email já cadastrado.');
}
$chk->close();

$sql = "INSERT INTO usuarios 
        (nome, email, cargo, senha, tipo, foto, setor_id, ativo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $cone->prepare($sql);

$hashSenha = password_hash($senha, PASSWORD_DEFAULT);
$null = null;

$stmt->bind_param(
    "sssssbii",
    $nome,
    $email,
    $cargo,
    $hashSenha,
    $tipo,
    $null,
    $setor_id,
    $ativo
);

if ($foto !== null) {
    $stmt->send_long_data(5, $foto);
}

$stmt->execute();
$stmt->close();

// LOG
$detalhe = "Usuário '$nome' criado com perfil '$tipo'";
$log = $cone->prepare(
    "INSERT INTO logs (usuario_id, acao, detalhes) VALUES (?, 'CRIAÇÃO DE USUÁRIO', ?)"
);
$log->bind_param("is", $id_user, $detalhe);
$log->execute();
$log->close();

header("Location: /estoquemh/pages/usuario.php");
exit;
?>
