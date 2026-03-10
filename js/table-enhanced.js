/**
 * ============================================
 * TABELA MELHORADA
 * ============================================
 * Funcionalidades:
 * - Seleção de múltiplos itens
 * - Contador de seleção
 * - Ações em massa
 */

document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const selectedCount = document.getElementById('selected-count');
    const deleteSelectedBtn = document.getElementById('delete-selected');
    
    // ===== SELECIONAR TODOS =====
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            itemCheckboxes.forEach(cb => cb.checked = this.checked);
            updateSelectedCount();
        });
    }
    
    // ===== CHECKBOXES INDIVIDUAIS =====
    itemCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            // Se todos estão selecionados, marcar "selecionar todos"
            const allChecked = Array.from(itemCheckboxes).every(ch => ch.checked);
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allChecked;
            }
            updateSelectedCount();
        });
    });
    
    /**
     * Atualiza contador de seleção
     */
    function updateSelectedCount() {
        const selected = document.querySelectorAll('.item-checkbox:checked').length;
        const total = document.querySelectorAll('.item-checkbox').length;
        
        if (selectedCount) {
            if (selected === 0) {
                selectedCount.textContent = 'Nenhum selecionado';
            } else if (selected === total) {
                selectedCount.textContent = `Todos os ${total} itens selecionados`;
            } else {
                selectedCount.textContent = `${selected} de ${total} selecionados`;
            }
        }
        
        if (deleteSelectedBtn) {
            deleteSelectedBtn.style.display = selected > 0 ? 'inline-block' : 'none';
        }
    }
    
    // ===== DELETAR SELECIONADOS =====
    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.item-checkbox:checked'))
                .map(cb => cb.value);
            
            if (selectedIds.length === 0) {
                alert('Selecione ao menos um item');
                return;
            }
            
            if (confirm(`Tem certeza que deseja deletar ${selectedIds.length} item(ns)?`)) {
                deletarEmMassa(selectedIds);
            }
        });
    }
    
    /**
     * Envia requisição de deleção em massa
     * @param {array} ids - Array de IDs para deletar
     */
    function deletarEmMassa(ids) {
        const formData = new FormData();
        formData.append('ids', JSON.stringify(ids));
        
        fetch('/estoquemh/deletar-em-massa.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`${data.deleted} item(ns) deletado(s) com sucesso`);
                location.reload();
            } else {
                alert(`Erro ao deletar: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao deletar itens');
        });
    }
});
