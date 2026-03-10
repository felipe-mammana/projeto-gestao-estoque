<?php
/**
 * EXPORTADOR DE LOGS COM RELATÓRIOS BI
 * Versão: 2.0 - Engenharia de Dados
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
ensureLoggedInUser();
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/cone.php';
$tipo = $_GET['tipo'] ?? '';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$formato = $_GET['formato'] ?? 'csv'; // csv ou bi

if (empty($data_inicio) || empty($data_fim)) {
    die('Período inválido');
}

// ========================================
// MODO RELATÓRIO BI (Denormalizado)
// ========================================
if ($formato === 'bi') {
    // Redirecionar para o novo sistema de BI
    header('Location: relatorio_bi_estoque.php?tipo=movimentacao&data_inicio=' . urlencode($data_inicio) . '&data_fim=' . urlencode($data_fim));
    exit;
}

// ========================================
// MODO EXPORTAÇÃO SIMPLES (Legado)
// ========================================

$sql = "SELECT usuario_id, acao, detalhes, data_hora
        FROM logs
        WHERE data_hora BETWEEN CONCAT(?, ' 00:00:00')
                            AND CONCAT(?, ' 23:59:59')";

$params = [$data_inicio, $data_fim];
$types = "ss";

if ($tipo === 'ENTRADA') {
    $sql .= " AND detalhes LIKE '%Entrada%'";
}

if ($tipo === 'SAIDA') {
    $sql .= " AND detalhes LIKE '%Saída%'";
}

$sql .= " ORDER BY data_hora DESC";

$stmt = $cone->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

/* CSV UTF-8 correto pro Excel */
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename=logs_' . $data_inicio . '_ate_' . $data_fim . '.csv');
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

fputcsv($output, ['usuario_id', 'acao', 'detalhes', 'data_hora']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
exit;
