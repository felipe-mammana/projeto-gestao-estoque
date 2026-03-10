<?php
/**
 * TESTE SIMPLES DE UPLOAD
 * Arquivo para diagnosticar problemas de upload de forma isolada
 * Acessa via: http://servidor/estoquemh/php/teste_upload_simples.php
 */

// Limpar saída anterior
ob_clean();
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<meta charset='utf-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";
echo "<title>Teste Upload Simples</title>";
echo "<style>";
echo "body { font-family: Arial; max-width: 600px; margin: 50px auto; }";
echo ".resultado { margin: 20px 0; padding: 15px; border-radius: 5px; }";
echo ".ok { background: #d4edda; border: 1px solid #28a745; color: #155724; }";
echo ".erro { background: #f8d7da; border: 1px solid #dc3545; color: #721c24; }";
echo ".aviso { background: #fff3cd; border: 1px solid #ffc107; color: #856404; }";
echo "textarea { width: 100%; height: 200px; }";
echo "button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>🧪 Teste de Upload Simples</h1>";

// ===== VERIFICAÇÕES INICIAIS =====
echo "<h2>1️⃣ Verificações de Servidor</h2>";

// GD Library
echo "<div class='resultado";
echo (extension_loaded('gd')) ? " ok" : " erro";
echo "'>";
echo (extension_loaded('gd')) 
    ? "✅ GD Library: ATIVO" 
    : "❌ GD Library: INATIVO (Imagens não vão funcionar)";
echo "</div>";

// Fileinfo
echo "<div class='resultado";
echo (extension_loaded('fileinfo')) ? " ok" : " erro";
echo "'>";
echo (extension_loaded('fileinfo')) 
    ? "✅ Fileinfo: ATIVO" 
    : "❌ Fileinfo: INATIVO (MIME detection falha)";
echo "</div>";

// Upload max filesize
$upload_max = ini_get('upload_max_filesize');
echo "<div class='resultado";
echo (return_bytes($upload_max) >= 5242880) ? " ok" : " aviso";
echo "'>";
echo "📤 upload_max_filesize: " . $upload_max;
echo (return_bytes($upload_max) >= 5242880) 
    ? " ✅ (OK para 5MB)" 
    : " ⚠️ (Pode ser problema)";
echo "</div>";

// Post max size
$post_max = ini_get('post_max_size');
echo "<div class='resultado";
echo (return_bytes($post_max) >= 5242880) ? " ok" : " aviso";
echo "'>";
echo "📮 post_max_size: " . $post_max;
echo (return_bytes($post_max) >= 5242880) 
    ? " ✅ (OK para 5MB)" 
    : " ⚠️ (Pode ser problema)";
echo "</div>";

// Diretório de upload
$upload_dir = dirname(dirname(__FILE__)) . '/uploads/imagens';
echo "<div class='resultado";
if (!is_dir($upload_dir)) {
    echo " erro";
} elseif (!is_writable($upload_dir)) {
    echo " erro";
} else {
    echo " ok";
}
echo "'>";
echo "📁 Diretório /uploads/imagens/: ";
if (!is_dir($upload_dir)) {
    echo "❌ NÃO EXISTE";
} elseif (!is_writable($upload_dir)) {
    echo "❌ NÃO ESCREVÍVEL";
} else {
    echo "✅ EXISTE E ESCREVÍVEL";
}
echo "<br><small>Localização: " . $upload_dir . "</small>";
echo "</div>";

// ===== TESTE DE ARQUIVO =====
echo "<h2>2️⃣ Teste com Arquivo Enviado</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['teste_arquivo'])) {
    $arquivo = $_FILES['teste_arquivo'];
    
    echo "<div class='resultado aviso'>";
    echo "<strong>📋 Informações do Upload:</strong><br>";
    echo "Nome: " . htmlspecialchars($arquivo['name']) . "<br>";
    echo "Tipo MIME: " . htmlspecialchars($arquivo['type']) . "<br>";
    echo "Tamanho: " . ($arquivo['size'] / 1024) . " KB<br>";
    echo "Código de erro: " . $arquivo['error'];
    
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_OK       => 'Sem erro',
            UPLOAD_ERR_INI_SIZE => 'Arquivo excede upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'Arquivo excede MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL  => 'Upload incompleto',
            UPLOAD_ERR_NO_FILE  => 'Nenhum arquivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Sem tmpdir',
            UPLOAD_ERR_CANT_WRITE => 'Erro ao escrever',
            UPLOAD_ERR_EXTENSION => 'Extensão bloqueada',
        ];
        echo "<br><strong>⚠️ ERRO:</strong> " . ($errors[$arquivo['error']] ?? 'Desconhecido');
    }
    echo "</div>";
    
    // ===== VALIDAR IMAGEM =====
    if ($arquivo['error'] === UPLOAD_ERR_OK) {
        echo "<div class='resultado'>";
        echo "<strong>🔍 Validação:</strong><br>";
        
        // MIME Type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $arquivo['tmp_name']);
        finfo_close($finfo);
        
        $mimes_permitidas = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($mime, $mimes_permitidas)) {
            echo "✅ MIME type válido: " . $mime . "<br>";
        } else {
            echo "❌ MIME type NÃO permitido: " . $mime . "<br>";
        }
        
        // Size
        if ($arquivo['size'] <= 5242880) {
            echo "✅ Tamanho OK: " . ($arquivo['size'] / 1024) . " KB<br>";
        } else {
            echo "❌ Arquivo muito grande (máx 5MB)<br>";
        }
        echo "</div>";
        
        // ===== TENTAR SALVAR =====
        if (in_array($mime, $mimes_permitidas) && $arquivo['size'] <= 5242880) {
            echo "<div class='resultado'>";
            echo "<strong>💾 Tentando Salvar:</strong><br>";
            
            $timestamp = time();
            $random = substr(md5(rand()), 0, 8);
            $filename = 'teste_' . $timestamp . '_' . $random . '.jpg';
            $filepath = $upload_dir . '/' . $filename;
            
            echo "Arquivo destino: " . $filename . "<br>";
            
            // Verificar se diretório é escrevível
            if (!is_writable($upload_dir)) {
                echo "<div class='resultado erro'>";
                echo "❌ Diretório NÃO é escrevível!<br>";
                echo "Caminho: " . $upload_dir . "<br>";
                echo "Permissões: " . substr(sprintf('%o', fileperms($upload_dir)), -4) . "<br>";
                echo "Proprietário: " . posix_getpwuid(fileowner($upload_dir))['name'] . "<br>";
                echo "Grupo: " . posix_getgrgid(filegroup($upload_dir))['name'] . "<br>";
                echo "</div>";
            } else {
                // Tentar salvar
                if (move_uploaded_file($arquivo['tmp_name'], $filepath)) {
                    echo "<div class='resultado ok'>";
                    echo "✅ ARQUIVO SALVO COM SUCESSO!<br>";
                    echo "Localização: " . $filepath . "<br>";
                    echo "Tamanho: " . filesize($filepath) . " bytes<br>";
                    
                    // Deletar arquivo de teste
                    @unlink($filepath);
                    echo "✅ Arquivo de teste deletado<br>";
                    echo "</div>";
                } else {
                    echo "<div class='resultado erro'>";
                    echo "❌ ERRO ao salvar arquivo<br>";
                    echo "Possível causa: Permissões de diretório<br>";
                    echo "</div>";
                }
            }
        }
    }
}

// ===== FORMULÁRIO =====
echo "<h2>3️⃣ Enviar Arquivo de Teste</h2>";
echo "<form method='POST' enctype='multipart/form-data'>";
echo "<input type='file' name='teste_arquivo' accept='image/*' required>";
echo "<button type='submit'>Testar Upload</button>";
echo "</form>";

echo "</body></html>";

// Função auxiliar
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}
?>
