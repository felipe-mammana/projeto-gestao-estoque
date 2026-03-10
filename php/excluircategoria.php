<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
include $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";

/* ===== LOGIN ===== */
$id_user = ensureLoggedInUser();

/* ===== PERMISSÃO ===== */
if (!usuarioEhAdmin()) {
    http_response_code(403);
    echo "Acesso negado. Apenas administradores.";
    exit;
}

/* ===== ID ===== */
$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo "ID inválido.";
    exit;
}

/* ===== VERIFICA SE CATEGORIA EXISTE ===== */
$sqlCat = "SELECT nome, parent_id FROM categorias WHERE id = ?";
$stmtCat = $cone->prepare($sqlCat);
$stmtCat->bind_param("i", $id);
$stmtCat->execute();
$stmtCat->bind_result($nome_categoria, $parent_id);
$stmtCat->fetch();
$stmtCat->close();

if (!$nome_categoria) {
    http_response_code(404);
    echo "Categoria não encontrada.";
    exit;
}

/* ===== SE FOR CATEGORIA PAI, VERIFICA SUBCATEGORIAS ===== */
if ($parent_id === null) {

    $checkSub = $cone->prepare(
        "SELECT COUNT(*) FROM categorias WHERE parent_id = ?"
    );
    $checkSub->bind_param("i", $id);
    $checkSub->execute();
    $checkSub->bind_result($totalSub);
    $checkSub->fetch();
    $checkSub->close();

    if ($totalSub > 0) {
        echo "Erro: categoria possui subcategorias.";
        exit;
    }
}

/* ===== VERIFICA ITENS ===== */
$checkItens = $cone->prepare(
    "SELECT COUNT(*) FROM itens WHERE categoria_id = ?"
);
$checkItens->bind_param("i", $id);
$checkItens->execute();
$checkItens->bind_result($totalItens);
$checkItens->fetch();
$checkItens->close();

if ($totalItens > 0) {
    echo "Erro: categoria possui itens vinculados.";
    exit;
}

/* ===== EXCLUIR ===== */
$stmt = $cone->prepare("DELETE FROM categorias WHERE id = ?");
$stmt->bind_param("i", $id);

if (!$stmt->execute()) {
    http_response_code(500);
    echo "Erro ao excluir categoria.";
    exit;
}

/* ===== LOG ===== */
$detalhe = "CATEGORIA '$nome_categoria' EXCLUÍDA DO SISTEMA";

$sql_logs = "
    INSERT INTO logs (usuario_id, acao, detalhes)
    VALUES (?, 'CATEGORIA EXCLUÍDA', ?)
";

$stmtLog = $cone->prepare($sql_logs);
$stmtLog->bind_param("is", $id_user, $detalhe);
$stmtLog->execute();

/* ===== OK ===== */
echo "ok";
exit;
