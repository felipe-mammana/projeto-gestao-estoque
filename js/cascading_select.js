/**
 * cascading_select.js
 * Implementação de Select em Cascata para Locais de Armazenamento
 */

/**
 * Carrega os filhos de um local ao selecionar o pai
 * @param {string} parentSelectId - ID do select pai
 * @param {string} childSelectId - ID do select filho
 */
function loadChildLocations(parentSelectId, childSelectId) {
    const parentSelect = document.getElementById(parentSelectId);
    const childSelect = document.getElementById(childSelectId);
    
    if (!parentSelect || !childSelect) {
        console.error(`Select não encontrado: ${parentSelectId} ou ${childSelectId}`);
        return;
    }
    
    const parentId = parentSelect.value;
    
    // Limpar select filho
    childSelect.innerHTML = '<option value="">Selecione um local</option>';
    
    // Se nenhum pai selecionado, sair
    if (!parentId) {
        childSelect.disabled = true;
        return;
    }
    
    // Buscar filhos via AJAX
    fetch('/estoquemh/php/get_child_locations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'parent_id=' + encodeURIComponent(parentId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data.length > 0) {
            data.data.forEach(location => {
                const option = document.createElement('option');
                option.value = location.id;
                option.textContent = location.nome;
                childSelect.appendChild(option);
            });
            childSelect.disabled = false;
        } else {
            childSelect.innerHTML = '<option value="">Nenhum local disponível</option>';
            childSelect.disabled = true;
        }
    })
    .catch(error => {
        console.error('Erro ao carregar locais filhos:', error);
        childSelect.innerHTML = '<option value="">Erro ao carregar</option>';
        childSelect.disabled = true;
    });
}

/**
 * Inicializa event listeners para cascading selects
 * Chamado após modal aberto
 */
function initCascadingSelects() {
    const cascadeConfigs = [
        {
            parent: 'item-location-pai',
            child: 'item-location-filho',
            hidden: 'item-localizacao-id'
        },
        {
            parent: 'editar-item-location-pai',
            child: 'editar-item-location-filho',
            hidden: 'editar-item-localizacao-id'
        }
    ];
    
    cascadeConfigs.forEach(config => {
        const parentSelect = document.getElementById(config.parent);
        const childSelect = document.getElementById(config.child);
        const hiddenField = document.getElementById(config.hidden);
        
        if (parentSelect) {
            parentSelect.addEventListener('change', function() {
                loadChildLocations(config.parent, config.child);
                // Atualizar hidden com valor do pai se não houver filho
                if (hiddenField) {
                    hiddenField.value = this.value;
                }
            });
        }
        
        if (childSelect) {
            childSelect.addEventListener('change', function() {
                // Atualizar hidden com valor do filho (prioridade)
                if (hiddenField) {
                    hiddenField.value = this.value || parentSelect.value;
                }
            });
        }
    });
}

/**
 * Atualiza a exibição do local selecionado no formulário
 * Retorna o local completo: "Armário 1 > Prateleira 1"
 */
function getSelectedLocationPath() {
    const parentSelect = document.getElementById('item-location-pai');
    const childSelect = document.getElementById('item-location-filho');
    
    if (!parentSelect) return '';
    
    let path = parentSelect.options[parentSelect.selectedIndex]?.text || '';
    
    if (childSelect && childSelect.value) {
        const childText = childSelect.options[childSelect.selectedIndex]?.text || '';
        if (childText && childText !== 'Selecione um local') {
            path += ' > ' + childText;
        }
    }
    
    return path;
}

/**
 * Obtém o ID do local selecionado (prioriza o filho, se houver)
 */
function getSelectedLocationId() {
    const parentSelect = document.getElementById('item-location-pai');
    const childSelect = document.getElementById('item-location-filho');
    
    if (childSelect && childSelect.value) {
        return childSelect.value;
    }
    
    return parentSelect?.value || null;
}

/**
 * Pré-seleciona um local nos cascading selects
 * Útil ao editar um item existente
 */
function preselectLocation(locationId, parentSelectId, childSelectId) {
    const parentSelect = document.getElementById(parentSelectId);
    const childSelect = document.getElementById(childSelectId);
    
    if (!parentSelect || !childSelect) return;
    
    // Atualizar campo hidden baseado no modal
    const hiddenFieldId = parentSelectId.includes('editar') 
        ? 'editar-item-localizacao-id' 
        : 'item-localizacao-id';
    const hiddenField = document.getElementById(hiddenFieldId);
    if (hiddenField) {
        hiddenField.value = locationId;
    }
    
    // Buscar informações do local para saber se é pai ou filho
    fetch('/estoquemh/php/get_location_info.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'location_id=' + encodeURIComponent(locationId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const location = data.data;
            
            if (location.parent_id) {
                // É um local filho
                parentSelect.value = location.parent_id;
                
                // Carregar filhos e depois selecionar
                loadChildLocations(parentSelectId, childSelectId);
                
                // Aguardar o carregamento
                setTimeout(() => {
                    childSelect.value = locationId;
                }, 300);
            } else {
                // É um local pai
                parentSelect.value = locationId;
                childSelect.innerHTML = '<option value="">Nenhum local disponível</option>';
                childSelect.disabled = true;
            }
        }
    })
    .catch(error => console.error('Erro ao pré-selecionar local:', error));
}

// Inicializar ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    initCascadingSelects();
});
