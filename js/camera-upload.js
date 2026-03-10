/**
 * Camera Upload Manager
 * Gerencia preview de fotos, upload via câmera e validações
 */

// Função para inicializar os listeners de arquivo
function initCameraUpload() {
    console.log('🎥 Inicializando Camera Upload...');
    
    // Gerenciar preview de foto no modal de adicionar
    const fotoInput = document.getElementById('foto_loc_input');
    const fotoPreview = document.getElementById('foto_preview');
    
    if (fotoInput && fotoPreview) {
        console.log('✅ Input foto_loc_input encontrado');
        
        // Remover listeners antigos para evitar duplicação
        if (fotoInput._fileChangeHandler) {
            fotoInput.removeEventListener('change', fotoInput._fileChangeHandler);
        }
        
        // Adicionar novo listener
        fotoInput.addEventListener('change', handleFileSelectWrapper);
        fotoInput._fileChangeHandler = handleFileSelectWrapper;
    }
    
    // Gerenciar preview de foto no modal de editar
    const fotoEditInput = document.getElementById('foto_loc_edit_input');
    const fotoEditPreview = document.getElementById('foto_edit_preview');
    
    if (fotoEditInput && fotoEditPreview) {
        console.log('✅ Input foto_loc_edit_input encontrado');
        
        // Remover listeners antigos para evitar duplicação
        if (fotoEditInput._fileChangeHandler) {
            fotoEditInput.removeEventListener('change', fotoEditInput._fileChangeHandler);
        }
        
        // Adicionar novo listener
        fotoEditInput.addEventListener('change', handleFileSelectWrapperEdit);
        fotoEditInput._fileChangeHandler = handleFileSelectWrapperEdit;
    }
    
    // Detectar câmera disponível e adicionar feedback visual
    if (hasCameraSupport()) {
        console.log('📱 Câmera detectada no dispositivo');
    }
}

// Wrapper para modal adicionar
function handleFileSelectWrapper(e) {
    const previewId = 'foto_preview';
    const preview = document.getElementById(previewId);
    if (preview && e.target && e.target.files && e.target.files.length > 0) {
        handleFileSelect(e, preview);
    }
}

// Wrapper para modal editar
function handleFileSelectWrapperEdit(e) {
    const previewId = 'foto_edit_preview';
    const preview = document.getElementById(previewId);
    if (preview && e.target && e.target.files && e.target.files.length > 0) {
        handleFileSelect(e, preview);
    }
}

/**
 * Processa a seleção de arquivo e mostra preview
 */
function handleFileSelect(event, previewContainer) {
    if (!event || !event.target || !previewContainer) {
        console.warn('⚠️ Parâmetros inválidos em handleFileSelect');
        return;
    }
    
    if (!event.target.files || event.target.files.length === 0) {
        console.log('❌ Nenhum arquivo selecionado');
        previewContainer.classList.remove('active');
        return;
    }
    
    const file = event.target.files[0];
    console.log('📸 Arquivo selecionado:', file.name, '|', (file.size / 1024).toFixed(2) + 'KB');
    
    // Validar tipo de arquivo
    if (!file.type.startsWith('image/')) {
        console.warn('❌ Arquivo não é imagem:', file.type);
        alert('Por favor, selecione um arquivo de imagem válido');
        event.target.value = '';
        previewContainer.classList.remove('active');
        return;
    }
    
    // Validar tamanho (máx 5MB)
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        console.warn('❌ Arquivo muito grande:', file.size);
        alert('Imagem muito grande! Máximo 5MB');
        event.target.value = '';
        previewContainer.classList.remove('active');
        return;
    }
    
    // Criar preview
    console.log('🎨 Criando preview...');
    const reader = new FileReader();
    const inputId = event.target.id;
    
    reader.onload = function(e) {
        console.log('✅ FileReader onload disparado');
        
        const imgHtml = `<img src="${e.target.result}" alt="Preview">`;
        const btnHtml = `<button type="button" class="remove-btn" data-input-id="${inputId}" onclick="removePreview(event); return false;">✕</button>`;
        
        if (previewContainer) {
            previewContainer.innerHTML = imgHtml + btnHtml;
            previewContainer.classList.add('active');
            console.log('✅ Preview renderizado com sucesso');
        }
    };
    
    reader.onerror = function(error) {
        console.error('❌ Erro ao ler arquivo:', error);
        alert('Erro ao ler o arquivo');
        event.target.value = '';
        previewContainer.classList.remove('active');
    };
    
    console.log('📖 Iniciando leitura do arquivo como DataURL...');
    reader.readAsDataURL(file);
}

/**
 * Remove a foto selecionada
 */
function removePreview(event) {
    if (!event) {
        console.warn('⚠️ Event não disponível em removePreview');
        return;
    }
    
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();
    
    // Extrair inputId do data-input-id do botão
    const button = event.target;
    if (!button) return;
    
    const inputId = button.getAttribute('data-input-id');
    if (!inputId) {
        console.warn('⚠️ data-input-id não encontrado no botão');
        return;
    }
    
    // Limpar o input
    const input = document.getElementById(inputId);
    if (input) {
        input.value = '';
        console.log('✅ Input limpo:', inputId);
    }
    
    // Remover preview
    const previewId = inputId.replace('_input', '_preview');
    const preview = document.getElementById(previewId);
    if (preview) {
        preview.innerHTML = '';
        preview.classList.remove('active');
        console.log('✅ Preview removido:', previewId);
    }
    
    return false;
}

/**
 * Detectar se é mobile com câmera disponível
 */
function hasCameraSupport() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

// Inicializar na DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 DOMContentLoaded disparado - inicializando Camera Upload');
    initCameraUpload();
});

// Fallback: Re-inicializar após um pequeno delay
setTimeout(function() {
    console.log('⏱️ Verificação de fallback do Camera Upload...');
    initCameraUpload();
}, 1000);

// Usar MutationObserver para monitorar mudanças no DOM
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
            const target = mutation.target;
            // Se um modal-bg foi exibido, reinicializar
            if (target.classList && target.classList.contains('modal-bg')) {
                const style = window.getComputedStyle(target);
                if (style.display !== 'none') {
                    console.log('🔄 Modal foi aberto - reinicializando Camera Upload');
                    setTimeout(initCameraUpload, 100);
                }
            }
        }
    });
});

// Observar todas as modal-bg
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal-bg').forEach(modalBg => {
        observer.observe(modalBg, {
            attributes: true,
            attributeFilter: ['style']
        });
    });
    console.log('👁️ MutationObserver ativado para monitorar modais');
});
