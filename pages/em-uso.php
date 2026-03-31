<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/locations_crud.php';
$id_user = ensureLoggedInUser();
require_once $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";

$isAdmin = ($_SESSION['usuario']['tipo'] ?? '') === 'admin';

// Buscar categorias (pai/filhos) para filtros e emojis
$sql_categorias = "
SELECT 
    c1.id AS pai_id,
    c1.nome AS pai_nome,
    c2.id AS filho_id,
    c2.nome AS filho_nome,
    c2.quantidade_minima AS filho_qtd_min
FROM categorias c1
LEFT JOIN categorias c2 ON c2.parent_id = c1.id
WHERE c1.parent_id IS NULL
ORDER BY c1.nome, c2.nome";

$result = mysqli_query($cone, $sql_categorias);

$categorias = [];
while ($row = mysqli_fetch_assoc($result)) {
    $paiId = $row['pai_id'];

    if (!isset($categorias[$paiId])) {
        $categorias[$paiId] = [
            'id' => $paiId,
            'nome' => $row['pai_nome'],
            'subcategorias' => []
        ];
    }

    if ($row['filho_id']) {
        $categorias[$paiId]['subcategorias'][] = [
            'id' => $row['filho_id'],
            'nome' => $row['filho_nome'],
            'quantidade_minima' => $row['filho_qtd_min'] ?? 0
        ];
    }
}

function getEmojiForCategory($catId) {
    switch($catId) {
        case 1: return '🖱️';
        case 2: return '⌨️';
        case 9: return '🔧';
        case 10: return '📡';
        case 11: return '🏗️';
        case 12: return '💿';
        case 13: return '🎁';
        case 14: return '🖱️';
        case 15: return '🖥️';
        case 16: return '🖨️';
        case 17: return '📠';
        case 18: return '📷';
        case 19: return '🎧';
        case 20: return '💻';
        case 21: return '💻';
        case 22: return '🖥️';
        case 23: return '🔌';
        case 24: return '🧠';
        case 25: return '⚙️';
        case 26: return '💾';
        case 27: return '🔋';
        case 28: return '📡';
        case 29: return '🔄';
        case 30: return '🔌';
        case 31: return '📶';
        case 32: return '🔌';
        case 33: return '🌐';
        case 34: return '🗂️';
        case 35: return '🔋';
        case 36: return '⚡';
        case 37: return '🔌';
        case 38: return '❄️';
        case 39: return '🖥️';
        case 40: return '📄';
        case 41: return '🛡️';
        case 42: return '🔧';
        case 43: return '📦';
        case 44: return '🔌';
        case 45: return '🔌';
        case 46: return '🖱️';
        case 47: return '🎁';
        default: return '📦';
    }
}

$categoryEmojis = [];
foreach ($categorias as $cat) {
    $categoryEmojis[$cat['id']] = getEmojiForCategory($cat['id']);
    if (!empty($cat['subcategorias'])) {
        foreach ($cat['subcategorias'] as $sub) {
            $categoryEmojis[$sub['id']] = getEmojiForCategory($sub['id']);
        }
    }
}

// ================= SEÇÃO EM USO - ITENS SAÍDOS =================
$where_em_uso = [];
$where_em_uso_base = [];

if (!empty($_GET['busca_em_uso'])) {
    $busca = mysqli_real_escape_string($cone, $_GET['busca_em_uso']);
    $where_em_uso[] = "ieu.nome LIKE '%$busca%'";
    $where_em_uso_base[] = "ieu.nome LIKE '%$busca%'";
}

if (!empty($_GET['setor_em_uso'])) {
    $setor = (int)$_GET['setor_em_uso'];
    $where_em_uso[] = "ieu.setor_id = $setor";
    $where_em_uso_base[] = "ieu.setor_id = $setor";
}

if (!empty($_GET['categoria_em_uso'])) {
    $categoria = (int)$_GET['categoria_em_uso'];
    $where_em_uso[] = "ieu.categoria_id = $categoria";
    $where_em_uso_base[] = "ieu.categoria_id = $categoria";
}

$porPaginaEmUso = (int)($_GET['limite_em_uso'] ?? 10);
$paginaEmUso = max((int)($_GET['pagina_em_uso'] ?? 1), 1);
$offsetEmUso = ($paginaEmUso - 1) * $porPaginaEmUso;

if (isset($_GET['status_em_uso']) && $_GET['status_em_uso'] !== '') {
    $status = (int)$_GET['status_em_uso'];
    $where_em_uso[] = "ieu.ativo = $status";
}

$sql_total_em_uso = "SELECT COUNT(*) AS total FROM itens_em_uso ieu WHERE 1=1";

if (!empty($where_em_uso)) {
    $sql_total_em_uso .= " AND " . implode(" AND ", $where_em_uso);
}

$res_total_em_uso = mysqli_query($cone, $sql_total_em_uso);
$totalRegistrosEmUso = mysqli_fetch_assoc($res_total_em_uso)['total'] ?? 0;
$totalPaginasEmUso = max(ceil($totalRegistrosEmUso / $porPaginaEmUso), 1);

$where_base_sql = '';
if (!empty($where_em_uso_base)) {
    $where_base_sql = " AND " . implode(" AND ", $where_em_uso_base);
}

$sql_total_ativos = "SELECT COUNT(*) AS total FROM itens_em_uso ieu WHERE 1=1{$where_base_sql} AND ieu.ativo = 1";
$sql_total_inativos = "SELECT COUNT(*) AS total FROM itens_em_uso ieu WHERE 1=1{$where_base_sql} AND ieu.ativo = 0";
$totalAtivosEmUso = mysqli_fetch_assoc(mysqli_query($cone, $sql_total_ativos))['total'] ?? 0;
$totalInativosEmUso = mysqli_fetch_assoc(mysqli_query($cone, $sql_total_inativos))['total'] ?? 0;

$sql_em_uso = "
    SELECT 
        ieu.id,
        ieu.patrimonio,
        ieu.nome,
        ieu.categoria_id,
        ieu.foto_loc,
        ieu.ativo,
        c.nome AS categoria_nome,
        s.nome AS setor_nome,
        s.id AS setor_id,
        ieu.quantidade,
        ieu.data_saida,
        ieu.observacao
    FROM itens_em_uso ieu
    LEFT JOIN categorias c ON ieu.categoria_id = c.id
    LEFT JOIN setores s ON ieu.setor_id = s.id
    WHERE 1=1
";

if (!empty($where_em_uso)) {
    $sql_em_uso .= " AND " . implode(" AND ", $where_em_uso);
}

$sql_em_uso .= " ORDER BY ieu.data_saida DESC LIMIT $porPaginaEmUso OFFSET $offsetEmUso";

$resultado_em_uso = mysqli_query($cone, $sql_em_uso);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no, maximum-scale=5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#0f172a">
    <title>Em Uso - Estoque</title>
    <link rel="stylesheet" href="/estoquemh/css/style.css">
</head>
<body>

<div class="ambient-bg">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
</div>

<div class="layout">
    
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-inner">
            <div class="sidebar-header">
                <img src="/estoquemh/php/foto_usuario.php?id=<?= $_SESSION['usuario']['id'] ?>" class="perfil-foto" alt="Foto">
                <div class="perfil-info">
                    <strong class="perfil-nome"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></strong>
                    <span class="perfil-setor"><?= htmlspecialchars($_SESSION['usuario']['setor_nome']) ?></span>
                    <small class="perfil-cargo"><?= htmlspecialchars($_SESSION['usuario']['cargo']) ?></small>
                </div>
            </div>
            <nav class="sidebar-menu">
                <a href="/estoquemh/pages/sistema.php">📦 <span>Estoque</span></a>
                <a href="/estoquemh/pages/em-uso.php" class="active">🚀 <span>Em Uso</span></a>
                <a href="/estoquemh/pages/usuario.php">👥 <span>Usuários</span></a>
                <a href="/estoquemh/pages/relatorio_bi_dashboard.php">📊 <span>Relatórios BI</span></a>
                <a href="/estoquemh/pages/login.php" style="margin-top: auto; color: #f87171;">🚪 <span>Sair</span></a>
            </nav>
        </div>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 15px;">
                <button class="btn-toggle" onclick="toggleSidebar()">☰</button>
                <h1>Em Uso</h1>
            </div>

            <div class="topbar-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button class="btn-primary" onclick="abrirModal('item')">Adicionar Item</button>
            </div>
        </header>

        <?php if (!empty($_SESSION['sucesso'])): ?>
            <div style="background-color: rgba(34, 197, 94, 0.1); border-left: 4px solid #22c55e; padding: 12px 16px; margin: 15px 20px 0; border-radius: 4px; color: #22c55e;">
                <?= htmlspecialchars($_SESSION['sucesso']) ?>
            </div>
            <?php unset($_SESSION['sucesso']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['erro'])): ?>
            <div style="background-color: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444; padding: 12px 16px; margin: 15px 20px 0; border-radius: 4px; color: #ef4444;">
                <?= htmlspecialchars($_SESSION['erro']) ?>
            </div>
            <?php unset($_SESSION['erro']); ?>
        <?php endif; ?>

        <div class="cards">
            <div class="card kpi-card" role="button" tabindex="0" data-card-action="em-uso-todos" title="Mostrar todos os itens">
                <h3><?= (int)$totalRegistrosEmUso ?></h3>
                <span>Total de itens</span>
            </div>

            <div class="card kpi-card" role="button" tabindex="0" data-card-action="em-uso-ativos" title="Filtrar itens ativos">
                <h3><?= (int)$totalAtivosEmUso ?></h3>
                <span>Itens ativos</span>
            </div>

            <div class="card kpi-card" role="button" tabindex="0" data-card-action="em-uso-inativos" title="Filtrar itens desativados">
                <h3><?= (int)$totalInativosEmUso ?></h3>
                <span>Itens desativados</span>
            </div>
        </div>

        <div class="filter-trigger-row">
            <button class="btn-primary" type="button" onclick="abrirModal('filtros-em-uso')">Filtros</button>
        </div>

        <!-- TABELA -->
        <div class="glass-panel" style="padding: 0; overflow: hidden;">
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                            <th style="width: 30%;">Item</th>
                            <th style="width: 10%; text-align:center;">Qtd.</th>
                            <th>Patrimônio</th>
                            <th>Setor</th>
                            <th>Categoria</th>
                            <th>Observação</th>
                            <th>Status</th>
                            <th style="width: 160px;">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
<?php if ($resultado_em_uso && mysqli_num_rows($resultado_em_uso) > 0): ?>
    <?php while ($item = mysqli_fetch_assoc($resultado_em_uso)): ?>
        <tr class="<?= ((int)($item['ativo'] ?? 1) === 0) ? 'item-desativado' : '' ?>">

            <td>
                <strong>
                    <?= $categoryEmojis[$item['categoria_id']] ?? '📦' ?>
                    <?= htmlspecialchars($item['nome']) ?>
                </strong>
            </td>

            <td style="text-align:center; font-weight:bold;">
                1
            </td>

            <td style="text-align:center;">
                <span class="badge" style="background:#8b5cf6;">
                    <?= htmlspecialchars($item['patrimonio'] ?: 'PC-000000') ?>
                </span>
            </td>

            <td><?= htmlspecialchars($item['setor_nome']) ?></td>
            <td><?= htmlspecialchars($item['categoria_nome']) ?></td>

            <td>
                <small style="color:#64748b;">
                    <?= htmlspecialchars($item['observacao'] ?: '—') ?>
                </small>
            </td>

            <td style="text-align:center;">
                <span class="badge <?= ((int)$item['ativo'] === 1) ? 'badge-success' : 'badge-danger' ?>">
                    <?= ((int)$item['ativo'] === 1) ? 'Ativo' : 'Desativado' ?>
                </span>
            </td>

            <td>
                <div style="display:flex; gap:6px; justify-content:center;">

                    <button class="btn-icon btn-loc"
                            title="Foto da Localizacao"
                            onclick="abrirFotoLocalizacaoEmUso(<?= (int)$item['id'] ?>)">
                        <img src="/estoquemh/img/loc.png">
                    </button>

                    <button class="btn-icon btn-edit"
                            title="Editar"
                            onclick="abrirModalEditar(
                                <?= (int)$item['id'] ?>,
                                '<?= addslashes($item['nome']) ?>',
                                <?= (int)$item['categoria_id'] ?>,
                                <?= (int)$item['setor_id'] ?>,
                                '<?= addslashes($item['patrimonio'] ?: 'PC-000000') ?>',
                                '<?= addslashes($item['observacao'] ?? '') ?>'
                            )">
                        <img src="/estoquemh/img/editar.png">
                    </button>

                    <button class="btn-icon btn-info"
                            title="Detalhes"
                            onclick="abrirDetalhesEmUso(
                                <?= (int)$item['id'] ?>,
                                '<?= addslashes($item['nome']) ?>',
                                '<?= addslashes($item['patrimonio'] ?: 'PC-000000') ?>',
                                '<?= addslashes($item['setor_nome']) ?>',
                                '<?= addslashes($item['categoria_nome']) ?>'
                            )">
                        <img src="/estoquemh/img/info.png">
                    </button>

                    <button class="btn-icon btn-del"
                            title="Excluir"
                            onclick="abrirModalExcluirEmUso(
                                <?= (int)$item['id'] ?>,
                                '<?= addslashes($item['nome']) ?>'
                            )">
                        <img src="/estoquemh/img/excluir.png">
                    </button>

                    <button class="btn-icon btn-warning"
                            title="<?= ((int)$item['ativo'] === 1) ? 'Desativar' : 'Ativar' ?>"
                            onclick="abrirModalStatusEmUso(
                                <?= (int)$item['id'] ?>,
                                '<?= addslashes($item['nome']) ?>',
                                <?= (int)$item['ativo'] ?>
                            )">
                        <img src="/estoquemh/img/<?= ((int)$item['ativo'] === 1) ? 'cancel.png' : 'check.png' ?>">
                    </button>

                </div>
            </td>

        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="8"
            style="text-align:center; padding:30px; color:var(--text-muted);">
            Nenhum item em uso encontrado.
        </td>
    </tr>
<?php endif; ?>
</tbody>
                </table>
            </div>
            <div class="table-footer">
                <div class="paginacao">
                    <?php for ($p = 1; $p <= $totalPaginasEmUso; $p++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['pagina_em_uso' => $p])) ?>" class="<?= $p == $paginaEmUso ? 'ativo' : '' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- MODAIS -->
<!-- Modal: Filtros -->
<div class="modal-bg" id="modal-filtros-em-uso-bg">
    <div class="modal">
        <span class="close" onclick="closeModal('filtros-em-uso')">×</span>
        <h2>Filtros</h2>

        <form method="GET">
            <input type="text" name="busca_em_uso" placeholder="Buscar item" value="<?= htmlspecialchars($_GET['busca_em_uso'] ?? '') ?>">

            <select name="setor_em_uso">
                <option value="">Todos os Setores</option>
                <?php
                $res_setores_filter = $cone->query("SELECT DISTINCT s.id, s.nome FROM setores s INNER JOIN itens_em_uso ieu ON s.id = ieu.setor_id WHERE s.ativo = 1 ORDER BY s.nome");
                while($setor = $res_setores_filter->fetch_assoc()): ?>
                    <option value="<?= $setor['id'] ?>" <?= ($_GET['setor_em_uso'] ?? '') == $setor['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($setor['nome']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <select name="categoria_em_uso">
                <option value="">Todas as Categorias</option>
                <?php
                $res_cat_filter = $cone->query("SELECT DISTINCT c.id, c.nome FROM categorias c INNER JOIN itens_em_uso ieu ON c.id = ieu.categoria_id WHERE ieu.ativo = 1 ORDER BY c.nome");
                while($cat = $res_cat_filter->fetch_assoc()): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($_GET['categoria_em_uso'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nome']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <select name="limite_em_uso">
                <option value="10" <?= ($_GET['limite_em_uso'] ?? 10) == 10 ? 'selected' : '' ?>>10 por pagina</option>
                <option value="15" <?= ($_GET['limite_em_uso'] ?? 10) == 15 ? 'selected' : '' ?>>15 por pagina</option>
                <option value="25" <?= ($_GET['limite_em_uso'] ?? 10) == 25 ? 'selected' : '' ?>>25 por pagina</option>
                <option value="50" <?= ($_GET['limite_em_uso'] ?? 10) == 50 ? 'selected' : '' ?>>50 por pagina</option>
            </select>

            <select name="status_em_uso">
                <option value="">Todos</option>
                <option value="1" <?= ($_GET['status_em_uso'] ?? '') === '1' ? 'selected' : '' ?>>Ativos</option>
                <option value="0" <?= ($_GET['status_em_uso'] ?? '') === '0' ? 'selected' : '' ?>>Desativados</option>
            </select>

            <div class="modal-buttons">
                <button class="btn-cancel" type="button" onclick="closeModal('filtros-em-uso')">Cancelar</button>
                <button class="btn-primary" type="submit">Aplicar filtros</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Desativar Item em Uso -->
<div class="modal-bg" id="modal-status-em-uso-bg">
    <div class="modal">
        <span class="close" onclick="closeModal('status-em-uso')">×</span>

        <h2 id="status-modal-titulo"></h2>

        <p id="status-modal-texto" style="margin:15px 0;"></p>

        <form method="post" id="form-status-em-uso">
            <input type="hidden" name="item_id" id="status-item-id">
            <input type="hidden" name="acao" id="status-acao">

            <div id="campo-observacao">
                <label>Observação</label>
                <textarea name="observacao"
                          id="status-observacao"
                          rows="4"
                          placeholder="Informe o motivo"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn-secondary"
                        onclick="closeModal('status-em-uso')">
                    Cancelar
                </button>

                <button type="submit"
                        id="status-modal-btn">
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Adicionar Item em Uso -->
<div class="modal-bg" id="modal-item-bg">
    <div class="modal">
        <span class="close" onclick="closeModal('item')">×</span>
        <h2>Adicionar Item em Uso</h2>

        <form method="post" action="/estoquemh/php/add_em_uso.php" enctype="multipart/form-data">
            <input type="text" name="item-nome" id="item-nome" placeholder="Nome do item" required>

            <select name="setor_id" id="setor-id" required style="margin-top: 5px;">
                <option value="">Selecione o Setor</option>
                <?php
                $res_setores = mysqli_query($cone, "SELECT id, nome FROM setores WHERE ativo = 1 ORDER BY nome");
                while ($row = mysqli_fetch_assoc($res_setores)) {
                    echo "<option value='{$row['id']}'>{$row['nome']}</option>";
                }
                ?>
            </select>

            <input type="text" name="patrimonio" id="patrimonio" placeholder="Patrimônio (opcional)" style="margin-top: 5px;">

            <select name="item-categoria" id="item-categoria" required style="margin-top: 5px;">
                <option value="">Selecione a Categoria</option>
                <?php
                $res = mysqli_query($cone, "SELECT id, nome FROM categorias WHERE parent_id IS NOT NULL");
                while ($row = mysqli_fetch_assoc($res)) {
                    echo "<option value='{$row['id']}'>{$row['nome']}</option>";
                }
                ?>
            </select>

            <textarea name="item-observacao" id="item-observacao"
                      placeholder="Observações (opcional)" rows="3" style="margin-top: 5px;"></textarea>

            <label style="margin-top: 10px; font-weight: 600; color: #93c5fd;">📍 Localização (opcional)</label>
            <div class="file-input-wrapper">
                <input type="file" name="foto_loc" accept="image/*" capture="environment" id="foto_loc_input" class="file-input-mobile">
                <div id="foto_preview" class="foto-preview"></div>
            </div>

            <button type="submit" class="btn-primary" style="margin-top: 15px;">Salvar</button>
        </form>
    </div>
</div>

<!-- Modal: Editar Item em Uso -->
<div class="modal-bg" id="modal-editar-bg">
    <div class="modal">
        <span class="close" onclick="closeModal('editar')">×</span>
        <h2>Editar Item em Uso</h2>

        <form method="post" action="/estoquemh/php/edit_em_uso.php" enctype="multipart/form-data" id="form-editar-item">
            <input type="hidden" name="item-id" id="editar-item-id">

            <input type="text" name="item-nome" id="editar-item-nome" placeholder="Nome do item" required>

            <select name="setor_id" id="editar-setor-id" required style="margin-top: 5px;">
                <option value="">Selecione o Setor</option>
                <?php
                $res_setores = mysqli_query($cone, "SELECT id, nome FROM setores WHERE ativo = 1 ORDER BY nome");
                while ($row = mysqli_fetch_assoc($res_setores)) {
                    echo "<option value='{$row['id']}'>{$row['nome']}</option>";
                }
                ?>
            </select>

            <input type="text" name="patrimonio" id="editar-patrimonio" placeholder="Patrimônio (opcional)" style="margin-top: 5px;">

            <select name="item-categoria" id="editar-item-categoria" required style="margin-top: 5px;">
                <option value="">Selecione a Categoria</option>
                <?php
                $res = mysqli_query($cone, "SELECT id, nome FROM categorias WHERE parent_id IS NOT NULL");
                while ($row = mysqli_fetch_assoc($res)) {
                    echo "<option value='{$row['id']}'>{$row['nome']}</option>";
                }
                ?>
            </select>

            <textarea name="item-observacao" id="editar-item-observacao"
                      placeholder="Observações (opcional)" rows="3" style="margin-top: 5px;"></textarea>

            <!-- Preview da foto atual (se já existir) -->
            <label>Foto atual</label>
            <div id="foto_atual_preview" class="foto-preview"></div>

            <label style="margin-top: 10px; font-weight: 600; color: #93c5fd;">📍 Localização (opcional)</label>
            <div class="file-input-wrapper">
                <input type="file" name="foto_loc" accept="image/*" capture="environment" id="foto_loc_edit_input" class="file-input-mobile">
                <div id="foto_edit_preview" class="foto-preview"></div>
            </div>

            <button type="submit" class="btn-primary" style="margin-top: 15px;">Atualizar</button>
        </form>
    </div>
</div>

<!-- Modal: Foto da Localização -->
<div class="modal-bg" id="modal-foto-loc-bg">
    <div class="modal" style="max-width: 600px;">
        <span class="close" onclick="closeModal('foto-loc')">×</span>
        <h2 style="margin-bottom: 20px;">📍 Localização do Patrimônio</h2>
        <img id="img-foto-loc" class="foto-loc-modal" alt="Foto da localização" style="width: 100%; border-radius: 8px; max-height: 500px; object-fit: contain;">
    </div>
</div>

<!-- Modal: Excluir Item em Uso -->
<div class="modal-bg" id="modal-excluir-em-uso-bg">
    <div class="modal">
        <span class="close" onclick="closeModal('excluir-em-uso')">×</span>
        <h2>Confirmar Exclusão</h2>
        <p id="excluir-item-nome-text" style="margin-bottom: 20px; color: #e0e7ff;">Tem certeza que deseja excluir este item?</p>
        <form method="post" action="/estoquemh/php/excluir_em_uso.php" style="display: flex; gap: 10px; justify-content: flex-end;">
            <input type="hidden" name="item_id" id="excluir-item-id">
            <button type="button" class="btn-secondary" onclick="closeModal('excluir-em-uso')">Cancelar</button>
            <button type="submit" class="btn-danger">Excluir</button>
        </form>
    </div>
</div>

<!-- Modal: Detalhes Item em Uso -->
<div class="modal-bg" id="modal-detalhes-em-uso-bg">
    <div class="modal">
        <span class="close" onclick="closeModal('detalhes-em-uso')">×</span>
        <h2 style="margin-bottom: 25px; color: #e0e7ff;">Detalhes do Patrimônio</h2>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <label style="color: #93c5fd; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Item</label>
                <p id="detalhe-item-nome" style="font-weight: 600; font-size: 15px; color: #e0e7ff; margin-top: 8px; margin-bottom: 0;">-</p>
            </div>
            
            <div>
                <label style="color: #93c5fd; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Patrimônio</label>
                <p id="detalhe-item-patrimonio" style="font-weight: 700; font-size: 15px; color: #c084fc; margin-top: 8px; margin-bottom: 0; background: rgba(139, 92, 246, 0.15); padding: 10px 12px; border-radius: 6px; border-left: 3px solid #8b5cf6;">PC-000000</p>
            </div>

            <div>
                <label style="color: #93c5fd; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Setor</label>
                <p id="detalhe-item-setor" style="font-weight: 600; font-size: 15px; color: #e0e7ff; margin-top: 8px; margin-bottom: 0;">-</p>
            </div>

            <div>
                <label style="color: #93c5fd; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Categoria</label>
                <p id="detalhe-item-categoria" style="font-weight: 600; font-size: 15px; color: #e0e7ff; margin-top: 8px; margin-bottom: 0;">-</p>
            </div>
        </div>

        <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(139, 92, 246, 0.2);">
            <button type="button" class="btn-primary" onclick="closeModal('detalhes-em-uso')" style="width: 100%; text-align: center;">Fechar</button>
        </div>
    </div>
</div>

<!-- SCRIPTS -->
<script src="/estoquemh/js/modal.js"></script>
<script src="/estoquemh/js/responsive.js"></script>
<script src="/estoquemh/js/cascading_select.js"></script>
<script src="/estoquemh/js/camera-upload.js"></script>

<button type="button" class="fab-add-item" onclick="abrirModal('item')" aria-label="Adicionar item em uso" title="Adicionar item em uso">
    +
</button>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof initCameraUpload === 'function') {
        initCameraUpload();
    }

    if (typeof window.abrirModal === 'function') {
        const originalAbrirModal = window.abrirModal;
        window.abrirModal = function(modalId) {
            originalAbrirModal(modalId);
            if (modalId === 'item' || modalId === 'editar') {
                setTimeout(function() {
                    if (typeof initCascadingSelects === 'function') initCascadingSelects();
                }, 100);
                setTimeout(function() {
                    if (typeof initCameraUpload === 'function') initCameraUpload();
                }, 150);
            }
        };
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.kpi-card');
    cards.forEach(function(card) {
        card.addEventListener('click', function() {
            handleEmUsoCardClick(card.dataset.cardAction);
        });

        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                handleEmUsoCardClick(card.dataset.cardAction);
            }
        });
    });
});

function handleEmUsoCardClick(action) {
    const url = new URL(window.location.href);
    url.searchParams.delete('pagina_em_uso');

    if (action === 'em-uso-todos') {
        url.searchParams.delete('status_em_uso');
    } else if (action === 'em-uso-ativos') {
        url.searchParams.set('status_em_uso', '1');
    } else if (action === 'em-uso-inativos') {
        url.searchParams.set('status_em_uso', '0');
    } else {
        return;
    }

    window.location.href = url.toString();
}

function abrirDetalhesEmUso(itemId, itemNome, patrimonio, setor, categoria) {
    document.getElementById('detalhe-item-nome').innerText = itemNome;
    document.getElementById('detalhe-item-patrimonio').innerText = patrimonio || 'PC-000000';
    document.getElementById('detalhe-item-setor').innerText = setor;
    document.getElementById('detalhe-item-categoria').innerText = categoria;

    abrirModal('detalhes-em-uso');
}

function abrirFotoLocalizacaoEmUso(itemId) {
    fetch(`/estoquemh/php/exibir_foto.php?id=${itemId}`)
        .then(response => {
            if (response.ok) {
                var img = document.getElementById('img-foto-loc');
                if (img) img.src = `/estoquemh/php/exibir_foto.php?id=${itemId}&t=${new Date().getTime()}`;
                if (typeof abrirModal === 'function') abrirModal('foto-loc');
            } else {
                alert('Foto não encontrada!');
            }
        })
        .catch(error => {
            console.error('Erro ao carregar foto:', error);
            alert('Erro ao carregar a foto!');
        });
}

function abrirModalEditar(itemId, nome, catId, setorId, patrimonio, obs) {
    document.getElementById('editar-item-id').value = itemId;
    document.getElementById('editar-item-nome').value = nome;
    document.getElementById('editar-item-categoria').value = catId;
    document.getElementById('editar-setor-id').value = setorId || '';
    document.getElementById('editar-patrimonio').value = patrimonio || '';
    document.getElementById('editar-item-observacao').value = obs || '';

    // carregar foto atual na pré-visualização
    const atualPreview = document.getElementById('foto_atual_preview');
    if (atualPreview) {
        atualPreview.innerHTML = '';
        atualPreview.classList.remove('active');
        const url = `/estoquemh/php/exibir_foto.php?id=${itemId}&t=${Date.now()}`;
        const tester = new Image();
        tester.onload = function() {
            if (this.naturalWidth > 2 && this.naturalHeight > 2) {
                atualPreview.innerHTML = `<img src="${url}" alt="Foto atual">`;
                atualPreview.classList.add('active');
            }
        };
        tester.onerror = function() {
            // nada a fazer
        };
        tester.src = url;
    }

    if (typeof abrirModal === 'function') abrirModal('editar');
}

function abrirModalExcluirEmUso(itemId, itemNome) {
    document.getElementById('excluir-item-id').value = itemId;
    document.getElementById('excluir-item-nome-text').innerText = 'Tem certeza que deseja excluir "' + itemNome + '"?';
    if (typeof abrirModal === 'function') abrirModal('excluir-em-uso');
}

function abrirModal(tipo) {
    console.log('Abrindo modal:', tipo);

    const modal = document.getElementById(`modal-${tipo}-bg`);
    if (!modal) {
        console.error('Modal não encontrado:', `modal-${tipo}-bg`);
        return;
    }

    modal.style.display = 'flex';
    modal.style.opacity = '1';
    modal.style.visibility = 'visible';
    modal.style.zIndex = '9999';

    modal.classList.add('active');
}

function closeModal(tipo) {
    const modal = document.getElementById(`modal-${tipo}-bg`);
    if (!modal) return;

    modal.style.display = 'none';
    modal.classList.remove('active');

    if (tipo === 'editar') {
        const atual = document.getElementById('foto_atual_preview');
        const novo = document.getElementById('foto_edit_preview');
        if (atual) {
            atual.innerHTML = '';
            atual.classList.remove('active');
        }
        if (novo) {
            novo.innerHTML = '';
            novo.classList.remove('active');
            const fileInput = document.getElementById('foto_loc_edit_input');
            if (fileInput) fileInput.value = '';
        }
    }
}

function abrirModalStatusEmUso(id, nome, ativo) {
    document.getElementById('status-item-id').value = id;

    const titulo = document.getElementById('status-modal-titulo');
    const texto  = document.getElementById('status-modal-texto');
    const btn    = document.getElementById('status-modal-btn');
    const acao   = document.getElementById('status-acao');
    const obsBox = document.getElementById('campo-observacao');
    const obs    = document.getElementById('status-observacao');
    const form   = document.getElementById('form-status-em-uso');

    if (ativo == 1) {
        // DESATIVAR
        titulo.innerText = 'Desativar item em uso';
        texto.innerText  = `Deseja desativar o item "${nome}"?`;
        btn.innerText    = 'Desativar';
        btn.className    = 'btn-warning';
        acao.value       = 'desativar';
        obs.required     = true;
        obsBox.style.display = 'block';
        form.action = '/estoquemh/php/status_em_uso.php';
    } else {
        // ATIVAR
        titulo.innerText = 'Ativar item em uso';
        texto.innerText  = `Deseja ativar novamente o item "${nome}"?`;
        btn.innerText    = 'Ativar';
        btn.className    = 'btn-success';
        acao.value       = 'ativar';
        obs.required     = false;
        obs.value        = '';
        obsBox.style.display = 'none';
        form.action = '/estoquemh/php/status_em_uso.php';
    }

    abrirModal('status-em-uso');
}
</script>

</body>
</html>
