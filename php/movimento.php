<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
$id_user = ensureLoggedInUser();
include $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";

/* ================= DADOS DO FORM ================= */
$item_id   = (int) ($_POST['item_id'] ?? 0);
$tipo_form = $_POST['movimento-tipo'] ?? '';
$obs       = trim($_POST['movimento-observacao'] ?? '');

$setores_ids = $_POST['setor_id'] ?? [];
$quantidades = $_POST['quantidade'] ?? [];

$qtde = array_sum(array_map('intval', $quantidades));

if ($item_id <= 0 || $qtde <= 0) {
    die("Dados inválidos");
}

/* ================= BUSCA ITEM ================= */
$sql_item = "
    SELECT 
        i.nome AS item_nome,
        i.quantidade,
        c.nome AS categoria_nome
    FROM itens i
    INNER JOIN categorias c ON c.id = i.categoria_id
    WHERE i.id = ?
";

$stmt = $cone->prepare($sql_item);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    die("Item não encontrado");
}

$nome_item  = $item['item_nome'];
$categoria  = $item['categoria_nome'];
$qtde_atual = (int) $item['quantidade'];

/* ================= USUÁRIO ================= */
$nome_usuario = obterNomeUsuario($id_user);

/* ================= PROCESSA MOVIMENTO ================= */
date_default_timezone_set('America/Sao_Paulo');
$data = date('d/m/Y H:i');

if ($tipo_form === 'entrada') {

    $nova_qtde = $qtde_atual + $qtde;
    $tipo_bd = 'Entrada';

    $emailDados = [
        'item'       => $nome_item,
        'categoria'  => $categoria,
        'quantidade' => $qtde,
        'usuario'    => $nome_usuario,
        'observacao' => $obs ?: '—',
        'data'       => $data
    ];

    require 'envio_entrada_email.php';

} elseif ($tipo_form === 'saida') {

    if ($qtde > $qtde_atual) {
        die("Estoque insuficiente");
    }

    $nova_qtde = $qtde_atual - $qtde;
    $tipo_bd = 'Saída';

    $emailDados = [
        'item'       => $nome_item,
        'categoria'  => $categoria,
        'quantidade' => $qtde,
        'usuario'    => $nome_usuario,
        'observacao' => $obs ?: '—',
        'data'       => $data
    ];

    require 'envio_saida_email.php';

} else {
    die("Tipo inválido");
}

/* ================= REGISTRA MOVIMENTAÇÃO ================= */
$mov = $cone->prepare("
    INSERT INTO movimentacoes (item_id, usuario_id, tipo, quantidade, observacao)
    VALUES (?, ?, ?, ?, ?)
");
$mov->bind_param("iisis", $item_id, $id_user, $tipo_bd, $qtde, $obs);
$mov->execute();

$movimentacao_id = $mov->insert_id;

/* ================= REGISTRA DESTINOS (BI) ================= */
if ($tipo_form === 'saida') {

    $dest = $cone->prepare("
        INSERT INTO movimentacoes_destinos 
        (movimentacao_id, setor_id, quantidade)
        VALUES (?, ?, ?)
    ");

    foreach ($setores_ids as $i => $setor_id) {
        $q = (int) ($quantidades[$i] ?? 0);

        if ($setor_id && $q > 0) {
            $dest->bind_param("iii", $movimentacao_id, $setor_id, $q);
            $dest->execute();
        }
    }
}

/* ================= ATUALIZA ESTOQUE ================= */
$upd = $cone->prepare("UPDATE itens SET quantidade = ? WHERE id = ?");
$upd->bind_param("ii", $nova_qtde, $item_id);
$upd->execute();

/* ================= LOG ================= */
$detalhe = "Item: $nome_item | $tipo_bd | Qtd: $qtde";
$log = $cone->prepare("
    INSERT INTO logs (usuario_id, acao, detalhes)
    VALUES (?, 'MOVIMENTAÇÃO', ?)
");
$log->bind_param("is", $id_user, $detalhe);
$log->execute();

/* ================= REDIRECT ================= */
header("Location: /estoquemh/pages/sistema.php?sucesso=1");
exit;