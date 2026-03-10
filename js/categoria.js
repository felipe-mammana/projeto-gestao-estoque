function esconderTodasCategorias() {
    document.getElementById('box-principal').classList.add('is-hidden');
    document.getElementById('box-sub').classList.add('is-hidden');
    document.getElementById('box-lista').classList.add('is-hidden');
}

function mostrarPrincipal() {
    esconderTodasCategorias();
    document.getElementById('box-principal').classList.remove('is-hidden');
}

function mostrarSub() {
    esconderTodasCategorias();
    document.getElementById('box-sub').classList.remove('is-hidden');
}

function mostrarLista() {
    esconderTodasCategorias();
    document.getElementById('box-lista').classList.remove('is-hidden');
}


