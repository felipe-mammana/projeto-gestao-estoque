/**
 * ============================================
 * UPLOAD DE ARQUIVO MELHORADO
 * ============================================
 * Drag and drop + Click to upload
 * Preview e validação de arquivo
 */

document.addEventListener('DOMContentLoaded', function() {
    setupFileUpload();
});

function setupFileUpload() {
    const uploadZone = document.getElementById('upload-zone');
    const fileInput = document.getElementById('foto_loc');
    
    if (!uploadZone || !fileInput) return;
    
    // ===== CLIQUE PARA ABRIR SELETOR =====
    uploadZone.addEventListener('click', () => {
        fileInput.click();
    });
    
    // ===== DRAG OVER =====
    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.stopPropagation();
        uploadZone.style.borderColor = 'var(--primary)';
        uploadZone.style.background = 'rgba(59, 130, 246, 0.15)';
        uploadZone.classList.add('active');
    });
    
    // ===== DRAG LEAVE =====
    uploadZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        e.stopPropagation();
        uploadZone.style.borderColor = 'var(--glass-border)';
        uploadZone.style.background = 'rgba(59, 130, 246, 0.05)';
        uploadZone.classList.remove('active');
    });
    
    // ===== DROP =====
    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        uploadZone.style.borderColor = 'var(--glass-border)';
        uploadZone.style.background = 'rgba(59, 130, 246, 0.05)';
        uploadZone.classList.remove('active');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelect(files[0]);
        }
    });
    
    // ===== CHANGE (seletor de arquivo) =====
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleFileSelect(this.files[0]);
        }
    });
    
    /**
     * Processa arquivo selecionado
     * @param {File} file - Arquivo selecionado
     */
    function handleFileSelect(file) {
        // Validar tipo
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Formato não permitido. Use JPG, PNG, GIF ou WebP');
            return;
        }
        
        // Validar tamanho (5MB)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('Arquivo muito grande. Máximo: 5MB');
            return;
        }
        
        // Atualizar input
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;
        
        // Atualizar UI
        updateUploadZoneText(file.name, file.size);
        
        // Mostrar preview
        showImagePreview(file);
    }
    
    /**
     * Atualiza texto da zona de upload
     * @param {string} filename - Nome do arquivo
     * @param {number} size - Tamanho em bytes
     */
    function updateUploadZoneText(filename, size) {
        const sizeKB = (size / 1024).toFixed(2);
        uploadZone.querySelector('p').innerHTML = `
            <strong>✅ ${filename}</strong><br>
            <small style="color: #64748b;">${sizeKB}KB</small>
        `;
    }
    
    /**
     * Mostra preview da imagem
     * @param {File} file - Arquivo de imagem
     */
    function showImagePreview(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            let previewContainer = document.getElementById('image-preview');
            
            if (!previewContainer) {
                previewContainer = document.createElement('div');
                previewContainer.id = 'image-preview';
                previewContainer.style.cssText = `
                    margin-top: 16px;
                    padding: 12px;
                    border-radius: 8px;
                    background: rgba(59, 130, 246, 0.1);
                    border: 1px solid var(--glass-border);
                `;
                uploadZone.parentElement.insertBefore(previewContainer, uploadZone.nextSibling);
            }
            
            previewContainer.innerHTML = `
                <div style="display: flex; gap: 12px; align-items: center;">
                    <img src="${e.target.result}" style="width: 60px; height: 60px; border-radius: 6px; object-fit: cover;">
                    <div>
                        <p style="margin: 0; font-weight: 500;">Preview da imagem</p>
                        <button type="button" style="margin-top: 8px; padding: 6px 12px; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;" onclick="removerImagemPreview()">
                            Remover
                        </button>
                    </div>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    }
}

/**
 * Remove preview da imagem
 */
function removerImagemPreview() {
    const fileInput = document.getElementById('foto_loc');
    const previewContainer = document.getElementById('image-preview');
    
    if (fileInput) {
        fileInput.value = '';
    }
    
    if (previewContainer) {
        previewContainer.remove();
    }
    
    const uploadZone = document.getElementById('upload-zone');
    if (uploadZone) {
        uploadZone.querySelector('p').innerHTML = '📁<br><p>Clique ou arraste uma imagem aqui</p>';
    }
}
