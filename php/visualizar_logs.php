<?php
/**
 * VISUALIZADOR DE ERROS DE UPLOAD
 * Mostra os últimos erros do arquivo de log em tempo real
 * Acessa via: http://servidor/estoquemh/php/visualizar_logs.php
 */

ob_start();
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<meta charset='utf-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";
echo "<meta http-equiv='refresh' content='5'>";
echo "<title>Visualizador de Logs - Upload</title>";
echo "<style>";
echo "body { font-family: 'Courier New', monospace; margin: 0; padding: 20px; background: #1e1e1e; color: #d4d4d4; }";
echo ".container { max-width: 1000px; margin: auto; }";
echo "h1 { color: #4ec9b0; }";
echo ".info { background: #2d2d30; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #0e7c86; }";
echo ".erro { background: #3d2626; color: #f48771; border-left-color: #dc3545; }";
echo ".aviso { background: #3d3000; color: #dcdcaa; border-left-color: #ffc107; }";
echo ".ok { background: #0d3d2d; color: #6a9955; border-left-color: #28a745; }";
echo ".log-section { background: #252526; padding: 15px; margin: 15px 0; border-radius: 5px; }";
echo ".timestamp { color: #858585; font-size: 0.9em; }";
echo ".controls { margin: 20px 0; }";
echo "button { padding: 10px 15px; margin: 5px; background: #007acc; color: white; border: none; cursor: pointer; border-radius: 3px; }";
echo "button:hover { background: #005a9e; }";
echo ".limpar { background: #dc3545; }";
echo ".limpar:hover { background: #c82333; }";
echo ".refresh-animation { animation: pulse 1s infinite; }";
echo "@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }";
echo "pre { overflow-x: auto; max-height: 500px; overflow-y: auto; }";
echo ".vazio { color: #858585; font-style: italic; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1>📋 Visualizador de Logs - Upload de Fotos</h1>";
echo "<p class='timestamp'>Última atualização: " . date('Y-m-d H:i:s') . " <span class='refresh-animation'>↻</span></p>";

// ===== INFO SOBRE LOGS =====
$error_log_path = ini_get('error_log');
if (!$error_log_path || $error_log_path === 'syslog') {
    $error_log_path = '/var/log/apache2/error.log';
}

echo "<div class='info'>";
echo "<strong>📍 Localização do error_log:</strong><br>";
echo htmlspecialchars($error_log_path) . "<br>";
echo "<small><em>Se o caminho acima não existir, editar php.ini e definir: error_log = /caminho/correto</em></small>";
echo "</div>";

// ===== VERIFICAR SE ARQUIVO EXISTE =====
if (!file_exists($error_log_path)) {
    echo "<div class='info aviso'>";
    echo "<strong>⚠️ Arquivo de log não encontrado!</strong><br>";
    echo "Caminho procurado: " . htmlspecialchars($error_log_path) . "<br>";
    echo "Possíveis soluções: <br>";
    echo "1. Verifique o caminho em php.ini<br>";
    echo "2. Crie o arquivo manualmente: touch " . $error_log_path . "<br>";
    echo "3. Verifique permissões: chmod 666 " . $error_log_path . "<br>";
    echo "</div>";
} else {
    // ===== LEITURA DO LOG =====
    echo "<div class='controls'>";
    echo "<button onclick=\"location.reload()\">🔄 Atualizar Agora</button>";
    echo "<button class='limpar' onclick=\"if(confirm('Tem certeza?')) location.href='?limpar=1'\">🗑️ Limpar Logs</button>";
    echo "</div>";

    // Limparclaridad
    if (isset($_GET['limpar'])) {
        file_put_contents($error_log_path, '');
        echo "<div class='info ok'>";
        echo "✅ Logs limpados!<br>";
        echo "<meta http-equiv='refresh' content='2'>";
        echo "</div>";
    }

    // Ler últimas linhas
    $file_size = filesize($error_log_path);
    echo "<div class='info'>";
    echo "<strong>📊 Tamanho do log:</strong> " . ($file_size / 1024) . " KB<br>";
    echo "<strong>🔍 Procurando por:</strong> saveItemImage, ERRO, debug<br>";
    echo "</div>";

    // Ler arquivo
    $lines = file($error_log_path);
    if (count($lines) === 0) {
        echo "<div class='log-section'>";
        echo "<p class='vazio'>📭 Nenhuma entrada no log ainda...</p>";
        echo "<p><em>Tente fazer upload de uma foto para gerar erros</em></p>";
        echo "</div>";
    } else {
        // Filtrar linhas relevantes
        $relevant_lines = [];
        foreach ($lines as $line) {
            if (stripos($line, 'saveImage') !== false ||
                stripos($line, 'saveItemImage') !== false ||
                stripos($line, 'ERRO') !== false ||
                stripos($line, 'ERROR') !== false ||
                stripos($line, 'debug') !== false ||
                stripos($line, 'upload') !== false ||
                stripos($line, 'foto') !== false) {
                $relevant_lines[] = $line;
            }
        }

        if (count($relevant_lines) === 0) {
            echo "<div class='log-section'>";
            echo "<p class='vazio'>📭 Nenhuma entrada relevante encontrada...</p>";
            echo "<p><em>Mostrar últimas " . count($lines) . " linhas do log:</em></p>";
            echo "<pre>";
            echo htmlspecialchars(implode('', array_slice($lines, -50)));
            echo "</pre>";
            echo "</div>";
        } else {
            echo "<div class='log-section'>";
            echo "<h2>✅ Linhas Relevantes Encontradas (" . count($relevant_lines) . ")</h2>";
            echo "<pre>";
            foreach (array_slice($relevant_lines, -100) as $line) {
                $html_line = htmlspecialchars($line);
                
                // Colorizar
                if (stripos($line, 'erro') !== false || stripos($line, 'error') !== false) {
                    $html_line = "<span style='color: #f48771;'>$html_line</span>";
                } elseif (stripos($line, 'sucesso') !== false || stripos($line, 'success') !== false) {
                    $html_line = "<span style='color: #6a9955;'>$html_line</span>";
                } elseif (stripos($line, 'aviso') !== false || stripos($line, 'warning') !== false) {
                    $html_line = "<span style='color: #dcdcaa;'>$html_line</span>";
                }
                
                echo $html_line;
            }
            echo "</pre>";
            echo "</div>";
        }
    }
}

// ===== ORIENTAÇÕES =====
echo "<div class='info aviso'>";
echo "<strong>💡 O que procurar nos logs:</strong><br>";
echo "❌ <strong style='color: #f48771;'>saveItemImage: Erro ao criar diretório</strong> - Permissões problemáticas<br>";
echo "❌ <strong style='color: #f48771;'>saveItemImage: Diretório NÃO é escrevível</strong> - chmod insuficiente<br>";
echo "❌ <strong style='color: #f48771;'>UPLOAD_ERR_INI_SIZE</strong> - upload_max_filesize muito pequeno<br>";
echo "❌ <strong style='color: #f48771;'>Arquivo não autorizado</strong> - MIME type problema<br>";
echo "✅ <strong style='color: #6a9955;'>saveItemImage: Sucesso</strong> - Upload funcionou!<br>";
echo "</div>";

echo "</div>";
echo "</body></html>";
?>
