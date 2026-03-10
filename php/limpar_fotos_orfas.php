<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/image_handler.php';

// Apenas admin pode acessar
$id_user = ensureLoggedInUser();

include $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";

$resultados = [];

// ===== PARTE 1: Limpar referências de fotos que não existem na tabela ITENS =====
$sqlItens = "SELECT id, foto_loc FROM itens WHERE foto_loc IS NOT NULL AND foto_loc != ''";
$resultItens = $cone->query($sqlItens);

$fotos_inexistentes = [];
$fotos_corrigidas = 0;

while ($row = $resultItens->fetch_assoc()) {
    if ($row['foto_loc'] && strpos($row['foto_loc'], '/uploads/imagens/') === 0) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/estoquemh' . $row['foto_loc'];
        
        if (!file_exists($fullPath)) {
            $fotos_inexistentes[] = [
                'id' => $row['id'],
                'caminho_banco' => $row['foto_loc'],
                'arquivo' => basename($row['foto_loc'])
            ];
            
            // Deletar referência do banco
            $stmtDelete = $cone->prepare("UPDATE itens SET foto_loc = NULL WHERE id = ?");
            $stmtDelete->bind_param("i", $row['id']);
            $stmtDelete->execute();
            $stmtDelete->close();
            $fotos_corrigidas++;
        }
    }
}

// ===== PARTE 2: Limpar referências de fotos que não existem na tabela ITENS_EM_USO =====
$sqlEmUso = "SELECT id, foto_loc FROM itens_em_uso WHERE foto_loc IS NOT NULL AND foto_loc != ''";
$resultEmUso = $cone->query($sqlEmUso);

$fotos_em_uso_inexistentes = [];
$fotos_em_uso_corrigidas = 0;

while ($row = $resultEmUso->fetch_assoc()) {
    if ($row['foto_loc'] && strpos($row['foto_loc'], '/uploads/imagens/') === 0) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/estoquemh' . $row['foto_loc'];
        
        if (!file_exists($fullPath)) {
            $fotos_em_uso_inexistentes[] = [
                'id' => $row['id'],
                'caminho_banco' => $row['foto_loc'],
                'arquivo' => basename($row['foto_loc'])
            ];
            
            // Deletar referência do banco
            $stmtDelete = $cone->prepare("UPDATE itens_em_uso SET foto_loc = NULL WHERE id = ?");
            $stmtDelete->bind_param("i", $row['id']);
            $stmtDelete->execute();
            $stmtDelete->close();
            $fotos_em_uso_corrigidas++;
        }
    }
}

// ===== PARTE 3: Listar arquivos órfãos (no disco mas não no banco) =====
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/uploads/imagens/';
$arquivos_orfaos = [];

if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $fullPath = $uploadDir . $file;
        
        // Verificar se existe referência no banco
        $stmtCheck = $cone->prepare(
            "SELECT id FROM itens WHERE foto_loc = ? 
             UNION 
             SELECT id FROM itens_em_uso WHERE foto_loc = ?"
        );
        $relativePath = '/uploads/imagens/' . $file;
        $stmtCheck->bind_param("ss", $relativePath, $relativePath);
        $stmtCheck->execute();
        $checkResult = $stmtCheck->get_result();
        
        if ($checkResult->num_rows === 0) {
            $arquivos_orfaos[] = [
                'arquivo' => $file,
                'tamanho' => filesize($fullPath),
                'data_criacao' => date('Y-m-d H:i:s', filemtime($fullPath))
            ];
        }
        $stmtCheck->close();
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Limpeza de Fotos Órfãs</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 5px;
            max-width: 1000px;
            margin: 0 auto;
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .resultado {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #007bff;
            background-color: #f9f9f9;
        }
        .numero-grande {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .erro {
            color: #dc3545;
        }
        .sucesso {
            color: #28a745;
        }
        .aviso {
            color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Limpeza de Fotos Órfãs do Sistema</h1>
        
        <div class="resultado">
            <h2>Tabela ITENS</h2>
            <p>Fotos com referência no banco mas arquivo não existe no disco:</p>
            <p class="numero-grande"><span class="erro"><?php echo $fotos_corrigidas; ?></span> corrigidas</p>
            <?php if (count($fotos_inexistentes) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID do Item</th>
                            <th>Caminho no Banco</th>
                            <th>Arquivo</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fotos_inexistentes as $foto): ?>
                            <tr>
                                <td><?php echo $foto['id']; ?></td>
                                <td><code><?php echo htmlspecialchars($foto['caminho_banco']); ?></code></td>
                                <td><?php echo htmlspecialchars($foto['arquivo']); ?></td>
                                <td><span class="sucesso">✓ Referência deletada do banco</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="sucesso">✓ Nenhuma foto órfã encontrada.</p>
            <?php endif; ?>
        </div>

        <div class="resultado">
            <h2>Tabela ITENS_EM_USO</h2>
            <p>Fotos com referência no banco mas arquivo não existe no disco:</p>
            <p class="numero-grande"><span class="erro"><?php echo $fotos_em_uso_corrigidas; ?></span> corrigidas</p>
            <?php if (count($fotos_em_uso_inexistentes) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID do Item</th>
                            <th>Caminho no Banco</th>
                            <th>Arquivo</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fotos_em_uso_inexistentes as $foto): ?>
                            <tr>
                                <td><?php echo $foto['id']; ?></td>
                                <td><code><?php echo htmlspecialchars($foto['caminho_banco']); ?></code></td>
                                <td><?php echo htmlspecialchars($foto['arquivo']); ?></td>
                                <td><span class="sucesso">✓ Referência deletada do banco</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="sucesso">✓ Nenhuma foto órfã encontrada.</p>
            <?php endif; ?>
        </div>

        <div class="resultado">
            <h2>Arquivos Órfãos no Disco</h2>
            <p>Arquivos na pasta de uploads mas sem referência no banco:</p>
            <p class="numero-grande"><span class="aviso"><?php echo count($arquivos_orfaos); ?></span> arquivos</p>
            <?php if (count($arquivos_orfaos) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Arquivo</th>
                            <th>Tamanho</th>
                            <th>Data de Criação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($arquivos_orfaos as $arquivo): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($arquivo['arquivo']); ?></td>
                                <td><?php echo number_format($arquivo['tamanho'] / 1024, 2); ?> KB</td>
                                <td><?php echo $arquivo['data_criacao']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="aviso"><strong>Nota:</strong> Estes arquivos podem ser deletados manualmente da pasta se tiverem certeza que não são mais necessários.</p>
            <?php else: ?>
                <p class="sucesso">✓ Nenhum arquivo órfão encontrado.</p>
            <?php endif; ?>
        </div>

        <div class="resultado">
            <h2>Resumo da Limpeza</h2>
            <p><strong>Total de fotos corrigidas (referências removidas do banco):</strong> <span class="numero-grande"><?php echo $fotos_corrigidas + $fotos_em_uso_corrigidas; ?></span></p>
            <p><strong>Arquivos órfãos no disco:</strong> <span class="numero-grande"><?php echo count($arquivos_orfaos); ?></span></p>
            <p style="margin-top: 20px;">
                <a href="/estoquemh/pages/sistema.php" style="padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">← Voltar ao Sistema</a>
            </p>
        </div>
    </div>
</body>
</html>
