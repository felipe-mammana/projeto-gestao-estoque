<?php
/**
 * Migration Script: Convert BLOB images to filesystem
 * 
 * This script is used to migrate existing BLOB images from the database
 * to the filesystem (/uploads/imagens/) and update the database records
 * to store only file paths.
 * 
 * Usage: Access via HTTP or run from command line
 * PHP CLI: php migrate_blobs_to_files.php
 */

// Check if running from CLI
$isCli = php_sapi_name() === 'cli';

// Determine document root
if (!$isCli) {
    include $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";
    $baseDir = $_SERVER['DOCUMENT_ROOT'] . "/estoquemh";
    $docRoot = $_SERVER['DOCUMENT_ROOT'];
} else {
    // CLI mode: adjust path accordingly
    $baseDir = __DIR__ . "/..";
    $docRoot = dirname(dirname(__DIR__));
    include $baseDir . "/cone.php";
}

require_once $baseDir . "/image_handler.php";

// Output helper
function log_message($message, $type = 'info') {
    global $isCli;
    if ($isCli) {
        echo "[$type] $message" . PHP_EOL;
    } else {
        echo "<div style='padding:8px; margin:4px 0; background-color:" . 
             ($type === 'error' ? '#fee' : ($type === 'success' ? '#efe' : '#eef')) . 
             "'><strong>" . strtoupper($type) . ":</strong> " . htmlspecialchars($message) . "</div>";
    }
}

// Check if already migrated (detect if any records still have binary blob data)
function hasBlobData($blobString) {
    // If it starts with known image file headers, it's binary BLOB data
    $headers = [
        "\xFF\xD8\xFF", // JPEG
        "\x89PNG\r\n\x1a\n", // PNG
        "GIF87a",
        "GIF89a",
        "RIFF", // WebP
    ];
    
    foreach ($headers as $header) {
        if (strpos($blobString, $header) === 0) {
            return true;
        }
    }
    
    // If it looks like a path (/uploads/imagens/...), it's already migrated
    if (strpos($blobString, '/uploads/imagens/') === 0) {
        return false;
    }
    
    return false; // Not a clear BLOB or path
}

// Page header
if (!$isCli) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Migração de BLOBs para Filesystem</title></head><body>";
    echo "<h2>Migração de Imagens: BLOB → Filesystem</h2>";
}

log_message("Iniciando migração de imagens de BLOB para filesystem...", 'info');

// Get all items from both tables
$tables = [
    'itens' => 'id',
    'itens_em_uso' => 'id'
];

$totalMigrated = 0;
$totalSkipped = 0;
$totalErrors = 0;

foreach ($tables as $table => $idCol) {
    log_message("Processando tabela '$table'...", 'info');
    
    $sql = "SELECT $idCol AS id, nome, foto_loc FROM $table WHERE foto_loc IS NOT NULL AND foto_loc != ''";
    $result = $cone->query($sql);
    
    if (!$result) {
        log_message("Erro ao consultar tabela '$table': " . $cone->error, 'error');
        $totalErrors++;
        continue;
    }
    
    while ($row = $result->fetch_assoc()) {
        $itemId = $row['id'];
        $itemName = $row['nome'] ?? "Item $itemId";
        $fotoLoc = $row['foto_loc'];
        
        // Check if it's already migrated (is a path)
        if (strpos($fotoLoc, '/uploads/imagens/') === 0) {
            log_message("Item '$itemName' (ID:$itemId) já migrado - pulado", 'info');
            $totalSkipped++;
            continue;
        }
        
        // Check if it's binary BLOB data
        if (!hasBlobData($fotoLoc)) {
            log_message("Item '$itemName' (ID:$itemId) - foto_loc não é BLOB válido - pulado", 'info');
            $totalSkipped++;
            continue;
        }
        
        // Determine file extension from BLOB header
        $ext = 'jpg'; // default
        if (strpos($fotoLoc, "\x89PNG\r\n\x1a\n") === 0) {
            $ext = 'png';
        } elseif (strpos($fotoLoc, "GIF87a") === 0 || strpos($fotoLoc, "GIF89a") === 0) {
            $ext = 'gif';
        } elseif (strpos($fotoLoc, "RIFF") === 0 && strpos($fotoLoc, "WEBP") !== false) {
            $ext = 'webp';
        }
        
        // Create directory if not exists
        $uploadDir = $baseDir . '/uploads/imagens';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                log_message("Erro ao criar diretório de uploads", 'error');
                $totalErrors++;
                continue;
            }
        }
        
        // Generate filename
        $filename = "item_{$itemId}_" . time() . "." . $ext;
        $filePath = $uploadDir . "/" . $filename;
        $relativePath = "/uploads/imagens/" . $filename;
        
        // Write BLOB to file
        if (file_put_contents($filePath, $fotoLoc) === false) {
            log_message("Erro ao salvar arquivo para item '$itemName' (ID:$itemId)", 'error');
            $totalErrors++;
            continue;
        }
        
        // Update database record with path
        $updateSql = "UPDATE $table SET foto_loc = ? WHERE $idCol = ?";
        $stmt = $cone->prepare($updateSql);
        
        if (!$stmt) {
            log_message("Erro ao preparar update para item '$itemName' (ID:$itemId): " . $cone->error, 'error');
            // Clean up file
            unlink($filePath);
            $totalErrors++;
            continue;
        }
        
        $stmt->bind_param("si", $relativePath, $itemId);
        
        if (!$stmt->execute()) {
            log_message("Erro ao atualizar BD para item '$itemName' (ID:$itemId): " . $stmt->error, 'error');
            // Clean up file
            unlink($filePath);
            $stmt->close();
            $totalErrors++;
            continue;
        }
        
        $stmt->close();
        
        log_message("Item '$itemName' (ID:$itemId) - migrado com sucesso para $relativePath", 'success');
        $totalMigrated++;
    }
}

// Summary
log_message("", 'info');
log_message("========== RESUMO DA MIGRAÇÃO ==========", 'info');
log_message("Total migrado: $totalMigrated", 'success');
log_message("Total pulado: $totalSkipped", 'info');
log_message("Total erros: $totalErrors", $totalErrors > 0 ? 'error' : 'success');
log_message("=========================================", 'info');

if (!$isCli) {
    echo "</body></html>";
}

log_message("Migração concluída!", 'success');
exit(0);
