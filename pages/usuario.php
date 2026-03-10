<?php
/**
 * P�?GINA DE GERENCIAMENTO DE USU�?RIOS
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/helpers.php';
$id_user = ensureLoggedInUser();
require_once $_SERVER['DOCUMENT_ROOT'] . "/estoquemh/cone.php";

$isAdmin = ($_SESSION['usuario']['tipo'] ?? '') === 'admin';
$setor_id = $_SESSION['usuario']['setor_id'];

// BUSCAR USU�?RIOS
$sql_usuarios = "
SELECT id, nome, email, cargo, foto
FROM usuarios
WHERE setor_id = $setor_id
ORDER BY nome";

$result_usuarios = mysqli_query($cone, $sql_usuarios);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no, maximum-scale=5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#0f172a">
    <title>Usuários</title>
    <link rel="stylesheet" href="/estoquemh/css/style.css">
</head>
<body>

<div class="ambient-bg">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
</div>

<div class="layout">

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-inner">
            <div class="sidebar-header">
                 <img src="/estoquemh/php/foto_usuario.php?id=<?= $_SESSION['usuario']['id'] ?>" class="perfil-foto" alt="Foto">
                <div class="perfil-info">
                    <strong class="perfil-nome"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></strong>
                    <span class="perfil-setor"><?= htmlspecialchars($_SESSION['usuario']['setor_nome']) ?></span>
                </div>
            </div>

            <nav class="sidebar-menu">
                <a href="/estoquemh/pages/sistema.php">📦 <span>Estoque</span></a>
                <a href="/estoquemh/pages/em-uso.php" class="<?= (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'em-uso.php') !== false) ? 'active' : '' ?>">🚀 <span>Em Uso</span></a>
                <a href="/estoquemh/pages/usuario.php" class="active">👥 <span>Usuários</span></a>
                <a href="/estoquemh/pages/relatorio_bi_dashboard.php">📊 <span>Relatórios BI</span></a>
                <a href="/estoquemh/pages/login.php" style="margin-top: auto; color: #f87171;">🚪 <span>Sair</span></a>
            </nav>
        </div>
    </aside>

    <div class="main-content">

        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 15px;">
                <button class="btn-toggle" onclick="toggleSidebar()">☰</button>
                <h1>Equipe do Setor</h1>
            </div>
            <div class="topbar-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button class="btn-primary" onclick="abrirModalAdd()">
                    <span>+</span> Adicionar Usu�rio
                </button>
            </div>
        </header>

        <section class="cards">
            <?php if ($result_usuarios && mysqli_num_rows($result_usuarios) > 0): ?>
                <?php while ($user = mysqli_fetch_assoc($result_usuarios)): ?>
                    <div class="card">
                        <div style="margin-bottom: 15px;">
                            <?php if (!empty($user['foto'])): ?>
                                <img src="/estoquemh/php/foto_usuario.php?id=<?= $user['id'] ?>" class="perfil-foto" style="width: 80px; height: 80px;">
                            <?php else: ?>
                                <div style="width: 80px; height: 80px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; color: #fff;">
                                    <?= strtoupper(substr($user['nome'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h3><?= htmlspecialchars($user['nome']) ?></h3>
                        <span><?= htmlspecialchars($user['cargo']) ?></span>
                        <small style="color: #64748b; margin-top: 5px;"><?= htmlspecialchars($user['email']) ?></small>

                        <div class="card-actions">
                            <button class="btn-icon btn-edit" onclick="abrirModalEdit(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nome'], ENT_QUOTES) ?>', '<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>', '<?= htmlspecialchars($user['cargo'], ENT_QUOTES) ?>')">
                                <img src="/estoquemh/img/editar.png" alt="Edit">
                            </button>

                            <?php if ($isAdmin): ?>
                                <button class="btn-icon btn-del" onclick="excluirUsuario(<?= (int)$user['id'] ?>, '<?= htmlspecialchars($user['nome'], ENT_QUOTES) ?>')">
                                    <img src="/estoquemh/img/excluir.png" alt="Del">
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 40px;">
                    Nenhum usuário encontrado.
                </p>
            <?php endif; ?>
        </section>

    </div> 
</div>

<button type="button" class="fab-add-item" onclick="abrirModalAdd()" aria-label="Adicionar usu�rio" title="Adicionar usu�rio">
    +
</button>

<div class="modal-bg" id="modalAdd">
    <div class="modal">
        <span class="close" onclick="fecharModal()">×</span>
        <h2>Novo Usuário</h2>
        <form action="/estoquemh/php/usuario_salvar.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="acao" value="add">
            <label>Nome Completo</label>
            <input type="text" name="nome" autocomplete="name" required>
            <label>Email Corporativo</label>
            <input type="email" name="email" autocomplete="email" required>
            <label>Cargo</label>
            <input type="text" name="cargo" autocomplete="organization-title" required>
            <label>Senha Provisória</label>
            <input type="password" name="senha" autocomplete="new-password" required>
            <?php if ($isAdmin): ?>
                <label>Tipo de Usuário</label>
                <select name="tipo" required>
                    <option value="user">Usuário</option>
                    <option value="admin">Administrador</option>
                </select>
            <?php endif; ?>
            <label>Foto de Perfil</label>
            <input type="file" name="foto" accept="image/*">
            <button type="submit">Salvar Usuário</button>
        </form>
    </div>
</div>

<div class="modal-bg" id="modalEdit">
    <div class="modal">
        <span class="close" onclick="fecharModal()">×</span>
        <h2>Editar Usuário</h2>
        <form action="/estoquemh/php/usuario_salvar.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="acao" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <label>Nome</label>
            <input type="text" name="nome" id="edit-nome" autocomplete="name" required>
            <label>Email</label>
            <input type="email" name="email" id="edit-email" autocomplete="email" required>
            <label>Cargo</label>
            <input type="text" name="cargo" id="edit-cargo" autocomplete="organization-title" required>
            <label>Nova Senha (opcional)</label>
            <input type="password" name="senha" autocomplete="new-password" placeholder="Deixe em branco para manter">
            <?php if ($isAdmin): ?>
                <label>Tipo de Usuário</label>
                <select name="tipo" id="edit-tipo">
                    <option value="user">Usuário</option>
                    <option value="admin">Administrador</option>
                </select>
            <?php endif; ?>
            <label>Alterar Foto</label>
            <input type="file" name="foto" accept="image/*">
            <button type="submit">Atualizar Dados</button>
        </form>
    </div>
</div>

<script>
function abrirModalAdd() {
    document.getElementById('modalAdd').style.display = 'flex';
}
function abrirModalEdit(id, nome, email, cargo) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-nome').value = nome;
    document.getElementById('edit-email').value = email;
    document.getElementById('edit-cargo').value = cargo;
    document.getElementById('modalEdit').style.display = 'flex';
}
function fecharModal() {
    document.querySelectorAll('.modal-bg').forEach(m => m.style.display = 'none');
}
window.onclick = function(event) {
    if (event.target.classList.contains('modal-bg')) { fecharModal(); }
};
function excluirUsuario(id, nome) {
    if (confirm(`Tem certeza que deseja excluir o usuário "${nome}"? Esta ação não pode ser desfeita.`)) {
        // Enviar requisição para excluir usuário
        fetch('usuario_excluir.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'usuario_id=' + encodeURIComponent(id)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erro: ' + data.error);
            }
        })
        .catch(error => {
            alert('Erro ao excluir usuário: ' + error);
        });
    }
}
</script>

<!-- JavaScript -->
<script src="/estoquemh/js/modal.js"></script>
<script src="/estoquemh/js/responsive.js"></script>

<?php
// Fechar conexão com banco para otimização
if (isset($cone)) {
    mysqli_close($cone);
}
?>

</body>
</html>