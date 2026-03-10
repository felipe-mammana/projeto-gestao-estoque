#!/usr/bin/env php
<?php
/**
 * QUICK REFERENCE - Image Handler System
 * 
 * Guia rápido de uso do novo sistema de imagens (Filesystem)
 * 
 * ================== ANTES (BLOB) vs AGORA (Filesystem) ==================
 * 
 * ANTES:
 *   $stmt->bind_param("isisbis", ...);
 *   $stmt->send_long_data(5, $binaryData);     // Lento e propenso a timeout
 *   SELECT * FROM itens;                        // Traz 50MB de BLOBs
 * 
 * AGORA:
 *   $stmt->bind_param("sisiiss", ...);
 *   $imagePath = saveItemImage($_FILES['foto'], $itemId);  // Rápido
 *   SELECT * FROM itens;                        // Traz apenas paths (~100 bytes)
 * 
 * =========================================================================
 * 
 */

// ======= EXEMPLO 1: USAR NO SEU ARQUIVO =======

// 1. No topo do seu arquivo PHP, incluir o handler:
/*
    require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/image_handler.php';
*/

// 2. Validar arquivo antes de processar:
/*
    if (isset($_FILES['foto']) && isValidImage($_FILES['foto'])) {
        // É uma imagem válida (JPEG/PNG/GIF/WebP, max 5MB)
        $imagePath = saveItemImage($_FILES['foto'], $itemId);
        if ($imagePath) {
            // Sucesso! $imagePath = "/uploads/imagens/item_123_456.jpg"
            // Agora salven no BD:
            $stmt->bind_param("si", $imagePath, $itemId);
            $stmt->execute();
        }
    }
*/

// ======= EXEMPLO 2: DELETAR IMAGEM ANTIGA =======

/*
    // Quando usuário troca de foto:
    
    // 1. Ler foto antiga
    $currentPhoto = $itemData['foto_loc']; // ex: "/uploads/imagens/item_123_old.jpg"
    
    // 2. Salvar nova
    $newPath = saveItemImage($_FILES['nova_foto'], $itemId);
    
    // 3. Deletar old (só se nova salvou)
    if ($newPath) {
        deleteItemImage($currentPhoto); // Remove arquivo antigo
        // UPDATE BD com novo path...
    }
*/

// ======= EXEMPLO 3: EXIBIR IMAGEM =======

/*
    // No HTML:
    <img src="/estoquemh/php/exibir_foto.php?id=123" alt="Item">
    
    // exibir_foto.php detecção automática:
    // - SELECT foto_loc FROM itens WHERE id=123
    // - Se for path: readfile() do disco
    // - Se for BLOB legado: detecta tipo e echo (compatibilidade)
*/

// ======= FUNÇÕES DISPONÍVEIS =======

/*
    === saveItemImage($file, $itemId) ===
    Parametros:
        $file: array $_FILES['campo'] (com keys: name, tmp_name, size, type, error)
        $itemId: integer (ID do item no BD)
    
    Retorno:
        string: path relativo "/uploads/imagens/item_123_456.jpg" se sucesso
        null: se erro (validação falhou, arquivo não salvou, etc)
    
    Faz:
        1. Valida com isValidImage()
        2. Se >4MB ou >1200px: redimensiona/comprime com GD
        3. Salva em /uploads/imagens/item_{id}_{timestamp}.{ext}
        4. Retorna path relativo
    
    Exemplo:
        $path = saveItemImage($_FILES['foto_loc'], 42);
        // Retorna: "/uploads/imagens/item_42_1234567890.jpg"
    
    --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- ---
    
    === deleteItemImage($imagePath) ===
    Parametros:
        $imagePath: string (path retornado por saveItemImage)
    
    Retorno:
        bool: true se deletado, false se erro ou caminho inválido
    
    Faz:
        1. Valida path (rejeita "../", "..\\")
        2. Se arquivo existir: unlink()
        3. Log silencioso de erros (sem exception)
    
    Exemplo:
        deleteItemImage("/uploads/imagens/item_42_1234567890.jpg");
    
    --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- ---
    
    === isValidImage($file) ===
    Parametros:
        $file: array $_FILES['campo']
    
    Retorno:
        bool: true se válido (JPEG/PNG/GIF/WebP, max 5MB)
        bool: false se inválido ou erro de upload
    
    Faz:
        1. Verifica UPLOAD_ERR_OK
        2. Verifica tamanho (max 5MB)
        3. Verifica MIME type (JPEG/PNG/GIF/WebP)
    
    Exemplo:
        if (isValidImage($_FILES['foto'])) {
            // Seguro processar
        }
    
    --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- ---
    
    === processImage($imageData, $mimeType) ===
    Parametros:
        $imageData: string (raw binary image data)
        $mimeType: string (ex: "image/jpeg")
    
    Retorno:
        string: processed image binary (redimensionado/comprimido se necessário)
    
    Faz:
        1. Se >1200px: redimensiona (GD)
        2. Se >4MB: reduz qualidade (GD)
        3. Retorna dados processados
    
    Nota: Já é chamada automaticamente por saveItemImage()
    
    --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- ---
    
    === getExtensionFromMime($mimeType) ===
    Parametros:
        $mimeType: string (ex: "image/jpeg")
    
    Retorno:
        string: extensão (ex: "jpg")
    
    Faz:
        1. Lookup table MIME → extensão
    
    Exemplo:
        $ext = getExtensionFromMime("image/png"); // Retorna "png"
*/

// ======= CONSTANTES =======

/*
    UPLOAD_DIR = $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/uploads/imagens/'
    MAX_FILE_SIZE = 5 * 1024 * 1024  // 5MB
    ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
*/

// ======= ERRO HANDLING =======

/*
    saveItemImage() retorna NULL em caso de erro. Sempre verificar:
    
    $path = saveItemImage($_FILES['foto'], $id);
    if ($path) {
        // Sucesso - salvar path no BD
        $stmt->bind_param("si", $path, $id);
    } else {
        // Erro - foto não foi salva
        echo "Erro ao fazer upload da imagem";
    }
    
    Possíveis causas de erro:
    - Arquivo não é imagem válida (isValidImage retorna false)
    - Arquivo > 5MB
    - GD Library não disponível (redimensionamento falha)
    - Permissões de escrita em /uploads/imagens/
    - Espaço em disco insuficiente
*/

// ======= CHECKLIST: ADICIONAR IMAGEM PARA NOVA ENTITY =======

/*
    1. No seu arquivo PHP de insert/update:
       require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/image_handler.php';
    
    2. Quando receber upload:
       if (isset($_FILES['seu_campo']) && isValidImage($_FILES['seu_campo'])) {
           $imagePath = saveItemImage($_FILES['seu_campo'], $recordId);
           if ($imagePath) {
               // Salvar $imagePath no BD (VARCHAR, não BLOB)
           }
       }
    
    3. Quando deletar record:
       deleteItemImage($record['image_path_column']);
    
    4. Para exibir:
       <img src="/estoquemh/php/exibir_foto.php?id=<?= $recordId ?>">
       // exibir_foto.php automaticamente detecta foto_loc e exibe
*/

// ======= DEBUGGING =======

/*
    // Ver se GD está disponível:
    var_dump(extension_loaded('gd')); // true/false
    
    // Ver se pasta tem permissão:
    var_dump(is_writable('/xampp/htdocs/estoquemh/uploads/imagens'));
    
    // Ver dados no BD:
    SELECT id, nome, foto_loc FROM itens WHERE id = 123;
    // foto_loc deve ser: "/uploads/imagens/item_123_*.jpg" (se novo)
    // ou: binary blob (se antigo, ainda não migrado)
    
    // Testar exibição manual:
    http://localhost/estoquemh/php/exibir_foto.php?id=123
    // Deve exibir imagem ou erro 404/text
*/

// ======= MIGRATION (de BLOB para Filesystem) =======

/*
    Seus dados atuais podem estar como BLOB no BD. Para migrar:
    
    VIA BROWSER:
    http://seu-dominio/estoquemh/php/migrate_blobs_to_files.php
    
    VIA CLI:
    php /xampp/htdocs/estoquemh/php/migrate_blobs_to_files.php
    
    O script:
    - Lê cada BLOB
    - Detecta tipo (JPEG, PNG, GIF, WebP)
    - Salva em /uploads/imagens/
    - Atualiza BD com path
    - Mostra relatório
    
    Não quebrará nada - exibir_foto.php suporta BLOB e path!
*/

// ======= MAIS DETALHES =======

/*
    Arquivo: IMAGE_REFACTORING_GUIDE.md
    - Documentação completa
    - Troubleshooting
    - Performance benchmarks
    - Schema changes
    - FAQ
    
    Arquivo: image_handler.php
    - Código comentado
    - Docstrings de cada função
    - Constantes configuráveis
*/

echo "✅ Image Handler System - Reference Loaded\n";
echo "📖 Para mais detalhes, veja: IMAGE_REFACTORING_GUIDE.md\n";
echo "🔧 Versão: 1.0 (Production Ready)\n";
