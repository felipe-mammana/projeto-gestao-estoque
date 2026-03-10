<?php
/**
 * Image Handler - Gerencia upload, armazenamento e deleção de imagens
 * Sistema: Estoque MH
 */

define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/uploads/imagens/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

/**
 * Salva uma imagem do upload e retorna o caminho relativo
 * 
 * @param array $file $_FILES array da imagem
 * @param int $itemId ID do item (para usar no nome do arquivo)
 * @return string|false Caminho relativo ou false se falhar
 */
function saveItemImage($file, $itemId) {
    // Validar se foi enviado arquivo
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        error_log("saveItemImage: Erro de upload - erro code: " . ($file['error'] ?? 'undefined'));
        return false;
    }

    // Validar tipo MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_TYPES)) {
        error_log("saveItemImage: MIME type inválido - $mimeType (permitidos: " . implode(',', ALLOWED_TYPES) . ")");
        return false;
    }

    // Validar tamanho
    if ($file['size'] > MAX_FILE_SIZE) {
        error_log("saveItemImage: Arquivo muito grande - {$file['size']} bytes (máximo: " . MAX_FILE_SIZE . ")");
        return false;
    }

    // Criar diretório se não existir
    if (!is_dir(UPLOAD_DIR)) {
        $mkdir_result = @mkdir(UPLOAD_DIR, 0755, true);
        if (!$mkdir_result) {
            error_log("saveItemImage: ERRO ao criar diretório " . UPLOAD_DIR);
            return false;
        }
        error_log("saveItemImage: Diretório criado: " . UPLOAD_DIR);
    }

    // Verificar se diretório é escrevível
    if (!is_writable(UPLOAD_DIR)) {
        error_log("saveItemImage: Diretório NÃO é escrevível: " . UPLOAD_DIR . " (perms: " . substr(sprintf('%o', fileperms(UPLOAD_DIR)), -4) . ")");
        return false;
    }

    // Ler conteúdo e redimensionar se necessário
    $imageData = file_get_contents($file['tmp_name']);
    if (!$imageData) {
        error_log("saveItemImage: Erro ao ler arquivo temporário");
        return false;
    }

    $processedImage = processImage($imageData, $mimeType);
    
    if (!$processedImage) {
        error_log("saveItemImage: Erro ao processar imagem");
        return false;
    }

    // Gerar nome único baseado em timestamp e Item ID
    $extension = getExtensionFromMime($mimeType);
    $filename = 'item_' . $itemId . '_' . time() . '.' . $extension;
    $filepath = UPLOAD_DIR . $filename;

    // Salvar arquivo
    $bytes_written = @file_put_contents($filepath, $processedImage);
    if ($bytes_written === false) {
        error_log("saveItemImage: ERRO ao escrever arquivo - $filepath (permissões do diretório: " . substr(sprintf('%o', fileperms(UPLOAD_DIR)), -4) . ")");
        return false;
    }
    
    // Verificar se arquivo foi realmente criado
    if (!file_exists($filepath)) {
        error_log("saveItemImage: Arquivo criado mas não encontrado - $filepath");
        return false;
    }

    error_log("saveItemImage: Sucesso - Item ID: $itemId, Arquivo: $filename, Bytes: $bytes_written");

    // Retornar caminho relativo com barra inicial
    return '/uploads/imagens/' . $filename;
}

/**
 * Processa imagem: redimensiona se for maior que 1200px e comprime
 * 
 * @param string $imageData Conteúdo binário da imagem
 * @param string $mimeType Tipo MIME da imagem
 * @return string|false Dados da imagem processada ou false
 */
function processImage($imageData, $mimeType) {
    // Se for pequena (<4MB), retornar direto
    if (strlen($imageData) <= 4 * 1024 * 1024) {
        return $imageData;
    }

    // Tentar redimensionar com GD
    if (!function_exists('imagecreatefromstring')) {
        return $imageData; // Retornar original se GD não disponível
    }

    $srcImg = @imagecreatefromstring($imageData);
    if ($srcImg === false) {
        return $imageData; // Retornar original se não conseguir ler
    }

    $width = imagesx($srcImg);
    $height = imagesy($srcImg);
    $maxDim = 1200;

    // Se já pequena, retornar original
    if ($width <= $maxDim && $height <= $maxDim) {
        imagedestroy($srcImg);
        return $imageData;
    }

    // Calcular novas dimensões mantendo aspecto
    $ratio = $width / $height;
    if ($ratio > 1) {
        $newW = $maxDim;
        $newH = intval($maxDim / $ratio);
    } else {
        $newH = $maxDim;
        $newW = intval($maxDim * $ratio);
    }

    // Criar imagem redimensionada
    $dst = imagecreatetruecolor($newW, $newH);

    // Preservar transparência para PNG/GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagecolortransparent($dst, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }

    // Copiar redimensionado
    imagecopyresampled($dst, $srcImg, 0, 0, 0, 0, $newW, $newH, $width, $height);

    // Codificar para arquivo
    ob_start();
    if ($mimeType === 'image/png') {
        imagepng($dst, null, 6);
    } elseif ($mimeType === 'image/gif') {
        imagegif($dst);
    } else {
        imagejpeg($dst, null, 82);
    }
    $output = ob_get_clean();

    // Limpar memória
    imagedestroy($srcImg);
    imagedestroy($dst);

    return $output !== false ? $output : $imageData;
}

/**
 * Deleta imagem do servidor
 * 
 * @param string $imagePath Caminho relativo da imagem (ex: 'uploads/imagens/item_1_123456.jpg')
 * @return bool
 */
function deleteItemImage($imagePath) {
    if (empty($imagePath)) {
        return false;
    }

    // Validação: rejeitar se contiver null bytes (BLOB legado) ou não for um path válido
    if (strpos($imagePath, '\x00') !== false || strpos($imagePath, '/uploads/imagens/') !== 0) {
        return false; // Não é um path válido ou é um BLOB
    }

    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/' . $imagePath;

    // Validar path para segurança
    $realPath = realpath($fullPath);
    $uploadDirReal = realpath(UPLOAD_DIR);

    if ($realPath === false || $uploadDirReal === false || strpos($realPath, $uploadDirReal) !== 0) {
        return false; // Tentativa de deletar arquivo fora do diretório permitido
    }

    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }

    return false;
}

/**
 * Obtém extensão baseado no tipo MIME
 * 
 * @param string $mimeType
 * @return string
 */
function getExtensionFromMime($mimeType) {
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];

    return $extensions[$mimeType] ?? 'jpg';
}

/**
 * Valida se o arquivo é uma imagem válida
 * 
 * @param array $file $_FILES array
 * @return bool
 */
function isValidImage($file) {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    return in_array($mimeType, ALLOWED_TYPES) && $file['size'] <= MAX_FILE_SIZE;
}
?>
