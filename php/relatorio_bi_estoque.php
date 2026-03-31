<?php
/**
 * RELATORIOS DE ESTOQUE E MOVIMENTACOES
 * CSV com validacao de schema e dados reais do banco.
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

function outputCsv(array $rows, string $reportName, string $fileName, string $modo, string $totalLabel): void
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

    // Header
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
        fputcsv($output, [$totalLabel, count($rows)], $delimiter);
    }

    fclose($output);
    exit;
}

// =========================
// Parametros
// =========================
ensureDbOrExit($cone);

$tipo_relatorio = $_GET['tipo'] ?? 'estoque_atual';
$modo = $_GET['modo'] ?? 'bi'; // bi (raw) ou relatorio (com cabecalho/rodape)

$data_inicio_default = date('Y-m-d', strtotime('-30 days'));
$data_fim_default = date('Y-m-d');
$data_inicio = normalizeDate($_GET['data_inicio'] ?? $data_inicio_default, $data_inicio_default);
$data_fim = normalizeDate($_GET['data_fim'] ?? $data_fim_default, $data_fim_default);

// =========================
// Schema
// =========================
$cols_itens = getTableColumns($cone, 'itens');
$cols_categorias = getTableColumns($cone, 'categorias');
$cols_locations = getTableColumns($cone, 'locations');
$cols_mov = getTableColumns($cone, 'movimentacoes');
$cols_mov_dest = getTableColumns($cone, 'movimentacoes_destinos');
$cols_users = getTableColumns($cone, 'usuarios');
$cols_setores = getTableColumns($cone, 'setores');

// =========================
// Relatorio 1: Estoque Atual
// =========================
if ($tipo_relatorio === 'estoque_atual') {
    $selects = [];
    $joins = [];
    $wheres = [];
    $hasCategoria = false;
    $hasNome = false;
    $hasLocalizacao = false;

    $selects[] = "i.id AS `ID_Item`";

    if (hasColumn($cols_itens, 'nome')) {
        $selects[] = "i.nome AS `Nome_Equipamento`";
        $hasNome = true;
    }

    // Categoria (se existir)
    if (hasColumn($cols_itens, 'categoria_id') && hasColumn($cols_categorias, 'id')) {
        $joins[] = "LEFT JOIN categorias c ON c.id = i.categoria_id";
        $hasCatParent = hasColumn($cols_categorias, 'parent_id');
        if ($hasCatParent) {
            $joins[] = "LEFT JOIN categorias cp ON cp.id = c.parent_id";
        }
        if ($hasCatParent) {
            $selects[] = "COALESCE(CONCAT_WS(' > ', cp.nome, c.nome), c.nome, cp.nome, 'Nao Informado') AS `Categoria`";
        } else {
            $selects[] = "COALESCE(c.nome, 'Nao Informado') AS `Categoria`";
        }
        $hasCategoria = true;
    }

    // Campos opcionais
    if (hasColumn($cols_itens, 'marca')) {
        $selects[] = "i.marca AS `Marca`";
    }
    if (hasColumn($cols_itens, 'modelo')) {
        $selects[] = "i.modelo AS `Modelo`";
    }
    if (hasColumn($cols_itens, 'numero_serie')) {
        $selects[] = "i.numero_serie AS `Numero_Serie`";
    }
    if (hasColumn($cols_itens, 'patrimonio')) {
        $selects[] = "i.patrimonio AS `Patrimonio`";
    }

    if (hasColumn($cols_itens, 'quantidade')) {
        $selects[] = "i.quantidade AS `Quantidade_Atual`";
    }

    // Localizacao
    if (hasColumn($cols_itens, 'localizacao_id') && hasColumn($cols_locations, 'id')) {
        $joins[] = "LEFT JOIN locations lf ON lf.id = i.localizacao_id";
        $hasLocParent = hasColumn($cols_locations, 'parent_id');
        if ($hasLocParent) {
            $joins[] = "LEFT JOIN locations lp ON lp.id = lf.parent_id";
        }
        if ($hasLocParent) {
            $selects[] = "COALESCE(CONCAT_WS(' > ', lp.nome, lf.nome), lf.nome, lp.nome, 'Nao Informado') AS `Localizacao`";
        } else {
            $selects[] = "COALESCE(lf.nome, 'Nao Informado') AS `Localizacao`";
        }
        $hasLocalizacao = true;
    } elseif (hasColumn($cols_itens, 'localizacao')) {
        $selects[] = "COALESCE(NULLIF(TRIM(i.localizacao), ''), 'Nao Informado') AS `Localizacao`";
        $hasLocalizacao = true;
    }

    // Status do estoque
    if (hasColumn($cols_itens, 'status')) {
        $selects[] = "i.status AS `Status`";
    } elseif (hasColumn($cols_itens, 'quantidade')) {
        $minField = hasColumn($cols_itens, 'quantidade_minima') ? "i.quantidade_minima" : "1";
        $selects[] = "CASE WHEN i.quantidade = 0 THEN 'Zerado' WHEN i.quantidade <= $minField THEN 'Baixo' ELSE 'Normal' END AS `Status`";
    }

    if (hasColumn($cols_itens, 'criado_em')) {
        $selects[] = "DATE_FORMAT(i.criado_em, '%d/%m/%Y %H:%i') AS `Data_Cadastro`";
    }

    if (hasColumn($cols_itens, 'ativo')) {
        $wheres[] = "i.ativo = 1";
    }

    $sql = "SELECT " . implode(",\n       ", $selects) . "\nFROM itens i\n";
    if (!empty($joins)) {
        $sql .= implode("\n", $joins) . "\n";
    }
    if (!empty($wheres)) {
        $sql .= "WHERE " . implode(" AND ", $wheres) . "\n";
    }
    $orderParts = [];
    if ($hasCategoria) {
        $orderParts[] = "`Categoria` ASC";
    }
    if ($hasNome) {
        $orderParts[] = "`Nome_Equipamento` ASC";
    }
    if ($hasLocalizacao) {
        $orderParts[] = "`Localizacao` ASC";
    }
    if (empty($orderParts)) {
        $orderParts[] = "i.id ASC";
    }
    $sql .= "ORDER BY " . implode(", ", $orderParts);

    try {
        $result = $cone->query($sql);
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        outputCsv(
            $rows,
            'Relatorio de Estoque Atual',
            'relatorio_estoque_atual_' . date('Y-m-d_H-i-s') . '.csv',
            $modo,
            'Total de Itens'
        );
    } catch (Throwable $e) {
        outputPlainAndExit("Erro ao gerar relatorio de estoque.");
    }
}

// =========================
// Relatorio 2: Movimentacoes
// =========================
if ($tipo_relatorio === 'movimentacao') {
    $selects = [];
    $joins = [];
    $wheres = [];
    $params = [];
    $types = '';
    $hasMovDest = false;

    $selects[] = "m.id AS `ID_Movimentacao`";

    if (hasColumn($cols_mov, 'tipo')) {
        $selects[] = "m.tipo AS `Tipo`";
    }

    $hasItemJoin = false;
    if (hasColumn($cols_mov, 'item_id') && hasColumn($cols_itens, 'id')) {
        $joins[] = "LEFT JOIN itens i ON i.id = m.item_id";
        $hasItemJoin = true;
        if (hasColumn($cols_itens, 'nome')) {
            $selects[] = "i.nome AS `Item`";
        }
    }

    if (hasColumn($cols_mov_dest, 'movimentacao_id')) {
        $joins[] = "LEFT JOIN movimentacoes_destinos md ON md.movimentacao_id = m.id";
        $hasMovDest = true;
    }

    if (hasColumn($cols_mov, 'quantidade')) {
        if ($hasMovDest && hasColumn($cols_mov_dest, 'quantidade')) {
            $selects[] = "COALESCE(md.quantidade, m.quantidade) AS `Quantidade`";
        } else {
            $selects[] = "m.quantidade AS `Quantidade`";
        }
    }

    if (hasColumn($cols_mov, 'usuario_id') && hasColumn($cols_users, 'id')) {
        $joins[] = "LEFT JOIN usuarios u ON u.id = m.usuario_id";
        if (hasColumn($cols_users, 'nome')) {
            $selects[] = "COALESCE(u.nome, 'Nao Informado') AS `Usuario`";
        }
    }

    // Origem baseada na localizacao atual do item (quando existir)
    if ($hasItemJoin && hasColumn($cols_itens, 'localizacao_id') && hasColumn($cols_locations, 'id')) {
        $joins[] = "LEFT JOIN locations lf ON lf.id = i.localizacao_id";
        $hasLocParentMov = hasColumn($cols_locations, 'parent_id');
        if ($hasLocParentMov) {
            $joins[] = "LEFT JOIN locations lp ON lp.id = lf.parent_id";
        }
        if ($hasLocParentMov) {
            $selects[] = "COALESCE(CONCAT_WS(' > ', lp.nome, lf.nome), lf.nome, lp.nome, 'Nao Informado') AS `Origem`";
        } else {
            $selects[] = "COALESCE(lf.nome, 'Nao Informado') AS `Origem`";
        }
    } elseif ($hasItemJoin && hasColumn($cols_itens, 'localizacao')) {
        $selects[] = "COALESCE(NULLIF(TRIM(i.localizacao), ''), 'Nao Informado') AS `Origem`";
    }

    // Destino por setor
    if ($hasMovDest && hasColumn($cols_setores, 'id')) {
        $joins[] = "LEFT JOIN setores s ON s.id = md.setor_id";
        if (hasColumn($cols_setores, 'nome')) {
            $selects[] = "COALESCE(s.nome, 'Nao Informado') AS `Destino`";
        }
    }

    if (hasColumn($cols_mov, 'observacao')) {
        $selects[] = "COALESCE(m.observacao, 'Nao Informado') AS `Motivo`";
    }

    if (hasColumn($cols_mov, 'data_movimentacao')) {
        $selects[] = "DATE_FORMAT(m.data_movimentacao, '%d/%m/%Y %H:%i') AS `Data_Hora`";
        $wheres[] = "m.data_movimentacao BETWEEN CONCAT(?, ' 00:00:00') AND CONCAT(?, ' 23:59:59')";
        $params[] = $data_inicio;
        $params[] = $data_fim;
        $types .= "ss";
    }

    // Filtros opcionais
    $tipo_mov = trim($_GET['tipo_mov'] ?? '');
    $item_filtro = trim($_GET['item'] ?? '');
    $usuario_filtro = trim($_GET['usuario'] ?? '');

    if ($tipo_mov !== '' && hasColumn($cols_mov, 'tipo')) {
        $wheres[] = "m.tipo = ?";
        $params[] = $tipo_mov;
        $types .= "s";
    }

    if ($item_filtro !== '' && $hasItemJoin && hasColumn($cols_itens, 'id')) {
        if (ctype_digit($item_filtro)) {
            $wheres[] = "i.id = ?";
            $params[] = (int)$item_filtro;
            $types .= "i";
        } else {
            $wheres[] = "i.nome LIKE ?";
            $params[] = '%' . $item_filtro . '%';
            $types .= "s";
        }
    }

    if ($usuario_filtro !== '' && hasColumn($cols_mov, 'usuario_id') && hasColumn($cols_users, 'id')) {
        if (ctype_digit($usuario_filtro)) {
            $wheres[] = "u.id = ?";
            $params[] = (int)$usuario_filtro;
            $types .= "i";
        } else {
            $wheres[] = "u.nome LIKE ?";
            $params[] = '%' . $usuario_filtro . '%';
            $types .= "s";
        }
    }

    $sql = "SELECT " . implode(",\n       ", $selects) . "\nFROM movimentacoes m\n";
    if (!empty($joins)) {
        $sql .= implode("\n", $joins) . "\n";
    }
    if (!empty($wheres)) {
        $sql .= "WHERE " . implode(" AND ", $wheres) . "\n";
    }
    if (hasColumn($cols_mov, 'data_movimentacao')) {
        $sql .= "ORDER BY m.data_movimentacao DESC";
    } else {
        $sql .= "ORDER BY m.id DESC";
    }

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
            'Relatorio de Movimentacoes',
            'relatorio_movimentacoes_' . date('Y-m-d_H-i-s') . '.csv',
            $modo,
            'Total de Registros'
        );
    } catch (Throwable $e) {
        outputPlainAndExit("Erro ao gerar relatorio de movimentacoes.");
    }
}

outputPlainAndExit("Tipo de relatorio invalido.");
