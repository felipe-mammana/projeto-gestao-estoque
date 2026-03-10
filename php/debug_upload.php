<?php
// Arquivo para debug de upload
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/cone.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/image_handler.php';

if ($_FILES) {
    echo "=== TESTE DE UPLOAD ===\n\n";
    echo "FILES recebidos:\n";
    foreach ($_FILES as $key => $file) {
        echo "- $key: ";
        if (!empty($file['tmp_name'])) {
            $size = filesize($file['tmp_name']);
            echo "✓ OK (" . ($size / 1024 / 1024) . " MB)\n";
        } else {
            echo "✗ Vazio\n";
        }
    }
} else {
    echo "Nenhum arquivo enviado\n";
}

// Testar insert direto com novo sistema de filesystem
if ($_POST && isset($_FILES['foto_loc']) && !empty($_FILES['foto_loc']['tmp_name'])) {
    $nome = $_POST['nome'] ?? 'Teste ' . date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO itens (nome, categoria_id, localizacao, foto_loc, quantidade) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $cone->prepare($sql);
    $categoria_id = 1;
    $localizacao = 'LOCAL TESTE';
    $quantidade = 1;
    $foto_loc_path = null;
    
    $stmt->bind_param("sissi", $nome, $categoria_id, $localizacao, $foto_loc_path, $quantidade);
    
    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        
        // Processar imagem
        if (isValidImage($_FILES['foto_loc'])) {
            $imagePath = saveItemImage($_FILES['foto_loc'], $id);
            if ($imagePath) {
                $updateSql = "UPDATE itens SET foto_loc = ? WHERE id = ?";
                $updateStmt = $cone->prepare($updateSql);
                $updateStmt->bind_param("si", $imagePath, $id);
                $updateStmt->execute();
                $updateStmt->close();
                echo "\n✅ Item inserido com ID: $id\n";
                echo "Foto salva: $imagePath\n";
            } else {
                echo "\n⚠️ Item inserido com ID: $id, mas imagem falhou ao processar\n";
            }
        } else {
            echo "\n⚠️ Item inserido com ID: $id, mas arquivo não é imagem válida\n";
        }
    } else {
        echo "\n❌ Erro ao inserir: " . $stmt->error . "\n";
    }
}
?>
