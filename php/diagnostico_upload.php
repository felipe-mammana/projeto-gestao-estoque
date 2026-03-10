<?php
/**
 * Script de Diagnóstico - Upload de Imagens
 * 
 * Verifica permissões, configurações PHP e sistema de arquivos
 * para identificar problemas no upload de imagens
 */

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnóstico de Upload</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }";
echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }";
echo ".check { margin: 15px 0; padding: 10px; border-left: 4px solid #ccc; }";
echo ".check.ok { border-left-color: #4CAF50; background: #e8f5e9; }";
echo ".check.warning { border-left-color: #FF9800; background: #fff3e0; }";
echo ".check.error { border-left-color: #f44336; background: #ffebee; }";
echo ".check strong { display: block; margin-bottom: 5px; }";
echo "</style>";
echo "</head><body>";
echo "<div class='container'>";
echo "<h1>🔍 Diagnóstico de Upload de Imagens</h1>";

$checks = [];

// 1. Verificar PHP GD Library
$check = [
    'titulo' => 'GD Library',
    'descricao' => 'Necessária para processar imagens'
];
if (extension_loaded('gd')) {
    $check['status'] = 'ok';
    $check['msg'] = '✓ Ativa';
} else {
    $check['status'] = 'error';
    $check['msg'] = '✗ Desativada - Ativar em php.ini';
}
$checks[] = $check;

// 2. Verificar fileinfo
$check = [
    'titulo' => 'Fileinfo Extension',
    'descricao' => 'Necessária para detectar MIME type'
];
if (extension_loaded('fileinfo')) {
    $check['status'] = 'ok';
    $check['msg'] = '✓ Ativa';
} else {
    $check['status'] = 'error';
    $check['msg'] = '✗ Desativada - Ativar em php.ini';
}
$checks[] = $check;

// 3. Verificar upload_max_filesize
$check = [
    'titulo' => 'upload_max_filesize',
    'descricao' => 'Máximo tamanho de upload permitido'
];
$uploadMax = ini_get('upload_max_filesize');
$uploadMaxBytes = (int)$uploadMax * 1024 * 1024;
if ($uploadMaxBytes >= 5 * 1024 * 1024) {
    $check['status'] = 'ok';
    $check['msg'] = "✓ {$uploadMax} (mínimo 5MB recomendado)";
} else {
    $check['status'] = 'warning';
    $check['msg'] = "⚠ {$uploadMax} (menor que 5MB recomendado)";
}
$checks[] = $check;

// 4. Verificar post_max_size
$check = [
    'titulo' => 'post_max_size',
    'descricao' => 'Máximo tamanho de POST data'
];
$postMax = ini_get('post_max_size');
$postMaxBytes = (int)$postMax * 1024 * 1024;
if ($postMaxBytes >= 5 * 1024 * 1024) {
    $check['status'] = 'ok';
    $check['msg'] = "✓ {$postMax} (mínimo 5MB recomendado)";
} else {
    $check['status'] = 'warning';
    $check['msg'] = "⚠ {$postMax} (menor que 5MB recomendado)";
}
$checks[] = $check;

// 5. Verificar /uploads/imagens/ existência
$check = [
    'titulo' => 'Diretório /uploads/imagens/',
    'descricao' => 'Pasta onde as imagens são salvas'
];
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/uploads/imagens';
if (is_dir($uploadDir)) {
    $check['status'] = 'ok';
    $check['msg'] = "✓ Existe: {$uploadDir}";
} else {
    $check['status'] = 'error';
    $check['msg'] = "✗ Não existe: {$uploadDir} - Criar com: mkdir -p {$uploadDir}";
}
$checks[] = $check;

// 6. Verificar permissões de /uploads/imagens/
if (is_dir($uploadDir)) {
    $check = [
        'titulo' => 'Permissões de /uploads/imagens/',
        'descricao' => 'Deve ser escrevível (755 ou 777)'
    ];
    $perms = substr(sprintf('%o', fileperms($uploadDir)), -4);
    if (is_writable($uploadDir)) {
        $check['status'] = 'ok';
        $check['msg'] = "✓ Escrevível (permissões: {$perms})";
    } else {
        $check['status'] = 'error';
        $check['msg'] = "✗ Não escrevível (permissões: {$perms}) - Corrigir com: chmod 755 {$uploadDir}";
    }
    $checks[] = $check;
}

// 7. Verificar /uploads/ existência
$check = [
    'titulo' => 'Diretório /uploads/',
    'descricao' => 'Pasta pai'
];
$uploadsDir = $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/uploads';
if (is_dir($uploadsDir)) {
    $check['status'] = 'ok';
    $check['msg'] = "✓ Existe";
    if (is_writable($uploadsDir)) {
        $check['msg'] .= " e é escrevível";
    } else {
        $check['status'] = 'warning';
        $check['msg'] .= " mas NÃO é escrevível";
    }
} else {
    $check['status'] = 'error';
    $check['msg'] = "✗ Não existe";
}
$checks[] = $check;

// 8. Verificar image_handler.php
$check = [
    'titulo' => 'image_handler.php',
    'descricao' => 'Arquivo central de processamento de imagens'
];
$imageHandlerPath = $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/image_handler.php';
if (file_exists($imageHandlerPath)) {
    $check['status'] = 'ok';
    $check['msg'] = "✓ Existe";
} else {
    $check['status'] = 'error';
    $check['msg'] = "✗ Não encontrado: {$imageHandlerPath}";
}
$checks[] = $check;

// 9. Verificar espaço em disco
$check = [
    'titulo' => 'Espaço em Disco',
    'descricao' => 'Espaço disponível para uploads'
];
$free = disk_free_space($uploadDir ?? $_SERVER['DOCUMENT_ROOT']);
$freeGB = $free / (1024 * 1024 * 1024);
if ($freeGB > 1) {
    $check['status'] = 'ok';
    $check['msg'] = "✓ " . number_format($freeGB, 2) . "GB disponível";
} else {
    $check['status'] = 'warning';
    $check['msg'] = "⚠ Apenas " . number_format($freeGB, 2) . "GB disponível";
}
$checks[] = $check;

// 10. Test file creation
$check = [
    'titulo' => 'Teste de Criação de Arquivo',
    'descricao' => 'Tentativa de criar arquivo de teste'
];
if (is_dir($uploadDir)) {
    $testFile = $uploadDir . '/test_' . time() . '.txt';
    if (@file_put_contents($testFile, 'teste')) {
        @unlink($testFile);
        $check['status'] = 'ok';
        $check['msg'] = '✓ Arquivo criado e deletado com sucesso';
    } else {
        $check['status'] = 'error';
        $check['msg'] = '✗ Não conseguiu criar arquivo - verifique permissões';
    }
} else {
    $check['status'] = 'error';
    $check['msg'] = '✗ Diretório não existe';
}
$checks[] = $check;

// Renderizar checks
foreach ($checks as $check) {
    echo "<div class='check {$check['status']}'>";
    echo "<strong>{$check['titulo']}</strong>";
    echo "<small>{$check['descricao']}</small>";
    echo "<p>{$check['msg']}</p>";
    echo "</div>";
}

echo "</div>";
echo "</body></html>";
?>
