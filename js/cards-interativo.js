/**
 * ============================================
 * CARDS INTERATIVOS (KPIs)
 * ============================================
 * Permite clicar nos cards para filtrar
 * a tabela automaticamente
 */

document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card[data-filter]');
    
    cards.forEach(card => {
        card.addEventListener('click', function() {
            const filter = this.dataset.filter;
            applyCardFilter(filter);
        });
    });
    
    /**
     * Aplica filtro baseado no card clicado
     * @param {string} filter - Tipo de filtro
     */
    function applyCardFilter(filter) {
        const filterMap = {
            'total-items': {},
            'low-stock': { status: 'baixo' },
            'zero-stock': { status: 'zero' },
            'total-categories': { mostrar: 'categorias' }
        };
        
        const params = filterMap[filter];
        if (params) {
            // Montar URL com parâmetros
            const url = new URL(window.location);
            
            // Limpar filtros anteriores
            url.searchParams.delete('status');
            url.searchParams.delete('mostrar');
            
            // Aplicar novo filtro
            Object.entries(params).forEach(([key, value]) => {
                if (value) {
                    url.searchParams.set(key, value);
                }
            });
            
            // Resetar para página 1
            url.searchParams.set('pagina', '1');
            
            window.location.href = url.toString();
        }
    }
});
