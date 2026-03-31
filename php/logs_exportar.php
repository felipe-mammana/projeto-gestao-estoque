<?php
/**
 * EXPORTADOR DE LOGS (CSV)
 * Com validacao de schema, filtros e formatacao.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
ensureLoggedInUser();
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/cone.php';

// =========================
// Helpers
// =========================
function outputPlainAndExit(string $message): void
{
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit;
}

function ensureDbOrExit(mysqli $cone): void
{
    if (!isset($cone) || $cone->connect_errno) {
        outputPlainAndExit("Erro de conexao com o banco de dados.");
    }

    try {
        if (!$cone->ping()) {
            outputPlainAndExit("Erro de conexao com o banco de dados.");
        }
    } catch (Throwable $e) {
        outputPlainAndExit("Erro de conexao com o banco de dados.");
    }
}

function getTableColumns(mysqli $cone, string $table): array
{
    try {
        $res = $cone->query("SHOW COLUMNS FROM `$table`");
        $cols = [];
        while ($row = $res->fetch_assoc()) {
            $cols[] = $row['Field'];
        }
        return $cols;
    } catch (Throwable $e) {
        return [];
    }
}

function hasColumn(array $cols, string $name): bool
{
    return in_array($name, $cols, true);
}

function normalizeDate(string $value, string $fallback): string
{
    $dt = DateTime::createFromFormat('Y-m-d', $value);
    if ($dt && $dt->format('Y-m-d') === $value) {
        return $value;
    }
    return $fallback;
}

function outputCsv(array $rows, string $reportName, string $fileName, string $modo): void
{
    if (empty($rows)) {
        outputPlainAndExit("Nenhum registro encontrado para os filtros selecionados.");
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=' . $fileName);
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF";
    $output = fopen('php://output', 'w');
    $delimiter = ';';

    if ($modo !== 'bi') {
        fputcsv($output, ['Sistema', 'Estoque de TI'], $delimiter);
        fputcsv($output, ['Relatorio', $reportName], $delimiter);
        fputcsv($output, ['Data de Geracao', date('d/m/Y H:i')], $delimiter);
        fputcsv($output, [], $delimiter);
    }

    fputcsv($output, array_keys($rows[0]), $delimiter);

    foreach ($rows as $row) {
        $row_tratado = array_map(function ($value) {
            if ($value === null || $value === '') {
                return 'Nao Informado';
            }
            return $value;
        }, $row);
        fputcsv($output, $row_tratado, $delimiter);
    }

    if ($modo !== 'bi') {
        fputcsv($output, [], $delimiter);
        fputcsv($output, ['Total de Registros', count($rows)], $delimiter);
    }

    fclose($output);
    exit;
}

// =========================
// Parametros
// =========================
ensureDbOrExit($cone);

$tipo = $_GET['tipo'] ?? '';
$data_inicio_default = date('Y-m-d', strtotime('-30 days'));
$data_fim_default = date('Y-m-d');
$data_inicio = normalizeDate($_GET['data_inicio'] ?? $data_inicio_default, $data_inicio_default);
$data_fim = normalizeDate($_GET['data_fim'] ?? $data_fim_default, $data_fim_default);
$formato = $_GET['formato'] ?? 'csv'; // csv ou bi
$modo = $_GET['modo'] ?? 'bi';

if ($formato === 'bi') {
    header('Location: relatorio_bi_estoque.php?tipo=movimentacao&data_inicio=' . urlencode($data_inicio) . '&data_fim=' . urlencode($data_fim) . '&modo=bi');
    exit;
}

$cols_logs = getTableColumns($cone, 'logs');
$cols_users = getTableColumns($cone, 'usuarios');

// =========================
// Montagem da query
// =========================
$selects = [];
$joins = [];
$wheres = [];
$params = [];
$types = '';

if (hasColumn($cols_logs, 'id')) {
    $selects[] = "l.id AS `ID_Log`";
}

if (hasColumn($cols_logs, 'usuario_id') && hasColumn($cols_users, 'id')) {
    $joins[] = "LEFT JOIN usuarios u ON u.id = l.usuario_id";
    if (hasColumn($cols_users, 'nome')) {
        $selects[] = "COALESCE(u.nome, 'Nao Informado') AS `Usuario`";
    }
}

if (hasColumn($cols_logs, 'acao')) {
    $selects[] = "l.acao AS `Acao`";
}

if (hasColumn($cols_logs, 'detalhes')) {
    $selects[] = "l.detalhes AS `Descricao`";
}

if (hasColumn($cols_logs, 'modulo')) {
    $selects[] = "l.modulo AS `Modulo`";
}

if (hasColumn($cols_logs, 'ip')) {
    $selects[] = "l.ip AS `Endereco_IP`";
} elseif (hasColumn($cols_logs, 'endereco_ip')) {
    $selects[] = "l.endereco_ip AS `Endereco_IP`";
}

if (hasColumn($cols_logs, 'data_hora')) {
    $selects[] = "DATE_FORMAT(l.data_hora, '%d/%m/%Y %H:%i') AS `Data_Hora`";
    $wheres[] = "l.data_hora BETWEEN CONCAT(?, ' 00:00:00') AND CONCAT(?, ' 23:59:59')";
    $params[] = $data_inicio;
    $params[] = $data_fim;
    $types .= "ss";
}

if ($tipo === 'ENTRADA' && hasColumn($cols_logs, 'detalhes')) {
    $wheres[] = "l.detalhes LIKE '%Entrada%'";
}
if ($tipo === 'SAIDA' && hasColumn($cols_logs, 'detalhes')) {
    $wheres[] = "(l.detalhes LIKE '%Saida%' OR l.detalhes LIKE '%Saída%')";
}

$sql = "SELECT " . implode(",\n       ", $selects) . "\nFROM logs l\n";
if (!empty($joins)) {
    $sql .= implode("\n", $joins) . "\n";
}
if (!empty($wheres)) {
    $sql .= "WHERE " . implode(" AND ", $wheres) . "\n";
}
$sql .= "ORDER BY l.data_hora DESC";

try {
    $stmt = $cone->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    outputCsv(
        $rows,
        'Relatorio de Logs do Sistema',
        'relatorio_logs_' . $data_inicio . '_ate_' . $data_fim . '.csv',
        $modo
    );
} catch (Throwable $e) {
    outputPlainAndExit("Erro ao gerar relatorio de logs.");
}
