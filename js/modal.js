/**
 * ============================================
 * SISTEMA DE GERENCIAMENTO DE MODAIS
 * ============================================
 * Funções centralizadas para abrir, fechar e gerenciar modais
 */

/**
 * Abre um modal fechando todos os outros
 * @param {string} id - ID do elemento modal a abrir
 */
function exibirModal(id) {
    const modal = document.getElementById(id);
    if (!modal) {
        console.warn(`Modal não encontrado: ${id}`);
        return;
    }
    
    // Fecha todos os modais
    document.querySelectorAll('.modal-bg').forEach(m => {
        m.style.display = 'none';
    });
    
    // Abre o modal solicitado
    modal.style.display = 'flex';
}

function abrirModal(tipo) {
    console.log('abrirModal chamado com tipo:', tipo);
    if (tipo === 'item') exibirModal('modal-item-bg');
    if (tipo === 'filtros') exibirModal('modal-filtros-bg');
    if (tipo === 'editar') exibirModal('modal-editar-bg');
    if (tipo === 'detalhes') exibirModal('modal-detalhes-bg');
    if (tipo === 'foto-loc') exibirModal('modal-foto-loc-bg');
    if (tipo === 'categoria') {
        exibirModal('modal-categoria-bg');
        mostrarPrincipal();
    }
    if (tipo === 'locations') {
        console.log('Abrindo modal de locations...');
        exibirModal('modal-locations-bg');
        setTimeout(function() {
            console.log('Chamando mostrarLocationLista...');
            if (typeof mostrarLocationLista === 'function') {
                mostrarLocationLista();
            } else {
                console.error('mostrarLocationLista não é uma função!');
            }
        }, 100);
    }
    if (tipo === 'editar-location') exibirModal('modal-editar-location-bg');
    if (tipo === 'exportar-logs') exibirModal('modal-exportar-logs');
}

function abrirModalMovimento(itemId, itemNome) {
    const campoId = document.getElementById('movimento-item-id');
    const campoNome = document.getElementById('movimento-item-nome');
    if (campoId) campoId.value = itemId;
    if (campoNome) campoNome.innerHTML = 'Item: <strong>' + itemNome + '</strong>';
    exibirModal('modal-movimento-bg');
}

function abrirFotoLocalizacao(id) {
    const img = document.getElementById('img-foto-loc');
    if (img) {
        img.src = '/estoquemh/php/exibir_foto.php?id=' + id + '&t=' + new Date().getTime();
        // passar a chave que abrirModal entende
        abrirModal('foto-loc');
    }
}

function abrirModalEditar(id, nome, categoriaId, localizacaoId, quantidade, observacao) {
    // Preencher campos básicos
    document.getElementById('editar-item-id').value = id;
    document.getElementById('editar-item-nome').value = nome;
    document.getElementById('editar-item-categoria').value = categoriaId;
    document.getElementById('editar-item-quantidade').value = quantidade;
    document.getElementById('editar-item-observacao').value = observacao;
    
    // Atualizar campo hidden com ID de localização
    document.getElementById('editar-item-localizacao-id').value = localizacaoId || '';

    // Atualizar preview da foto atual (se houver)
    const atualPreview = document.getElementById('foto_atual_preview');
    if (atualPreview) {
        // limpar preview existente imediatamente
        atualPreview.innerHTML = '';
        atualPreview.classList.remove('active');

        const url = `/estoquemh/php/exibir_foto.php?id=${id}&t=${Date.now()}`;
        // testar carregamento para evitar exibir um quadrado 1x1 quando não há imagem
        const tester = new Image();
        tester.onload = function() {
            // se a imagem for maior que 2px em qualquer dimensão, consideramos válida
            if (this.naturalWidth > 2 && this.naturalHeight > 2) {
                atualPreview.innerHTML = `<img src="${url}" alt="Foto atual">`;
                atualPreview.classList.add('active');
            } else {
                // mantém limpo
            }
        };
        tester.onerror = function() {
            // mantém limpo
        };
        tester.src = url;
    }

    // Abrir o modal primeiro
    exibirModal('modal-editar-bg');
    
    // Se tem location_id (válido e não zero/vazio), pré-selecionar nos cascading selects
    if (localizacaoId && localizacaoId !== '0' && localizacaoId !== '') {
        setTimeout(() => {
            preselectLocation(
                localizacaoId, 
                'editar-item-location-pai', 
                'editar-item-location-filho'
            );
        }, 200);
    }
}

function abrirModalExcluir(itemId, itemNome) {
    const idField = document.getElementById('excluir-item-id');
    const nomeField = document.getElementById('excluir-item-nome');
    
    if (idField) idField.value = itemId;
    if (nomeField) nomeField.innerHTML = 'Item: <strong>' + itemNome + '</strong>';
    
    exibirModal('modal-excluir-bg');
}

function controleExclusao(confirmar) {
    const campoMotivo = document.getElementById('campo-motivo');
    const botao = document.getElementById('btn-excluir');
    
    if (confirmar) {
        if (campoMotivo) campoMotivo.style.display = 'block';
        if (botao) botao.disabled = false;
    } else {
        closeModal('excluir');
    }
}

function closeModal(tipo) {
    console.log('closeModal chamado com tipo:', tipo);
    const modais = {
        'movimento': 'modal-movimento-bg',
        'foto-loc': 'modal-foto-loc-bg',
        'item': 'modal-item-bg',
        'locations': 'modal-locations-bg',
        'filtros': 'modal-filtros-bg',
        'categoria': 'modal-categoria-bg',
        'editar': 'modal-editar-bg',
        'excluir': 'modal-excluir-bg',
        'exportar-logs': 'modal-exportar-logs',
        'editar-categoria': 'modal-editar-categoria-bg',
        'editar-location': 'modal-editar-location-bg'
    };
    
    const modalId = modais[tipo];
    console.log('Mapeando tipo:', tipo, '-> ID:', modalId);
    if (modalId) {
        const modal = document.getElementById(modalId);
        console.log('Modal encontrado:', modal !== null);
        if (modal) {
            modal.style.display = 'none';
            console.log('Modal fechado com sucesso');
        }
    } else {
        console.warn('Tipo de modal não mapeado:', tipo);
    }
    
    // limpar previews específicos quando fechamos o formulário de edição
    if (tipo === 'editar') {
        const atual = document.getElementById('foto_atual_preview');
        const novo = document.getElementById('foto_edit_preview');
        if (atual) {
            atual.innerHTML = '';
            atual.classList.remove('active');
        }
        if (novo) {
            novo.innerHTML = '';
            novo.classList.remove('active');
            const fileInput = document.getElementById('foto_loc_edit_input');
            if (fileInput) fileInput.value = '';
        }
    }
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-bg')) e.target.style.display = 'none';
});

// Garantir fechamento ao clicar no X (mesmo se o mapping falhar)
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal .close').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const modalBg = btn.closest('.modal-bg');
            if (modalBg) modalBg.style.display = 'none';
        });
    });
});

function esconderCategorias() {
    const boxes = ['box-principal', 'box-sub', 'box-lista'];
    boxes.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });
}

function mostrarPrincipal() {
    esconderCategorias();
    const el = document.getElementById('box-principal');
    if (el) el.style.display = 'block';
}

function mostrarSub() {
    esconderCategorias();
    const el = document.getElementById('box-sub');
    if (el) el.style.display = 'block';
}

function mostrarLista() {
    esconderCategorias();
    const el = document.getElementById('box-lista');
    if (el) el.style.display = 'block';
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    if (!sidebar || !mainContent) return;

    const isActive = sidebar.classList.contains('active');
    const isMobile = window.innerWidth <= 768;
    const sidebarWidth = isMobile ? window.innerWidth : 250; // Largura da sidebar em px
    const duration = 300; // Duração da animação em ms

    let startTime = null;

    const animate = (timestamp) => {
        if (!startTime) startTime = timestamp;
        const elapsed = timestamp - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const easeProgress = 1 - Math.pow(1 - progress, 3); // Ease-out cubic

        if (isActive) {
            // Fechando
            const currentWidth = sidebarWidth * (1 - easeProgress);
            sidebar.style.width = currentWidth + 'px';
        } else {
            // Abrindo
            const currentWidth = sidebarWidth * easeProgress;
            sidebar.style.width = currentWidth + 'px';
        }

        if (progress < 1) {
            requestAnimationFrame(animate);
        } else {
            // Finalizar
            if (isActive) {
                sidebar.classList.remove('active');
                sidebar.style.width = '';
            } else {
                sidebar.classList.add('active');
                sidebar.style.width = sidebarWidth + 'px';
            }
        }
    };

    requestAnimationFrame(animate);
}

function abrirEditarCategoria(btn) {
    const id = btn.dataset.id;
    const nome = btn.dataset.nome;
    const qtdMin = btn.dataset.qtdMin || 0;
    
    const idField = document.getElementById('edit-cat-id');
    const nomeField = document.getElementById('edit-cat-nome');
    const qtdField = document.getElementById('edit-cat-qtd');
    
    if (idField) idField.value = id;
    if (nomeField) nomeField.value = nome;
    
    if (qtdField) {
        qtdField.value = qtdMin;
        // Se for subcategoria (tem qtd minima), mostra o campo, senão esconde
        qtdField.style.display = (qtdMin > 0) ? 'block' : 'none';
    }
    
    exibirModal('modal-editar-categoria-bg');
}

/**
 * Função para excluir categoria
 * @param {number} id - ID da categoria
 */

function excluirCategoria(id) {
    if (!confirm('Tem certeza que deseja excluir esta categoria?')) return;

    fetch('/estoquemh/php/excluircategoria.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'id=' + id
    })
    .then(res => res.text())
    .then(data => {
        console.log('Resposta PHP:', data); // DEBUG
        if (data.trim() === 'ok') {
            alert('Categoria excluída com sucesso!');
            location.reload();
        } else {
            alert(data); // MOSTRA O ERRO REAL
        }
    })
    .catch(err => {
        console.error(err);
        alert('Erro na requisição');
    });
}

// Adicione este código no final do seu arquivo modal.js ou sistema.php

document.addEventListener('DOMContentLoaded', function() {
    const categoriaSelect = document.getElementById('editar-item-categoria');
    
    if (categoriaSelect) {
        categoriaSelect.addEventListener('change', function() {
            atualizarQuantidadeMinimaCategoria(this.value, 'editar-categoria-minima-display');
        });
    }
});

function atualizarQuantidadeMinimaCategoria(categoriaId, displayElementId) {
    if (!categoriaId) return;
    
    fetch('/estoquemh/php/get_categoria_minima.php?id=' + categoriaId)
        .then(response => response.json())
        .then(data => {
            const displayElement = document.getElementById(displayElementId);
            if (displayElement && data.success) {
                displayElement.textContent = data.quantidade_minima || 0;
            }
        })
        .catch(error => console.error('Erro ao buscar quantidade mínima:', error));
}
