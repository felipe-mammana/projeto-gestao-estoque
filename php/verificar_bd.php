<?php
/**
 * VERIFICADOR DE BANCO DE DADOS
 * Mostra fotos no banco e se consegue servir elas
 * Acessa via: http://servidor/estoquemh/php/verificar_bd.php
 */

ob_start();
header('Content-Type: text/html; charset=utf-8');

// ===== CONEXÃO COM BD =====
require_once(dirname(dirname(__FILE__)) . '/config/email.php');

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<meta charset='utf-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";
echo "<title>Verificador de BD - Fotos</title>";
echo "<style>";
echo "body { font-family: Arial; max-width: 1000px; margin: 20px auto; }";
echo "table { width: 100%; border-collapse: collapse; margin: 20px 0; }";
echo "th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }";
echo "th { background: #4CAF50; color: white; }";
echo "tr:nth-child(even) { background: #f9f9f9; }";
echo ".ok { color: green; }";
echo ".erro { color: red; }";
echo ".aviso { color: orange; }";
echo ".container { max-width: 1200px; margin: 0 auto; }";
echo ".info { background: #e3f2fd; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #2196F3; }";
echo ".resumo { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }";
echo ".card { background: white; padding: 15px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }";
echo ".numero { font-size: 2em; font-weight: bold; }";
echo ".green { color: green; }";
echo ".red { color: red; }";
echo "img { max-width: 100px; max-height: 100px; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1>🔍 Verificador de Banco de Dados - Fotos</h1>";

// ===== CONECTAR =====
try {
    $connectStr = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($connectStr, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='info'>";
    echo "✅ <strong>Conectado ao BD:</strong> " . DB_NAME . "<br>";
    echo "<small>Host: " . DB_HOST . "</small>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='info' style='background: #ffebee; border-left-color: #f44336;'>";
    echo "❌ <strong>Erro ao conectar:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    exit;
}

// ===== RESUMO =====
echo "<h2>📊 Resumo Geral</h2>";

// Total de itens
$total_stmt = $pdo->query("SELECT COUNT(*) as total FROM itens");
$total_itens = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Com foto
$com_foto_stmt = $pdo->query("SELECT COUNT(*) as total FROM itens WHERE foto_loc IS NOT NULL AND foto_loc != ''");
$com_foto = $com_foto_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Sem foto
$sem_foto = $total_itens - $com_foto;

echo "<div class='resumo'>";
echo "<div class='card'>";
echo "<div class='numero green'>" . $total_itens . "</div>";
echo "<div><strong>Total de Itens</strong></div>";
echo "</div>";
echo "<div class='card'>";
echo "<div class='numero green'>" . $com_foto . "</div>";
echo "<div><strong>Com Foto</strong></div>";
echo "</div>";
echo "<div class='card'>";
echo "<div class='numero'>" . $sem_foto . "</div>";
echo "<div><strong>Sem Foto</strong></div>";
echo "</div>";
echo "</div>";

// ===== ITENS COM FOTO =====
echo "<h2>📸 Itens com Foto</h2>";

$query = "SELECT id, nome, foto_loc, data_entrada FROM itens WHERE foto_loc IS NOT NULL AND foto_loc != '' ORDER BY data_entrada DESC LIMIT 20";
$stmt = $pdo->query($query);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($itens) === 0) {
    echo "<div class='info' style='background: #fff3e0; border-left-color: #ff9800;'>";
    echo "⚠️ Nenhum item com foto encontrado no banco de dados";
    echo "</div>";
} else {
    echo "<table>";
    echo "<tr>";
    echo "<th>ID</th>";
    echo "<th>Nome</th>";
    echo "<th>Foto (BD)</th>";
    echo "<th>Arquivo Existe?</th>";
    echo "<th>Preview</th>";
    echo "<th>Tamanho</th>";
    echo "</tr>";
    
    $upload_dir = dirname(dirname(__FILE__)) . '/uploads/imagens';
    
    foreach ($itens as $item) {
        $foto_loc = $item['foto_loc'];
        $arquivo_path = dirname(dirname(__FILE__)) . $foto_loc;
        $existe = file_exists($arquivo_path);
        $tamanho = $existe ? filesize($arquivo_path) : 0;
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($item['id']) . "</td>";
        echo "<td>" . htmlspecialchars($item['nome']) . "</td>";
        echo "<td><code>" . htmlspecialchars($foto_loc) . "</code></td>";
        
        // Status arquivo
        if ($existe) {
            echo "<td class='ok'>✅ Existe</td>";
        } else {
            echo "<td class='erro'>❌ Não existe</td>";
        }
        
        // Preview
        if ($existe) {
            echo "<td><img src='" . htmlspecialchars($foto_loc) . "' alt='Foto'></td>";
        } else {
            echo "<td>-</td>";
        }
        
        // Tamanho
        if ($tamanho > 0) {
            echo "<td>" . round($tamanho / 1024, 2) . " KB</td>";
        } else {
            echo "<td>-</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
}

// ===== VERIFICAR DIRETÓRIO =====
echo "<h2>📁 Verificação do Diretório</h2>";

$upload_dir = dirname(dirname(__FILE__)) . '/uploads/imagens';

echo "<table>";
echo "<tr><th>Propriedade</th><th>Valor</th><th>Status</th></tr>";

// Existe
$existe = is_dir($upload_dir);
echo "<tr>";
echo "<td>Diretório existe?</td>";
echo "<td>" . ($existe ? "Sim - $upload_dir" : "Não") . "</td>";
echo "<td class='" . ($existe ? 'ok' : 'erro') . "'>" . ($existe ? '✅' : '❌') . "</td>";
echo "</tr>";

if ($existe) {
    // Escrevível
    $escrevivel = is_writable($upload_dir);
    echo "<tr>";
    echo "<td>É escrevível?</td>";
    echo "<td>" . ($escrevivel ? "Sim" : "Não") . "</td>";
    echo "<td class='" . ($escrevivel ? 'ok' : 'erro') . "'>" . ($escrevivel ? '✅' : '❌') . "</td>";
    echo "</tr>";
    
    // Permissões
    $perms = substr(sprintf('%o', fileperms($upload_dir)), -4);
    echo "<tr>";
    echo "<td>Permissões</td>";
    echo "<td>" . $perms . "</td>";
    echo "<td class='" . (in_array($perms, ['0755', '0777']) ? 'ok' : 'aviso') . "'>";
    echo ($perms === '0755' || $perms === '0777' ? '✅' : '⚠️ Verificar');
    echo "</td>";
    echo "</tr>";
    
    // Arquivo de teste
    $test_file = $upload_dir . '/test_' . time() . '.txt';
    $test_ok = @file_put_contents($test_file, 'test');
    if ($test_ok) {
        @unlink($test_file);
        echo "<tr>";
        echo "<td>Teste de escrita</td>";
        echo "<td>Sucesso</td>";
        echo "<td class='ok'>✅</td>";
        echo "</tr>";
    } else {
        echo "<tr>";
        echo "<td>Teste de escrita</td>";
        echo "<td>Falhou</td>";
        echo "<td class='erro'>❌</td>";
        echo "</tr>";
    }
    
    // Quantidade de arquivos
    $files = @glob($upload_dir . '/*.*');
    $qtd = count($files);
    echo "<tr>";
    echo "<td>Quantidade de arquivos</td>";
    echo "<td>" . $qtd . " arquivos</td>";
    echo "<td class='ok'>ℹ️</td>";
    echo "</tr>";
    
    // Espaço em disco
    $disk_free = disk_free_space($upload_dir);
    if ($disk_free !== false) {
        $disk_free_mb = round($disk_free / 1024 / 1024, 2);
        echo "<tr>";
        echo "<td>Espaço livre em disco</td>";
        echo "<td>" . $disk_free_mb . " MB</td>";
        echo "<td class='" . ($disk_free_mb > 100 ? 'ok' : 'aviso') . "'>";
        echo ($disk_free_mb > 100 ? '✅' : '⚠️');
        echo "</td>";
        echo "</tr>";
    }
}

echo "</table>";

// ===== VERIFICAR TABELA "em_uso" =====
echo "<h2>📦 Tabela 'em_uso'</h2>";

// Total em_uso
$total_emuso_stmt = $pdo->query("SELECT COUNT(*) as total FROM em_uso");
$total_emuso = $total_emuso_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Com foto
$com_foto_emuso_stmt = $pdo->query("SELECT COUNT(*) as total FROM em_uso WHERE foto_loc IS NOT NULL AND foto_loc != ''");
$com_foto_emuso = $com_foto_emuso_stmt->fetch(PDO::FETCH_ASSOC)['total'];

echo "<div class='resumo'>";
echo "<div class='card'>";
echo "<div class='numero'>" . $total_emuso . "</div>";
echo "<div><strong>Total em em_uso</strong></div>";
echo "</div>";
echo "<div class='card'>";
echo "<div class='numero green'>" . $com_foto_emuso . "</div>";
echo "<div><strong>Com Foto</strong></div>";
echo "</div>";
echo "</div>";

if ($total_emuso > 0 && $com_foto_emuso > 0) {
    $query_emuso = "SELECT id, item_id, foto_loc FROM em_uso WHERE foto_loc IS NOT NULL AND foto_loc != '' LIMIT 10";
    $stmt_emuso = $pdo->query($query_emuso);
    $itens_emuso = $stmt_emuso->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr>";
    echo "<th>ID</th>";
    echo "<th>Item ID</th>";
    echo "<th>Foto (Bd)</th>";
    echo "<th>Arquivo Existe?</th>";
    echo "</tr>";
    
    foreach ($itens_emuso as $item) {
        $arquivo_path = dirname(dirname(__FILE__)) . $item['foto_loc'];
        $existe = file_exists($arquivo_path);
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($item['id']) . "</td>";
        echo "<td>" . htmlspecialchars($item['item_id']) . "</td>";
        echo "<td><code>" . htmlspecialchars($item['foto_loc']) . "</code></td>";
        echo "<td class='" . ($existe ? 'ok' : 'erro') . "'>";
        echo ($existe ? '✅ Existe' : '❌ Não existe');
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

echo "</div>";
echo "</body></html>";
?>
