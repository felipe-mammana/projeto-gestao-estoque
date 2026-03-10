/**
 * Responsividade Mobile
 */

// Detectar se é mobile
function isMobile() {
    return window.innerWidth <= 768;
}

// Toggle sidebar em mobile
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}

// Fechar sidebar ao clicar em um link
document.addEventListener('DOMContentLoaded', function() {
    // Fechar sidebar ao navegar
    const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar && isMobile()) {
                sidebar.classList.remove('active');
            }
        });
    });

    // Fechar modais ao pressionar Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });

    // Ajustar altura da viewport em mobile
    adjustViewportHeight();
});

// Fechar todos os modais
function closeAllModals() {
    const modals = document.querySelectorAll('.modal-bg');
    modals.forEach(modal => {
        modal.style.display = 'none';
    });
}

// Ajustar viewport em mobile (safari issue)
function adjustViewportHeight() {
    if (isMobile()) {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }
}

// Recalcular na mudança de orientação
window.addEventListener('orientationchange', function() {
    adjustViewportHeight();
});

// Recalcular ao redimensionar
window.addEventListener('resize', function() {
    adjustViewportHeight();
});

// Otimizar scroll em mobile
document.addEventListener('touchmove', function() {
    // Apenas renderização nativa
}, { passive: true });

// Prevenir zoom ao clicar em inputs
document.addEventListener('touchstart', function() {
    // Nenhuma ação necessária, apenas evita delays
}, { passive: true });
