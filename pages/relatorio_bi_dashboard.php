<?php
/**
 * DASHBOARD DE RELATÓRIOS PARA BI
 */




require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
ensureLoggedInUser();
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/cone.php';

$isAdmin = ($_SESSION['usuario']['tipo'] ?? '') === 'admin';
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
    <title>Relatórios para BI - Estoque</title>
    <link rel="stylesheet" href="/estoquemh/css/style.css">
    <style>
        .relatorio-container {
            padding: 40px 0;
        }
        
        .relatorio-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .relatorio-card {
            background: var(--glass-card);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 28px;
            transition: all 0.3s ease;
        }
        
        .relatorio-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255,255,255,0.2);
            box-shadow: 0 8px 32px rgba(59, 130, 246, 0.2);
        }
        
        .relatorio-card h2 {
            color: #fff;
            font-size: 1.4em;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .relatorio-card .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75em;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .badge.new { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }
        .badge.popular { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .badge.legacy { background: rgba(139, 92, 246, 0.2); color: #d8b4fe; }
        
        .relatorio-card p {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 18px;
            font-size: 0.95em;
        }
        
        .features {
            background: rgba(15, 23, 42, 0.4);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9em;
        }
        
        .features ul {
            list-style: none;
            padding: 0;
        }
        
        .features li {
            padding: 6px 0;
            color: var(--text-muted);
        }
        
        .features li:before {
            content: "✓ ";
            color: #10b981;
            font-weight: bold;
            margin-right: 8px;
        }
        
        .form-group-inline {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        
        .form-group-inline input,
        .form-group-inline select {
            flex: 1;
            min-width: 140px;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid var(--glass-border);
            color: #fff;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 0.9em;
        }
        
        .form-group-inline input:focus,
        .form-group-inline select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-export {
            flex: 1;
            min-width: 140px;
            padding: 11px 20px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.95em;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-export:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .info-section {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid var(--primary);
            padding: 24px;
            border-radius: 8px;
            margin-top: 40px;
        }
        
        .info-section h3 {
            color: #fff;
            margin-bottom: 15px;
            font-size: 1.2em;
        }
        
        .info-section p {
            color: var(--text-muted);
            margin-bottom: 12px;
            line-height: 1.6;
        }
        
        .info-section ul {
            list-style: none;
            margin-left: 0;
            color: var(--text-muted);
        }
        
        .info-section li {
            padding: 6px 0;
            padding-left: 24px;
            position: relative;
        }
        
        .info-section li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
        }
        
        .code-block {
            background: #0f172a;
            border: 1px solid var(--glass-border);
            color: #10b981;
            padding: 12px 16px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            overflow-x: auto;
            margin: 12px 0;
        }
        
        @media (max-width: 768px) {
            .relatorio-container {
                padding: 24px 0;
            }
            
            .relatorio-cards {
                gap: 16px;
            }
            
            .form-group-inline {
                flex-direction: column;
            }
            
            .form-group-inline input,
            .form-group-inline select {
                min-width: auto;
            }
        }
    </style>
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
                </div>
            </div>
            <nav class="sidebar-menu">
                <a href="/estoquemh/pages/sistema.php">📦 <span>Estoque</span></a>
                <a href="/estoquemh/pages/em-uso.php" class="<?= (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'em-uso.php') !== false) ? 'active' : '' ?>">🚀 <span>Em Uso</span></a>
                <a href="/estoquemh/pages/usuario.php">👥 <span>Usuários</span></a>
                <a href="/estoquemh/pages/relatorio_bi_dashboard.php" class="active">📊 <span>Relatórios BI</span></a>
                <a href="/estoquemh/pages/login.php" style="margin-top: auto; color: #f87171;">🚪 <span>Sair</span></a>
            </nav>
        </div>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 15px;">
                <button class="btn-toggle" onclick="toggleSidebar()">☰</button>
                <h1>Relatórios para BI</h1>
            </div>
        </header>

        <div class="relatorio-container">
            <div class="relatorio-cards">
                
                <!-- Card 1: Estoque Atual -->
                <div class="relatorio-card">
                    <h2><span>📦</span> Estoque Atual</h2>
                    <span class="badge new">NOVO</span>
                    <p>Visão completa do estoque com métricas calculadas e hierarquias denormalizadas para BI.</p>
                    
                    <div class="features">
                        <ul>
                            <li>Hierarquia de Categorias</li>
                            <li>Hierarquia de Localizações</li>
                            <li>Status Sinalizador (3 níveis)</li>
                            <li>Percentual de Estoque (%)</li>
                            <li>Dias desde última movimentação</li>
                            <li>Flag de criticidade</li>
                        </ul>
                    </div>
                    
                    <a href="/estoquemh/php/relatorio_bi_estoque.php?tipo=estoque_atual" class="btn-export" download>
                        <span>⬇️</span> Exportar CSV
                    </a>
                </div>
                
                <!-- Card 2: Movimentação Histórica -->
                <div class="relatorio-card">
                    <h2><span>📈</span> Movimentação</h2>
                    <span class="badge popular">POPULAR</span>
                    <p>Histórico de entradas e saídas com dados de usuário para análise de fluxo.</p>
                    
                    <div class="features">
                        <ul>
                            <li>Período configurável</li>
                            <li>Tipo de Movimentação</li>
                            <li>Usuário responsável</li>
                            <li>Data/hora formatadas</li>
                            <li>Pronto para séries</li>
                            <li>Detalhes completos</li>
                        </ul>
                    </div>
                    
                    <form method="GET" action="/estoquemh/php/relatorio_bi_estoque.php" class="form-group-inline">
                        <input type="hidden" name="tipo" value="movimentacao">
                        <input type="date" name="data_inicio" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                        <input type="date" name="data_fim" value="<?= date('Y-m-d') ?>">
                        <button type="submit" class="btn-export">
                            <span>⬇️</span> Exportar
                        </button>
                    </form>
                </div>
                
                <!-- Card 3: Logs Simples -->
                <div class="relatorio-card">
                    <h2><span>📋</span> Logs Simples</h2>
                    <span class="badge legacy">LEGADO</span>
                    <p>Exportação simples de logs sem transformações. Útil para auditoria direta.</p>
                    
                    <div class="features">
                        <ul>
                            <li>Formato legado mantido</li>
                            <li>Período configurável</li>
                            <li>Filtro por tipo</li>
                            <li>Delimitador ;CSV</li>
                            <li>UTF-8 com BOM</li>
                            <li>Compatível Excel</li>
                        </ul>
                    </div>
                    
                    <form method="GET" action="/estoquemh/php/logs_exportar.php" class="form-group-inline">
                        <select name="tipo">
                            <option value="">Todos os tipos</option>
                            <option value="ENTRADA">Apenas Entradas</option>
                            <option value="SAIDA">Apenas Saídas</option>
                        </select>
                        <input type="date" name="data_inicio" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                        <input type="date" name="data_fim" value="<?= date('Y-m-d') ?>">
                        <button type="submit" class="btn-export">
                            <span>⬇️</span> Exportar
                        </button>
                    </form>
                </div>
                
            </div>
            
            <!-- Footer Info -->
            <div style="text-align: center; color: var(--text-muted); font-size: 0.9em; margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--glass-border);">
                <p>📖 Para mais detalhes técnicos,<br>veja <strong>docs/QUERIES_BI_ESTOQUE.md</strong></p>
            </div>
        </div>
    </div>
</div>

<script src="/estoquemh/js/responsive.js"></script>

</body>
</html>
