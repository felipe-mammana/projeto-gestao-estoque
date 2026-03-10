<?php
include $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Primeiro, tentar buscar em itens_em_uso
    $stmt = $cone->prepare("SELECT foto_loc FROM itens_em_uso WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();

    // Se não encontrou ou está vazio, tentar na tabela itens
    if (empty($item) || empty($item['foto_loc'])) {
        $stmt = $cone->prepare("SELECT foto_loc FROM itens WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
    }

    if ($item && !empty($item['foto_loc'])) {
        $imagePath = $item['foto_loc'];
        
        // Construir caminho absoluto baseado se é path relativo ou legado BLOB
        // Se começar com "/" ou conter "../", é path relativo seguro
        if (strpos($imagePath, '/uploads/imagens/') === 0) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . "/estoquemh" . $imagePath;
            
            // Debug: log para verificar caminho
            error_log("DEBUG exibir_foto.php - Path do BD: " . $imagePath);
            error_log("DEBUG exibir_foto.php - Full path construído: " . $fullPath);
            error_log("DEBUG exibir_foto.php - Arquivo existe? " . (file_exists($fullPath) ? "SIM" : "NÃO"));
        } else {
            // Compatibilidade com sistema legado: BLOB armazenado como string binária
            // Se não for um path válido, tratar como BLOB legado
            $fullPath = null;
        }
        
        // Tentar ler arquivo do disco
        if ($fullPath && file_exists($fullPath) && is_readable($fullPath)) {
            // Detectar MIME type pela extensão (ignorar timestamp no nome)
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp'
            ];
            
            $mimeType = $mimeTypes[$ext] ?? 'image/jpeg';
            
            header("Content-Type: " . $mimeType);
            header("Content-Length: " . filesize($fullPath));
            header("Cache-Control: public, max-age=86400");
            
            readfile($fullPath);
            exit;
        } elseif (!$fullPath) {
            // Compatibilidade legado: dados BLOB ainda no banco
            // Detectar tipo de imagem pela estrutura do arquivo
            $imageData = $item['foto_loc'];
            
            // Verificar o header da imagem para detectar tipo
            $bom = substr($imageData, 0, 3);
            
            if ($bom === "\xFF\xD8\xFF") {
                // JPEG
                header("Content-Type: image/jpeg");
            } elseif (substr($imageData, 0, 8) === "\x89PNG\r\n\x1a\n") {
                // PNG
                header("Content-Type: image/png");
            } elseif (substr($imageData, 0, 6) === "GIF87a" || substr($imageData, 0, 6) === "GIF89a") {
                // GIF
                header("Content-Type: image/gif");
            } else {
                // Default para JPEG
                header("Content-Type: image/jpeg");
            }
            
            header("Content-Length: " . strlen($imageData));
            header("Cache-Control: public, max-age=86400");
            
            echo $imageData;
            exit;
        } else {
            // Arquivo não encontrado no disco. limpar campo no banco para evitar tentativas futuras
            if (!empty($imagePath) && strpos($imagePath, '/uploads/imagens/') === 0) {
                $stmtClear = $cone->prepare("UPDATE itens SET foto_loc = NULL WHERE id = ?");
                if ($stmtClear) {
                    $stmtClear->bind_param("i", $id);
                    $stmtClear->execute();
                    $stmtClear->close();
                }
            }

            // retornar placeholder transparente (mesmo do fim do arquivo)
            header("Content-Type: image/png");
            header("Cache-Control: public, max-age=86400");
            // 1x1 PNG transparente
            echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
            exit;
        }
    } else {
        // Nenhum item ou foto encontrada
        http_response_code(404);
        header("Content-Type: text/plain");
        echo "Item ou imagem não encontrada";
        exit;
    }
} else {
    http_response_code(400);
    header("Content-Type: text/plain");
    echo "ID não fornecido";
    exit;
}

// Se não encontrar nada, retornar imagem placeholder (1x1 pixel transparente PNG)
header("Content-Type: image/png");
header("Content-Length: 68");
echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
exit;
?>