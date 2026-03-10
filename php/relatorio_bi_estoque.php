<?php
/**
 * RELATÓRIO DE ESTOQUE PARA BI (Power BI / Tableau)
 * Engenharia de Dados Sênior
 * Autor: Sistema de Estoque
 * Data: 2026-01-15
 * 
 * Descrição:
 * Gera um CSV denormalizado e otimizado para ferramentas de BI
 * com métricas calculadas e hierarquias completas.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
ensureLoggedInUser();
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/cone.php';

$tipo_relatorio = $_GET['tipo'] ?? 'estoque_atual';
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

// ================================================
// QUERY 1: VISÃO DE ESTOQUE ATUAL (Denormalizado)
// ================================================
if ($tipo_relatorio === 'estoque_atual') {
    $sql = "
    SELECT
        i.id AS ItemID,
        i.nome AS ItemNome,
        
        -- Hierarquia de Categoria
        COALESCE(cat_pai.nome, 'Não Informado') AS CategoriaPai,
        COALESCE(cat_filho.nome, COALESCE(cat_pai.nome, 'Não Informado')) AS CategoriaFilho,
        CONCAT_WS(' > ', 
            COALESCE(cat_pai.nome, 'Não Informado'),
            COALESCE(cat_filho.nome, '')
        ) AS Categoria_Completa,
        
        -- Hierarquia de Localização
        COALESCE(loc_pai.nome, 'Não Informado') AS LocalPai,
        COALESCE(loc_filho.nome, COALESCE(loc_pai.nome, 'Não Informado')) AS LocalFilho,
        CONCAT_WS(' > ', 
            COALESCE(loc_pai.nome, 'Não Informado'),
            COALESCE(loc_filho.nome, '')
        ) AS Localizacao_Completa,
        
        -- Dados de Quantidade
        COALESCE(i.quantidade, 0) AS Quantidade_Atual,
        COALESCE(cat_filho.quantidade_minima, cat_pai.quantidade_minima, 1) AS Estoque_Minimo,
        
        -- Status Sinalizador (KPI)
        CASE 
            WHEN COALESCE(i.quantidade, 0) = 0 THEN 'Sem Estoque'
            WHEN COALESCE(i.quantidade, 0) <= COALESCE(cat_filho.quantidade_minima, cat_pai.quantidade_minima, 1) THEN 'Abaixo do Mínimo'
            ELSE 'Ideal'
        END AS Status_Sinalizador,
        
        -- Percentual de Estoque (Métrica de Criticidade)
        ROUND(
            (COALESCE(i.quantidade, 0) / COALESCE(cat_filho.quantidade_minima, cat_pai.quantidade_minima, 1)) * 100,
            2
        ) AS Percentual_Estoque_Percentual,
        
        -- Dias desde última movimentação
        COALESCE(
            DATEDIFF(NOW(), (
                SELECT MAX(data_hora) 
                FROM logs 
                WHERE logs.acao LIKE CONCAT('%', i.id, '%')
                LIMIT 1
            )),
            -1
        ) AS Dias_Desde_Ultima_Movimentacao,
        
        -- Dados de Auditoria
        DATE_FORMAT(i.criado_em, '%d/%m/%Y %H:%i') AS Data_Criacao,
        COALESCE(i.observacao, 'Sem observação') AS Observacao,
        
        -- Flag de Estoque Crítico (para filtros em BI)
        CASE 
            WHEN COALESCE(i.quantidade, 0) = 0 THEN 1
            WHEN COALESCE(i.quantidade, 0) <= COALESCE(cat_filho.quantidade_minima, cat_pai.quantidade_minima, 1) THEN 1
            ELSE 0
        END AS Eh_Critico,
        
        -- Período de Relatório
        CURDATE() AS Data_Relatorio,
        'ESTOQUE_ATUAL' AS Tipo_Relatorio
    
    FROM itens i
    
    -- JOINs de Categoria (Hierarquia)
    LEFT JOIN categorias cat_main ON i.categoria_id = cat_main.id
    LEFT JOIN categorias cat_pai ON 
        (cat_main.parent_id IS NULL AND cat_main.id = cat_pai.id)
        OR (cat_main.parent_id IS NOT NULL AND cat_main.parent_id = cat_pai.id)
    LEFT JOIN categorias cat_filho ON 
        (cat_main.parent_id IS NOT NULL AND cat_main.id = cat_filho.id)
    
    -- JOINs de Localização (Hierarquia)
    LEFT JOIN locations loc_pai ON 
        CAST(i.localizacao AS UNSIGNED) = loc_pai.id 
        AND loc_pai.parent_id IS NULL
    LEFT JOIN locations loc_filho ON 
        CAST(i.localizacao AS UNSIGNED) = loc_filho.id 
        AND loc_filho.parent_id IS NOT NULL
    
    ORDER BY 
        Categoria_Completa ASC,
        Status_Sinalizador DESC,
        i.nome ASC
    ";
}

// ================================================
// QUERY 2: VISÃO DE MOVIMENTAÇÃO (COM SETORES)
// ================================================
elseif ($tipo_relatorio === 'movimentacao') {

    $sql = "
    SELECT
        m.id AS MovimentacaoID,

        -- Item
        i.id AS ItemID,
        i.nome AS ItemNome,

        -- Usuário
        u.id AS UsuarioID,
        COALESCE(u.nome, 'Usuário Desconhecido') AS Usuario_Nome,

        -- Tipo e Quantidade
        m.tipo AS Tipo_Movimentacao,
        md.quantidade AS Quantidade_Movida,

        -- Setor de Destino
        s.id AS SetorID,
        COALESCE(s.nome, 'Setor Não Informado') AS Setor_Destino,

        -- Observações
        COALESCE(m.observacao, 'Sem observação') AS Observacao,

        -- Datas
        DATE_FORMAT(m.data_movimentacao, '%d/%m/%Y') AS Data_Movimentacao,
        DATE_FORMAT(m.data_movimentacao, '%H:%i:%s') AS Hora_Movimentacao,
        DATE_FORMAT(m.data_movimentacao, '%d/%m/%Y %H:%i') AS Data_Hora_Completa,

        -- Metadados BI
        'MOVIMENTACAO_HISTORICO' AS Tipo_Relatorio,
        CURDATE() AS Data_Relatorio

    FROM `movimentacoes` m

    INNER JOIN `itens` i 
        ON i.id = m.item_id

    LEFT JOIN `usuarios` u 
        ON u.id = m.usuario_id

    INNER JOIN `movimentacoes_destinos` md 
        ON md.movimentacao_id = m.id

    LEFT JOIN `setores` s 
        ON s.id = md.setor_id

    WHERE 
        m.data_movimentacao BETWEEN CONCAT(?, ' 00:00:00')
                                AND CONCAT(?, ' 23:59:59')

    ORDER BY 
        m.data_movimentacao DESC,
        i.nome ASC
    ";

    $stmt = $cone->prepare($sql);
    $stmt->bind_param("ss", $data_inicio, $data_fim);
    $stmt->execute();
    $result = $stmt->get_result();
}


// ================================================
// GERAÇÃO DO ARQUIVO CSV
// ================================================

// Nome do arquivo
$nome_arquivo = 'relatorio_bi_' . $tipo_relatorio . '_' . date('Y-m-d_H-i-s') . '.csv';

// Headers para download
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename=' . $nome_arquivo);
header('Pragma: no-cache');
header('Expires: 0');

// BOM UTF-8 (importante para Excel)
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Configurar delimitador para Ponto-e-Vírgula (padrão para BI)
$primeiro_linha = true;

while ($row = $result->fetch_assoc()) {
    // Se for a primeira linha, escrever headers
    if ($primeiro_linha) {
        fputcsv($output, array_keys($row), ';');
        $primeiro_linha = false;
    }
    
    // Tratar NULOs e valores vazios
    $row_tratado = array_map(function($value) {
        if ($value === null || $value === '') {
            return 'Não Informado';
        }
        return $value;
    }, $row);
    
    fputcsv($output, $row_tratado, ';');
}

fclose($output);
exit;
