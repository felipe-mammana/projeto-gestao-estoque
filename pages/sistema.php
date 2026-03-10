<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/locations_crud.php';
$id_user = ensureLoggedInUser();
include $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";
if (isset($cone) && $cone instanceof mysqli) {
    mysqli_set_charset($cone, 'utf8mb4');
}

$isAdmin = ($_SESSION['usuario']['tipo'] ?? '') === 'admin';

// Carregar estrutura hierárquica de locais
$locationsHierarchy = getLocationsHierarchy($cone);
$parentLocations = getParentLocations($cone);

// ================= FILTROS =================
$where = [];

// Busca por nome do item
if (!empty($_GET['busca'])) {
    $busca = mysqli_real_escape_string($cone, $_GET['busca']);
    $where[] = "I.nome LIKE '%$busca%'";
}

// Filtro por localização pai
if (!empty($_GET['localizacao_pai'])) {
    $localizacaoPai = mysqli_real_escape_string($cone, $_GET['localizacao_pai']);
    $where[] = "I.localizacao LIKE '$localizacaoPai%'";
}

// Filtro por localização
if (!empty($_GET['localizacao'])) {
    $local = mysqli_real_escape_string($cone, $_GET['localizacao']);
    $where[] = "I.localizacao LIKE '%$local%'";
}

// Filtro por subcategoria
if (!empty($_GET['subcategoria'])) {
    $subcategoria = (int)$_GET['subcategoria'];
    $where[] = "c.id = $subcategoria";
}

// Filtro por status
if (!empty($_GET['status'])) {
    if ($_GET['status'] === 'ok') {
        $where[] = "I.quantidade > c.quantidade_minima";
    } elseif ($_GET['status'] === 'baixo') {
        $where[] = "I.quantidade > 0 AND I.quantidade <= c.quantidade_minima";
    } elseif ($_GET['status'] === 'zero') {
        $where[] = "I.quantidade = 0";
    }
}

// Filtro por categoria pai
if (!empty($_GET['categoria_pai'])) {
    $categoriaPai = (int)$_GET['categoria_pai'];
    $where[] = "c.parent_id = $categoriaPai";
}

$porPagina = (int)($_GET['limite'] ?? 10);
$pagina = max((int)($_GET['pagina'] ?? 1), 1);
$offset = ($pagina - 1) * $porPagina;

// ================= TOTAL DE REGISTROS (PAGINAÇÃO) =================
$sql_total = "
    SELECT COUNT(*) AS total
    FROM itens I
    INNER JOIN categorias c ON I.categoria_id = c.id
";

if (!empty($where)) {
    $sql_total .= " WHERE " . implode(" AND ", $where);
}

$res_total = mysqli_query($cone, $sql_total);
$totalRegistros = mysqli_fetch_assoc($res_total)['total'] ?? 0;

$totalPaginas = max(ceil($totalRegistros / $porPagina), 1);
// ================= BUSCA ITENS =================
$sql_item = "
SELECT 
    I.id,
    I.nome AS item_nome,
    I.categoria_id,
    c.nome AS categoria_nome,
    c.quantidade_minima AS categoria_minima,
    I.localizacao,
    I.localizacao_id,  -- ADICIONE ESTA LINHA
    I.quantidade,
    I.status,
    I.observacao
FROM itens I
INNER JOIN categorias c ON I.categoria_id = c.id
";

if (!empty($where)) {
    $sql_item .= " WHERE " . implode(" AND ", $where);
}

$sql_item .= " ORDER BY I.nome LIMIT $porPagina OFFSET $offset";
// Otimização: limite de 1000 registros para evitar gargalos

$resultado = mysqli_query($cone, $sql_item);

// ================= BUSCA CATEGORIAS (Para o filtro) =================
$sql_categorias_filtro = "SELECT id, nome FROM categorias WHERE parent_id IS NULL ORDER BY nome";
$result_cats = mysqli_query($cone, $sql_categorias_filtro);

// ====================================
// BUSCAR CATEGORIAS (PAI/FILHOS) PARA MODAIS
// ====================================

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

// ====================================
// CALCULAR DADOS DOS CARDS (se quiser adicionar depois)
// ====================================

// ====================================
// MAPEAR EMOJIS POR CATEGORIA
// ====================================
$categoryEmojis = [];

// Função helper para mapear emoji por ID
function getEmojiForCategory($catId) {
    switch($catId) {
        case 1: return '🖱️'; // Perifericos
        case 2: return '⌨️'; // Teclado
        case 9: return '🔧'; // Hardware
        case 10: return '📡'; // Redes
        case 11: return '🏗️'; // Infraestrutura
        case 12: return '💿'; // Software
        case 13: return '🎁'; // Acessórios
        case 14: return '🖱️'; // Mouse
        case 15: return '🖥️'; // Monitor
        case 16: return '🖨️'; // Impressora
        case 17: return '📠'; // Scanner
        case 18: return '📷'; // Webcam
        case 19: return '🎧'; // Headset
        case 20: return '💻'; // Computadores
        case 21: return '💻'; // Notebooks
        case 22: return '🖥️'; // Servidores
        case 23: return '🔌'; // Placa-mãe
        case 24: return '🧠'; // Memória RAM
        case 25: return '⚙️'; // Processador
        case 26: return '💾'; // HD / SSD
        case 27: return '🔋'; // Fonte
        case 28: return '📡'; // Roteador
        case 29: return '🔄'; // Switch
        case 30: return '🔌'; // Cabo de Rede
        case 31: return '📶'; // Access Point
        case 32: return '🔌'; // Patch Panel
        case 33: return '🌐'; // Placa de Rede
        case 34: return '🗂️'; // Rack
        case 35: return '🔋'; // No-break
        case 36: return '⚡'; // Estabilizador
        case 37: return '🔌'; // Cabeamento Estruturado
        case 38: return '❄️'; // Ar-condicionado
        case 39: return '🖥️'; // Sistema Operacional
        case 40: return '📄'; // Licença Office
        case 41: return '🛡️'; // Antivírus
        case 42: return '🔧'; // Ferramentas de Suporte
        case 43: return '📦'; // Drivers
        case 44: return '🔌'; // Cabos
        case 45: return '🔌'; // Adaptador
        case 46: return '🖱️'; // Mousepad
        case 47: return '🎁'; // Suporte
        case 50: return '🎵'; // lula
        case 51: return '📋'; // Organização
        default: return '📦'; // Padrão
    }
}

// Popular array com categorias PAI
foreach ($categorias as $cat) {
    $categoryEmojis[$cat['id']] = getEmojiForCategory($cat['id']);
    
    // Popular também as subcategorias
    if (!empty($cat['subcategorias'])) {
        foreach ($cat['subcategorias'] as $sub) {
            $categoryEmojis[$sub['id']] = getEmojiForCategory($sub['id']);
        }
    }
}

$total_itens = mysqli_fetch_assoc(mysqli_query($cone, "SELECT SUM(quantidade) AS total FROM itens"))['total'] ?? 0;
$total_cat = mysqli_fetch_assoc(mysqli_query($cone, "SELECT COUNT(id) AS total FROM categorias"))['total'] ?? 0;

$sql_totalb = "
SELECT COUNT(*) AS total 
FROM itens I
JOIN categorias c ON I.categoria_id = c.id
WHERE I.quantidade > 0 AND I.quantidade <= c.quantidade_minima";
$total_baixo = mysqli_fetch_assoc(mysqli_query($cone, $sql_totalb))['total'] ?? 0;

$total_zero = mysqli_fetch_assoc(mysqli_query($cone, "SELECT COUNT(*) AS total FROM itens WHERE quantidade = 0"))['total'] ?? 0;
// ================= SETORES AGRUPADOS POR ANDAR =================
$setoresPorAndar = [];

$sql_setores = "
    SELECT id, nome, andar
    FROM setores
    WHERE ativo = 1
    ORDER BY andar ASC, nome ASC
";

$res_setores = $cone->query($sql_setores);

if ($res_setores) {
    while ($row = $res_setores->fetch_assoc()) {
        $andar = (int)$row['andar']; // SEM TEXTO, SEM DESCRICAO
        $setoresPorAndar[$andar][] = $row;
    }
}
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
    <title>Estoque</title>
    <link rel="stylesheet" href="../css/style.css">
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
                 <img src="../php/foto_usuario.php?id=<?= $_SESSION['usuario']['id'] ?>" class="perfil-foto" alt="Foto">
                <div class="perfil-info">
                    <strong class="perfil-nome"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></strong>
                    <span class="perfil-setor"><?= htmlspecialchars($_SESSION['usuario']['setor_nome']) ?></span>
                    <small class="perfil-cargo"><?= htmlspecialchars($_SESSION['usuario']['cargo']) ?></small>
                </div>
            </div>
            <nav class="sidebar-menu">
                <a href="sistema.php" class="<?= (!isset($_GET['view']) || $_GET['view'] !== 'em-uso') ? 'active' : '' ?>">📦 <span>Estoque</span></a>
                <a href="em-uso.php" class="<?= (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'em-uso.php') !== false) ? 'active' : '' ?>">🚀 <span>Em Uso</span></a>
                <a href="usuario.php">👥 <span>Usuários</span></a>
                <a href="relatorio_bi_dashboard.php">📊 <span>Relatórios BI</span></a>
                <a href="/estoquemh/pages/login.php" style="margin-top: auto; color: #f87171;">🚪 <span>Sair</span></a>
            </nav>

            <div class="sidebar-actions">
                <a href="#" class="sidebar-action-link" onclick="abrirModal('categoria'); return false;">➕ <span>Adicionar Categoria</span></a>
                <?php if ($isAdmin): ?>
                    <a href="#" class="sidebar-action-link" onclick="abrirModal('locations'); return false;">📍 <span>Gerenciar Locais</span></a>
                <?php endif; ?>
            </div>
        </div>
    </aside>

    <div class="main-content">
        <header class="topbar">
            
            <div style="display: flex; align-items: center; gap: 15px;">
                <button class="btn-toggle" onclick="toggleSidebar()">☰</button>
                <h1>Controle de Estoque</h1>
            </div>

            <div class="topbar-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button class="btn-primary btn-add-item-inline" onclick="abrirModal('item')">Adicionar Item</button>
                <button class="btn-primary btn-cat-top" onclick="abrirModal('categoria')">Adicionar Categoria</button>
                <?php if ($isAdmin): ?>
                    <button class="btn-primary btn-loc-top" onclick="abrirModal('locations')" style="background-color: #8b5cf6;">Gerenciar Locais</button>
                <?php endif; ?>
            </div>
        </header>

        <!-- ABAS DE NAVEGAÇÃO (agora simplificada; 'Em Uso' é acessado pela sidebar) -->
        <div class="section-tabs">
            <button class="tab-btn <?= $isEmUsoView ? '' : 'active' ?>" onclick="mostrarAba('estoque')">📦 Estoque</button>
        </div>

        <!-- SEÇÃO ESTOQUE -->
        <div id="aba-estoque" class="aba-content <?= $isEmUsoView ? '' : 'active' ?>">
            <div class="cards">
                <div class="card kpi-card" role="button" tabindex="0" data-card-action="total-itens" title="Mostrar todos os itens">
                    <h3><?= (int)$total_itens ?></h3>
                    <span>Total de itens em estoque</span>
                </div>

                <div class="card kpi-card" role="button" tabindex="0" data-card-action="categorias" title="Abrir listagem de categorias">
                    <h3><?= (int)$total_cat ?></h3>
                    <span>Categorias cadastradas</span>
                </div>

                <div class="card kpi-card" role="button" tabindex="0" data-card-action="estoque-baixo" title="Filtrar itens com estoque baixo">
                    <h3><?= (int)$total_baixo ?></h3>
                    <span>Itens com estoque baixo</span>
                </div>

                <div class="card kpi-card" role="button" tabindex="0" data-card-action="zerados" title="Filtrar itens zerados">
                    <h3><?= (int)$total_zero ?></h3>
                    <span>Itens zerados</span>
                </div>
            </div>

            <div class="filter-trigger-row">
                <button class="btn-primary" type="button" onclick="abrirModalFiltros()">Filtros</button>
            </div>
            <div class="glass-panel" id="estoque-tabela" style="padding: 0; overflow: hidden;">
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40%;">Item</th>
                            <th>Categoria</th>
                            <th>Localização</th>
                            <th style="text-align: center; width: 60px;">Qtd.</th>
                            <th style="text-align: center; width: 80px;">Status</th>
                            <th>Observação</th>
                            <th style="width: 100px;">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if($resultado && mysqli_num_rows($resultado) > 0): ?>
                            <?php while($item = mysqli_fetch_assoc($resultado)): 
                                // Lógica de Status
                                $statusClass = 'badge-ok';
                                $statusText = 'Normal';
                                
                                if ($item['quantidade'] == 0) {
                                    $statusClass = 'badge-zero';
                                    $statusText = 'Zerado';
                                } elseif ($item['quantidade'] <= $item['categoria_minima']) {
                                    $statusClass = 'badge-low';
                                    $statusText = 'Baixo';
                                }
                            ?>
                            <tr>
                                <td><strong><?= ($categoryEmojis[$item['categoria_id']] ?? '📦') ?> <?= htmlspecialchars($item['item_nome']) ?></strong></td>
                                <td><?= htmlspecialchars($item['categoria_nome']) ?></td>
                                <td><?= htmlspecialchars($item['localizacao']) ?></td>
                                <td style="text-align: center; font-weight: bold;"><?= $item['quantidade'] ?></td>
                                <td style="text-align: center;">
                                    <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                </td>
                                <td>
                                    <small style="color: #64748b;">
                                        <?= htmlspecialchars($item['observacao'] ?: '—') ?>
                                    </small>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <!-- Editar -->
                                       <button class="btn-icon btn-edit"
    title="Editar"
    onclick="abrirModalEditar(
        <?= (int)$item['id'] ?>,
        '<?= htmlspecialchars($item['item_nome'], ENT_QUOTES) ?>',
        <?= (int)$item['categoria_id'] ?>,
        '<?= htmlspecialchars($item['localizacao_id'] ?? '', ENT_QUOTES) ?>',
        <?= (int)$item['quantidade'] ?>,
        '<?= htmlspecialchars($item['observacao'], ENT_QUOTES) ?>'
    )">
    <img src="/estoquemh/img/editar.png">
</button>



                                        <!-- Localização -->
                                        <button class="btn-icon btn-loc" title="Foto da Localização" onclick="abrirFotoLocalizacao(<?= $item['id'] ?>)">
                                            <img src="/estoquemh/img/loc.png">
                                        </button>

                                        <!-- Movimento -->
                                        <button class="btn-icon btn-move" title="Movimentar Estoque" onclick="abrirModalMovimento(<?= $item['id'] ?>, '<?= addslashes($item['item_nome']) ?>')">
                                            <img src="/estoquemh/img/move.png">
                                        </button>

                                        <!-- Excluir (admin) -->
                                        <?php if ($isAdmin): ?>
                                            <button class="btn-icon btn-del" title="Excluir" onclick="abrirModalExcluir(<?= (int)$item['id'] ?>, '<?= addslashes($item['item_nome']) ?>')">
                                                <img src="/estoquemh/img/excluir.png">
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                    Nenhum item encontrado.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <div class="paginacao">
                    <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $p])) ?>"
                           class="<?= $p == $pagina ? 'ativo' : '' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        </div> <!-- Fecha aba-estoque -->
<!-- ====================================
     MODAIS
     ==================================== -->

<!-- Modal: Exportar Logs -->
<div class="modal-bg" id="modal-exportar-logs">
    <div class="modal modal-sm">
        <span class="close" onclick="closeModal('exportar-logs')">×</span>
        <h2>Exportar Logs</h2>

        <form action="/estoquemh/php/logs_exportar.php" method="get">
            <label>Tipo de log</label>
            <select name="tipo">
                <option value="">Todos</option>
                <option value="ENTRADA">Entrada</option>
                <option value="SAIDA">Saída</option>
            </select>

            <label>Data inicial</label>
            <input type="date" name="data_inicio" required>

            <label>Data final</label>
            <input type="date" name="data_fim" required>

            <button type="submit" class="btn-primary">Exportar CSV</button>
        </form>
    </div>
</div>

<!-- Modal: Adicionar Item -->
<div class="modal-bg" id="modal-item-bg">
    <div class="modal">
        <span class="close" onclick="closeModal('item')">×</span>
        <h2>Adicionar Item</h2>

        <form method="post" action="/estoquemh/php/additem.php" enctype="multipart/form-data">
            <!-- Manter filtros e paginação -->
            <input type="hidden" name="redirect_busca" value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>">
            <input type="hidden" name="redirect_status" value="<?= htmlspecialchars($_GET['status'] ?? '') ?>">
            <input type="hidden" name="redirect_categoria_pai" value="<?= htmlspecialchars($_GET['categoria_pai'] ?? '') ?>">
            <input type="hidden" name="redirect_subcategoria" value="<?= htmlspecialchars($_GET['subcategoria'] ?? '') ?>">
            <input type="hidden" name="redirect_localizacao_pai" value="<?= htmlspecialchars($_GET['localizacao_pai'] ?? '') ?>">
            <input type="hidden" name="redirect_pagina" value="<?= htmlspecialchars($_GET['pagina'] ?? 1) ?>">
            <input type="hidden" name="redirect_limite" value="<?= htmlspecialchars($_GET['limite'] ?? 10) ?>">

            <input type="text" name="item-nome" id="item-nome" placeholder="Nome do item" required>

            <select name="item-categoria" id="item-categoria" required>
                <option value="">Selecione a Categoria</option>
                <?php
                $res = mysqli_query($cone, "SELECT id, nome FROM categorias WHERE parent_id IS NOT NULL");
                while ($row = mysqli_fetch_assoc($res)) {
                    echo "<option value='{$row['id']}'>{$row['nome']}</option>";
                }
                ?>
            </select>

            <!-- Cascading Select para Locais de Armazenamento -->
            <label style="margin-top: 15px; font-weight: 500;">Local de Armazenamento</label>
            
            <select name="item-location-pai" id="item-location-pai" style="margin-top: 5px;">
                <option value="">Selecione um local</option>
                <?php foreach ($parentLocations as $loc): ?>
                    <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['nome']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="item-location-filho" id="item-location-filho" style="margin-top: 5px;" disabled>
                <option value="">Selecione um local</option>
            </select>

            <!-- Campo legado para compatibilidade -->
            <input type="hidden" name="item-localizacao-id" id="item-localizacao-id" value="">
            
        
            
            <input type="number" name="item-qtde" id="item-qtde" placeholder="Quantidade" min="0" style="margin-top: 5px;">
                

            <textarea name="item-observacao" id="item-observacao"
                      placeholder="Observação (opcional)" rows="3"></textarea>

            <label>Foto da localização (opcional)</label>
            <div class="file-input-wrapper">
                <input type="file" name="foto_loc" accept="image/*" capture="environment" id="foto_loc_input" class="file-input-mobile">
                <div id="foto_preview" class="foto-preview"></div>
            </div>

            <button type="submit" class="btn-primary">Salvar</button>
        </form>
    </div>
</div>

<script>
// Ao abrir o modal de adicionar item, inicializar cascading selects
document.addEventListener('modalOpened', function(e) {
    if (e.detail === 'item') {
        initCascadingSelects();
    }
});
</script>

<!-- Modal: Editar Item -->
<div class="modal-bg" id="modal-editar-bg">
    <div class="modal">
        <span class="close" onclick="closeModal('editar')">×</span>
        <h2>Editar Item</h2>

        <form method="post" action="/estoquemh/php/editaritem.php" enctype="multipart/form-data" id="form-editar-item">
            <!-- Manter filtros e paginação -->
            <input type="hidden" name="redirect_busca" value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>">
            <input type="hidden" name="redirect_status" value="<?= htmlspecialchars($_GET['status'] ?? '') ?>">
            <input type="hidden" name="redirect_categoria_pai" value="<?= htmlspecialchars($_GET['categoria_pai'] ?? '') ?>">
            <input type="hidden" name="redirect_subcategoria" value="<?= htmlspecialchars($_GET['subcategoria'] ?? '') ?>">
            <input type="hidden" name="redirect_localizacao_pai" value="<?= htmlspecialchars($_GET['localizacao_pai'] ?? '') ?>">
            <input type="hidden" name="redirect_pagina" value="<?= htmlspecialchars($_GET['pagina'] ?? 1) ?>">
            <input type="hidden" name="redirect_limite" value="<?= htmlspecialchars($_GET['limite'] ?? 10) ?>">
            <input type="hidden" name="item-localizacao-id" id="editar-item-localizacao-id" value="">      
            <input type="hidden" name="item-id" id="editar-item-id">

            <input type="text" name="item-nome" id="editar-item-nome" placeholder="Nome do item" required>

            <select name="item-categoria" id="editar-item-categoria" required>
                <option value="">Selecione a Categoria</option>
                <?php
                $res = mysqli_query($cone, "SELECT id, nome FROM categorias WHERE parent_id IS NOT NULL");
                while ($row = mysqli_fetch_assoc($res)) {
                    echo "<option value='{$row['id']}'>{$row['nome']}</option>";
                }
                ?>
            </select>

            <!-- Cascading Select para Locais de Armazenamento (Edição) -->
            <label style="margin-top: 15px; font-weight: 500;">Local de Armazenamento</label>
            
            <select name="editar-item-location-pai" id="editar-item-location-pai" style="margin-top: 5px;">
                <option value="">Selecione um local</option>
                <?php foreach ($parentLocations as $loc): ?>
                    <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['nome']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="editar-item-location-filho" id="editar-item-location-filho" style="margin-top: 5px;" disabled>
                <option value="">Selecione um local</option>
            </select>

            <!-- Campo legado para compatibilidade -->
            <input type="hidden" name="editar-item-localizacao-id" id="editar-item-localizacao-id" value="">
            
            <input type="number" name="item-quantidade" id="editar-item-quantidade" placeholder="Quantidade" min="0" style="margin-top: 5px;">
            <!-- Mostrar a quantidade mínima da categoria (somente leitura) -->
<div style="background: rgba(59, 130, 246, 0.1); padding: 10px; border-radius: 8px; margin-top: 10px;">
    <label style="color: #93c5fd; font-size: 12px;">Quantidade Mínima (da categoria)</label>
    <p id="editar-categoria-minima-display" style="font-weight: 600; margin: 5px 0 0 0;">-</p>
</div>

            <textarea name="item-observacao" id="editar-item-observacao"
                      placeholder="Observação" rows="3"></textarea>

            <!-- Mostrar foto já existente quando for editar -->
            <label>Foto atual</label>
            <div id="foto_atual_preview" class="foto-preview"></div>

            <label style="margin-top:10px;">Nova foto da localização (opcional)</label>
            <div class="file-input-wrapper">
                <input type="file" name="foto_loc" accept="image/*" capture="environment" id="foto_loc_edit_input" class="file-input-mobile">
                <div id="foto_edit_preview" class="foto-preview"></div>
            </div>

            <button type="submit" class="btn-primary">Salvar Alterações</button>
        </form>
    </div>
</div>

<!-- Modal: Excluir Item -->
<div class="modal-bg" id="modal-excluir-bg">
    <div class="modal modal-excluir">
        <span class="close" onclick="closeModal('excluir')">×</span>
        <h2>Excluir item</h2>
        <p id="excluir-item-nome" class="item-nome">Item: <strong>-</strong></p>

        <form method="post" action="/estoquemh/php/excluir.php" id="form-excluir">
            <!-- Manter filtros e paginação -->
            <input type="hidden" name="redirect_busca" value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>">
            <input type="hidden" name="redirect_status" value="<?= htmlspecialchars($_GET['status'] ?? '') ?>">
            <input type="hidden" name="redirect_categoria_pai" value="<?= htmlspecialchars($_GET['categoria_pai'] ?? '') ?>">
            <input type="hidden" name="redirect_subcategoria" value="<?= htmlspecialchars($_GET['subcategoria'] ?? '') ?>">
            <input type="hidden" name="redirect_localizacao_pai" value="<?= htmlspecialchars($_GET['localizacao_pai'] ?? '') ?>">
            <input type="hidden" name="redirect_pagina" value="<?= htmlspecialchars($_GET['pagina'] ?? 1) ?>">
            <input type="hidden" name="redirect_limite" value="<?= htmlspecialchars($_GET['limite'] ?? 10) ?>">

            <div class="confirmacao">
    <label class="opcao">
        <input type="radio" name="confirmacao" value="1"
               onchange="controleExclusao(true)">
        <span>Sim, desejo excluir este item</span>
    </label>

    <label class="opcao">
        <input type="radio" name="confirmacao" value="0"
               onchange="controleExclusao(false)">
        <span>Não</span>
    </label>
</div>

            <div id="campo-motivo">
                <input type="text" name="motivo" id="motivo-texto"
                       placeholder="Informe o motivo (ex: sucata, venda, substituição...)">
                <small>O motivo da exclusão é obrigatório para registro.</small>
            </div>

            <input type="hidden" name="item_id" id="excluir-item-id">

            <button type="submit" id="btn-excluir" class="btn-primary" disabled>CONFIRMAR EXCLUSÃO</button>
        </form>
    </div>
</div>

<!-- Modal: Movimentação de Estoque -->
<div class="modal-bg" id="modal-movimento-bg">
    <div class="modal">
        <span class="close" onclick="closeModal('movimento')">×</span>
        <h2>Movimentar Estoque</h2>

        <p id="movimento-item-nome"></p>

        <form method="post" action="/estoquemh/php/movimento.php">
            <select name="movimento-tipo" required>
                <option value="entrada">Entrada</option>
                <option value="saida">Saída</option>
            </select>

            <div id="destinos-container">

    <div class="destino-bloco">
        <select name="setor_id[]" required>
    <option value="">Selecione o destino</option>

    <?php foreach ($setoresPorAndar as $andar => $lista): ?>
        <optgroup label="<?= $andar ?>º Andar">
            <?php foreach ($lista as $s): ?>
                <option value="<?= $s['id'] ?>">
                    <?= htmlspecialchars($s['nome']) ?>
                </option>
            <?php endforeach; ?>
        </optgroup>
    <?php endforeach; ?>
</select>

        <input type="number" name="quantidade[]" min="1" placeholder="Qtd" required>
    </div>

</div>

<button type="button" onclick="addDestino()" class="btn-secondary">
    + Adicionar outro destino
</button>
            <input type="text" name="movimento-observacao" placeholder="Observação (opcional)">

            <input type="hidden" name="item_id" id="movimento-item-id">

            <button type="submit" class="btn-primary">Registrar Movimento</button>
        </form>
    </div>
</div>

<!-- Modal: Foto da Localização -->
<div class="modal-bg" id="modal-foto-loc-bg">
    <div class="modal">
        <span class="close" onclick="closeModal('foto-loc')">×</span>
        <h2>Foto da Localização</h2>
        <img id="img-foto-loc" class="foto-loc-modal" alt="Foto da localização">
    </div>
</div>


<!-- Modal: Gerenciar Locais de Armazenamento -->
<div class="modal-bg" id="modal-locations-bg">
    <div class="modal">
        <span class="close" onclick="closeModal('locations')">&times;</span>
        <h2>Gerenciar Locais de Armazenamento</h2>

        <div class="categoria-tabs">
            <button type="button" class="btn-primary" onclick="mostrarLocationPrincipal()">Local Principal</button>
            <button type="button" class="btn-primary" onclick="mostrarLocationSub()">Subloca</button>
            <button type="button" class="btn-primary" onclick="mostrarLocationLista()">Listagem</button>
        </div>

        <!-- TAB: Local Principal -->
        <div id="box-location-principal" class="categoria-box" style="display:none;">
            <h3>Adicionar Local Principal</h3>
            <form method="post" action="/estoquemh/php/add_location.php">
                <input type="hidden" name="operacao" value="add">
                <input type="text" name="location_nome" placeholder="Nome do local (ex: Armário 1)" required>
                <textarea name="location_descricao" placeholder="Descrição (opcional)" rows="2"></textarea>
                <button type="submit" class="btn-primary">Salvar</button>
            </form>
        </div>

        <!-- TAB: Subloca -->
        <div id="box-location-sub" class="categoria-box" style="display:none;">
            <h3>Adicionar Subloca</h3>
            <form method="post" action="/estoquemh/php/add_location.php">
                <input type="hidden" name="operacao" value="add">
                <select name="location_parent_id" required>
                    <option value="">Selecione o local pai</option>
                    <?php foreach ($parentLocations as $loc): ?>
                        <option value="<?= $loc['id'] ?>">
                            <?= htmlspecialchars($loc['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="text" name="location_nome" placeholder="Nome da subloca (ex: Prateleira 1)" required>
                <textarea name="location_descricao" placeholder="Descrição (opcional)" rows="2"></textarea>

                <button type="submit" class="btn-primary">Salvar</button>
            </form>
        </div>

        <!-- TAB: Listagem -->
        <div id="box-location-lista" class="categoria-box categoria-lista">
            <?php if (!empty($locationsHierarchy)): ?>
                <?php foreach ($locationsHierarchy as $loc): ?>
                    <div class="categoria-card">
                        <div class="categoria-header">
                            <div>
                                <strong><?= htmlspecialchars($loc['nome']) ?></strong>
                                <?php if (!empty($loc['descricao'])): ?>
                                    <small style="display: block; color: #64748b; margin-top: 5px;">
                                        <?= htmlspecialchars($loc['descricao']) ?>
                                    </small>
                                <?php endif; ?>
                            </div>

                            <div class="categoria-acoes">
                                <!-- Editar -->
                                <button class="btn-editar-icone"
                                        data-id="<?= (int)$loc['id'] ?>"
                                        data-nome="<?= htmlspecialchars($loc['nome']) ?>"
                                        data-descricao="<?= htmlspecialchars($loc['descricao'] ?? '') ?>"
                                        onclick="abrirEditarLocation(this)"
                                        title="Editar local">
                                    <img src="/estoquemh/img/editar.png" alt="Editar">
                                </button>

                                <!-- Excluir (admin) -->
                                <?php if ($isAdmin): ?>
                                    <button class="btn-excluir"
                                            onclick="excluirLocation(<?= (int)$loc['id'] ?>)"
                                            title="Excluir local">
                                        <img src="/estoquemh/img/excluir.png" alt="Excluir">
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Sublocas -->
                        <?php if (!empty($loc['sublocals'])): ?>
                            <ul class="subcategoria-lista">
                                <?php foreach ($loc['sublocals'] as $sub): ?>
                                    <li class="subcategoria-item">
                                        <div class="subcategoria-info">
                                            <span class="sub-nome">
                                                <?= htmlspecialchars($sub['nome']) ?>
                                            </span>
                                            <?php if (!empty($sub['descricao'])): ?>
                                                <small style="display: block; color: #64748b;">
                                                    <?= htmlspecialchars($sub['descricao']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>

                                        <div class="subcategoria-acoes">
                                            <!-- Editar -->
                                            <button class="btn-editar-icone"
                                                    data-id="<?= (int)$sub['id'] ?>"
                                                    data-nome="<?= htmlspecialchars($sub['nome']) ?>"
                                                    data-descricao="<?= htmlspecialchars($sub['descricao'] ?? '') ?>"
                                                    onclick="abrirEditarLocation(this)"
                                                    title="Editar subloca">
                                                <img src="/estoquemh/img/editar.png" alt="Editar">
                                            </button>

                                            <!-- Excluir (admin) -->
                                            <?php if ($isAdmin): ?>
                                                <button class="btn-excluir"
                                                        onclick="excluirLocation(<?= (int)$sub['id'] ?>)"
                                                        title="Excluir subloca">
                                                    <img src="/estoquemh/img/excluir.png" alt="Excluir">
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #64748b;">Nenhum local cadastrado.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Modal: Filtros -->
<div class="modal-bg" id="modal-filtros-bg">
    <div class="modal modal-filtros">
        <span class="close" onclick="closeModal('filtros')">&times;</span>
        <h2>Filtros</h2>

        <form method="GET" class="modal-form-grid">
            <div class="form-group full-width">
                <label for="filtro-busca">Buscar item</label>
                <input id="filtro-busca" type="text" name="busca" placeholder="Buscar item"
                       value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="filtro-status">Status</label>
                <select id="filtro-status" name="status">
                    <option value="">Status geral</option>
                    <option value="ok" <?= ($_GET['status'] ?? '') == 'ok' ? 'selected' : '' ?>>Normal</option>
                    <option value="baixo" <?= ($_GET['status'] ?? '') == 'baixo' ? 'selected' : '' ?>>Estoque baixo</option>
                    <option value="zero" <?= ($_GET['status'] ?? '') == 'zero' ? 'selected' : '' ?>>Zerado</option>
                </select>
            </div>

            <div class="form-group">
                <label for="filtro-categoria-pai">Categoria pai</label>
                <select id="filtro-categoria-pai" name="categoria_pai">
                    <option value="">Todas as Categorias Pai</option>
                    <?php 
                    mysqli_data_seek($result_cats, 0);
                    while($cat = mysqli_fetch_assoc($result_cats)): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($_GET['categoria_pai'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nome']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="filtro-subcategoria">Subcategoria</label>
                <select id="filtro-subcategoria" name="subcategoria">
                    <option value="">Todas as Subcategorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <?php foreach ($cat['subcategorias'] as $subcat): ?>
                            <option value="<?= $subcat['id'] ?>" <?= ($_GET['subcategoria'] ?? '') == $subcat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nome']) ?> > <?= htmlspecialchars($subcat['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="filtro-localizacao">Localização</label>
                <select id="filtro-localizacao" name="localizacao_pai">
                    <option value="">Todas as Localizações</option>
                    <?php foreach ($parentLocations as $loc): ?>
                        <option value="<?= htmlspecialchars($loc['nome']) ?>" <?= ($_GET['localizacao_pai'] ?? '') == $loc['nome'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($loc['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="filtro-limite">Itens por Página</label>
                <select id="filtro-limite" name="limite">
                    <option value="10" <?= (($_GET['limite'] ?? '10') == '10') ? 'selected' : '' ?>>10 por Página</option>
                    <option value="15" <?= (($_GET['limite'] ?? '') == '15') ? 'selected' : '' ?>>15 por Página</option>
                    <option value="25" <?= (($_GET['limite'] ?? '') == '25') ? 'selected' : '' ?>>25 por Página</option>
                    <option value="50" <?= (($_GET['limite'] ?? '') == '50') ? 'selected' : '' ?>>50 por Página</option>
                    <option value="100" <?= (($_GET['limite'] ?? '') == '100') ? 'selected' : '' ?>>100 por Página</option>
                </select>
            </div>

            <div class="modal-buttons">
                <button class="btn-cancel" type="button" onclick="closeModal('filtros')">Cancelar</button>
                <button class="btn-primary" type="submit">Aplicar filtros</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Editar Local -->
<div class="modal-bg" id="modal-editar-location-bg">
    <div class="modal">
        <span class="close" onclick="closeModal('editar-location')">×</span>
        <h2>Editar Local</h2>

        <form method="post" action="/estoquemh/php/add_location.php">
            <input type="hidden" name="operacao" value="edit">
            <input type="hidden" name="location_id" id="edit-loc-id">
            <input type="text" name="location_nome" id="edit-loc-nome" required>
            <textarea name="location_descricao" id="edit-loc-descricao" placeholder="Descrição (opcional)" rows="2"></textarea>

            <button type="submit" class="btn-primary">Salvar</button>
        </form>
    </div>
</div>

<!-- Modal: Categorias -->

<div class="modal-bg" id="modal-categoria-bg">
    <div class="modal">
        <span class="close" onclick="closeModal('categoria')">×</span>
        <h2>Categorias</h2>

        <div class="categoria-tabs">
            <button type="button" class="btn-primary" onclick="mostrarPrincipal()">Categoria Principal</button>
            <button type="button" class="btn-primary" onclick="mostrarSub()">Subcategorias</button>
            <button type="button" class="btn-primary" onclick="mostrarLista()">Listagem</button>
        </div>

        <!-- TAB: Categoria Principal -->
        <div id="box-principal" class="categoria-box" style="display:none;">
            <h3>Adicionar Categoria Principal</h3>
            <form method="post" action="/estoquemh/php/addcategoria.php">
                <input type="text" name="categoria-nome" required>
                <input type="hidden" name="parent_id" value="">
                <button type="submit" class="btn-primary">Salvar</button>
            </form>
        </div>

        <!-- TAB: Subcategoria -->
        <div id="box-sub" class="categoria-box" style="display:none;">
            <h3>Adicionar Subcategoria</h3>
            <form method="post" action="../php/addcategoria.php">
                <select name="parent_id" required>
                    <option value="">Selecione a categoria pai</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>">
                            <?= htmlspecialchars($cat['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="text" name="categoria-nome" placeholder="Nome da subcategoria" required>
                <input type="number" name="quantidade_minima" placeholder="Qtd Mínima de estoque" min="0" value="0">

                <button type="submit" class="btn-primary">Salvar</button>
            </form>
        </div>

        <!-- TAB: Listagem -->
        <div id="box-lista" class="categoria-box categoria-lista">
            <?php foreach ($categorias as $cat): ?>
                <div class="categoria-card">
                    <div class="categoria-header">
                        <strong><?= htmlspecialchars($cat['nome']) ?></strong>

                        <div class="categoria-acoes">
                            <!-- Editar -->
                            <button class="btn-editar-icone"
                                    data-id="<?= (int)$cat['id'] ?>"
                                    data-nome="<?= htmlspecialchars($cat['nome']) ?>"
                                    onclick="abrirEditarCategoria(this)"
                                    title="Editar categoria">
                                <img src="/estoquemh/img/editar.png" alt="Editar">
                            </button>

                            <!-- Excluir (admin) -->
                            <?php if ($isAdmin): ?>
                                <button class="btn-excluir"
                                        onclick="excluirCategoria(<?= (int)$cat['id'] ?>)"
                                        title="Excluir categoria">
                                    <img src="/estoquemh/img/excluir.png" alt="Excluir">
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Subcategorias -->
                    <?php if (!empty($cat['subcategorias'])): ?>
                        <ul class="subcategoria-lista">
                            <?php foreach ($cat['subcategorias'] as $sub): ?>
                                <li class="subcategoria-item">
                                    <div class="subcategoria-info">
                                        <span class="sub-nome">
                                            <?= htmlspecialchars($sub['nome']) ?>
                                        </span>
                                        <span class="sub-min">
                                            mín: <?= (int)($sub['quantidade_minima'] ?? 0) ?>
                                        </span>
                                    </div>

                                    <div class="subcategoria-acoes">
                                        <!-- Editar -->
                                        <button class="btn-editar-icone"
                                                data-id="<?= (int)$sub['id'] ?>"
                                                data-nome="<?= htmlspecialchars($sub['nome']) ?>"
                                                data-qtd-min="<?= (int)($sub['quantidade_minima'] ?? 0) ?>"
                                                onclick="abrirEditarCategoria(this)"
                                                title="Editar subcategoria">
                                            <img src="/estoquemh/img/editar.png" alt="Editar">
                                        </button>

                                        <!-- Excluir (admin) -->
                                        <?php if ($isAdmin): ?>
                                            <button class="btn-excluir"
                                                    onclick="excluirCategoria(<?= (int)$sub['id'] ?>)"
                                                    title="Excluir subcategoria">
                                                <img src="/estoquemh/img/excluir.png" alt="Excluir">
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<!-- Modal: Editar Categoria -->
<div class="modal-bg" id="modal-editar-categoria-bg">
    <div class="modal">
        <span class="close" onclick="closeModal('editar-categoria')">×</span>
        <h2>Editar Categoria</h2>

        <form method="post" action="/estoquemh/php/editarcategoria.php">
            <input type="hidden" name="id" id="edit-cat-id">
            <input type="text" name="nome" id="edit-cat-nome" required>

            <input type="number" name="quantidade_minima" id="edit-cat-qtd" min="0"
                   placeholder="Qtd mínima" style="display:none;">

            <button type="submit" class="btn-primary">Salvar</button>
        </form>
    </div>
</div>

<!-- JavaScript -->
<script src="/estoquemh/js/modal.js?v=20260310"></script>
<script src="/estoquemh/js/responsive.js"></script>
<script src="/estoquemh/js/cascading_select.js"></script>
<script src="/estoquemh/js/camera-upload.js"></script>

<?php
// Fechar conexão com banco para otimização
if (isset($cone)) {
    mysqli_close($cone);
}
?>
<script>
// ===== Funções para Cascading Select de Locais =====

function abrirEditarLocation(button) {
    const id = button.getAttribute('data-id');
    const nome = button.getAttribute('data-nome');
    const descricao = button.getAttribute('data-descricao');
    
    console.log('Abrindo editar localização - ID:', id, 'Nome:', nome);
    
    document.getElementById('edit-loc-id').value = id;
    document.getElementById('edit-loc-nome').value = nome;
    document.getElementById('edit-loc-descricao').value = descricao;
    
    console.log('Abrindo modal de editar-location...');
    abrirModal('editar-location');
}

function excluirLocation(locationId) {
    if (confirm('Tem certeza que deseja excluir este local? Os sublocais também serão deletados.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../php/add_location.php';
        
        form.innerHTML = `
            <input type="hidden" name="operacao" value="delete">
            <input type="hidden" name="location_id" value="${locationId}">
        `;
        
        document.body.appendChild(form);
        form.submit();
    }
}

function mostrarLocationPrincipal() {
    document.getElementById('box-location-principal').style.display = 'block';
    document.getElementById('box-location-sub').style.display = 'none';
    document.getElementById('box-location-lista').style.display = 'none';
}

function mostrarLocationSub() {
    document.getElementById('box-location-principal').style.display = 'none';
    document.getElementById('box-location-sub').style.display = 'block';
    document.getElementById('box-location-lista').style.display = 'none';
}

function mostrarLocationLista() {
    document.getElementById('box-location-principal').style.display = 'none';
    document.getElementById('box-location-sub').style.display = 'none';
    document.getElementById('box-location-lista').style.display = 'block';
}

function abrirModalFiltros() {
    if (typeof exibirModal === 'function') {
        exibirModal('modal-filtros-bg');
        return;
    }
    const modal = document.getElementById('modal-filtros-bg');
    if (modal) {
        modal.style.display = 'flex';
    }
}
// ===== Funções de Adição de Destino (Existentes) =====

function addDestino() {
    const container = document.getElementById('destinos-container');

    const bloco = document.createElement('div');
    bloco.className = 'destino-bloco';

    bloco.innerHTML = `
        <select name="setor_id[]" required>
            <option value="">Selecione o destino</option>
            <?php foreach ($setoresPorAndar as $andar => $lista): ?>
                <optgroup label="<?= htmlspecialchars($andar) ?>">
                    <?php foreach ($lista as $s): ?>
                        <option value="<?= $s['id'] ?>">
                            <?= htmlspecialchars($s['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>

        <input type="number" name="quantidade[]" min="1" placeholder="Qtd" required>
    `;

    container.appendChild(bloco);
}

// ===== Inicializar Cascading Select após abertura de modal =====
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar camera upload
    if (typeof initCameraUpload === 'function') {
        initCameraUpload();
        console.log('✅ Camera Upload inicializado');
    }
    
    // Observer para quando modal é aberto
    const originalAbrirModal = window.abrirModal;
    window.abrirModal = function(modalId) {
        originalAbrirModal(modalId);
        if (modalId === 'item' || modalId === 'editar') {
            setTimeout(initCascadingSelects, 100);
            setTimeout(function() {
                if (typeof initCameraUpload === 'function') {
                    initCameraUpload();
                    console.log('🔄 Camera Upload reinicializado para modal:', modalId);
                }
            }, 150);
        }
    };
});

// ===== Funções de Navegação entre Abas =====
function mostrarAba(abaId) {
    // Esconder todas as abas
    const abas = document.querySelectorAll('.aba-content');
    abas.forEach(aba => {
        aba.classList.remove('active');
        aba.style.display = 'none';
    });

    // Remover classe active de todos os botões
    const botoes = document.querySelectorAll('.tab-btn');
    botoes.forEach(btn => {
        btn.classList.remove('active');
    });

    // Mostrar aba selecionada
    const abaAtiva = document.getElementById('aba-' + abaId);
    if (abaAtiva) {
        abaAtiva.classList.add('active');
        abaAtiva.style.display = 'block';
    }

    // Ativar botão correspondente
    event.target.classList.add('active');
}




// ===== Função genérica para Exibir Foto da Localização (itens ou itens_em_uso) =====
function abrirFotoLocalizacao(itemId) {
    // Opcionalmente verificamos se o endpoint responde ok antes de abrir o modal
    fetch(`/estoquemh/php/exibir_foto.php?id=${itemId}`)
        .then(response => {
            if (response.ok) {
                const img = document.getElementById('img-foto-loc');
                if (img) {
                    img.src = `/estoquemh/php/exibir_foto.php?id=${itemId}&t=${new Date().getTime()}`;
                }
                // abrir o modal com o id correto
                abrirModal('foto-loc');
            } else {
                alert('Foto não encontrada!');
            }
        })
        .catch(error => {
            console.error('Erro ao carregar foto:', error);
            alert('Erro ao carregar a foto!');
        });
}

// ===== Cards de resumo interativos =====
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.kpi-card');
    const isMobile = window.matchMedia('(max-width: 768px)').matches;
    const hasStatusFilter = new URLSearchParams(window.location.search).has('status');

    if (isMobile) {
        const shouldScrollToTable = sessionStorage.getItem('scrollToEstoqueTable') === '1' || hasStatusFilter;
        if (shouldScrollToTable) {
            const tableSection = document.getElementById('estoque-tabela');
            if (tableSection) {
                setTimeout(function() {
                    tableSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 120);
            }
            sessionStorage.removeItem('scrollToEstoqueTable');
        }
    }

    cards.forEach(function(card) {
        card.addEventListener('click', function() {
            handleKpiCardClick(card.dataset.cardAction);
        });

        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                handleKpiCardClick(card.dataset.cardAction);
            }
        });
    });
});

function handleKpiCardClick(action) {
    if (action === 'categorias') {
        abrirModal('categoria');
        if (typeof mostrarLista === 'function') {
            mostrarLista();
        }
        return;
    }

    const url = new URL(window.location.href);
    url.searchParams.delete('pagina');

    if (action === 'total-itens') {
        url.searchParams.delete('status');
    } else if (action === 'estoque-baixo') {
        url.searchParams.set('status', 'baixo');
    } else if (action === 'zerados') {
        url.searchParams.set('status', 'zero');
    }

    if (window.matchMedia('(max-width: 768px)').matches) {
        sessionStorage.setItem('scrollToEstoqueTable', '1');
    }

    window.location.href = url.toString();
}


</script>

<button type="button" class="fab-add-item" onclick="abrirModal('item')" aria-label="Adicionar item" title="Adicionar item">
    +
</button>

</body>
</html>
