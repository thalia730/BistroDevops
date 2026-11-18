
<?php
require_once __DIR__ . '/config/database.php';

function e($valor): string {
    return htmlspecialchars(
        (string)($valor ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

$erro = '';
$sucesso = '';
$editando = null;

// CADASTRAR, EDITAR OU EXCLUIR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = (string)($_POST['acao'] ?? '');

    try {
        if ($acao === 'salvar') {
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT) ?: null;

            $nome = trim((string)($_POST['nome'] ?? ''));
            $telefone = trim((string)($_POST['telefone'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $endereco = trim((string)($_POST['endereco'] ?? ''));

            if ($nome === '' || strlen($nome) > 120) {
                $erro = 'Informe o nome do cliente (até 120 caracteres).';

            } elseif (strlen($telefone) > 30) {
                $erro = 'O telefone deve ter até 30 caracteres.';

            } elseif (strlen($email) > 160 || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
                $erro = 'Informe um e-mail válido.';

            } elseif (strlen($endereco) > 255) {
                $erro = 'O endereço deve ter até 255 caracteres.';

            } else {
                if ($id) {
                    $sql = 'UPDATE clientes
                            SET nome = ?, telefone = ?, email = ?, endereco = ?
                            WHERE id = ?';

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $nome,
                        $telefone !== '' ? $telefone : null,
                        $email !== '' ? $email : null,
                        $endereco !== '' ? $endereco : null,
                        $id
                    ]);

                    header('Location: clientes.php?atualizado=1');
                    exit;

                } else {
                    $sql = 'INSERT INTO clientes
                            (nome, telefone, email, endereco)
                            VALUES (?, ?, ?, ?)';

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $nome,
                        $telefone !== '' ? $telefone : null,
                        $email !== '' ? $email : null,
                        $endereco !== '' ? $endereco : null
                    ]);

                    header('Location: clientes.php?cadastrado=1');
                    exit;
                }
            }

        } elseif ($acao === 'excluir') {
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

            if ($id) {
                $stmt = $pdo->prepare('DELETE FROM clientes WHERE id = ?');
                $stmt->execute([$id]);
            }

            header('Location: clientes.php?excluido=1');
            exit;
        }

    } catch (Throwable $ex) {
        error_log('Erro clientes: ' . $ex->getMessage());
        $erro = 'Não foi possível concluir a operação.';
    }
}

// BUSCAR CLIENTE PARA EDIÇÃO
if (isset($_GET['editar'])) {
    $id = filter_var($_GET['editar'], FILTER_VALIDATE_INT);

    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM clientes WHERE id = ?');
        $stmt->execute([$id]);
        $editando = $stmt->fetch() ?: null;
    }
}

// MENSAGENS
if (isset($_GET['cadastrado'])) {
    $sucesso = 'Cliente cadastrado com sucesso.';
}

if (isset($_GET['atualizado'])) {
    $sucesso = 'Dados do cliente atualizados.';
}

if (isset($_GET['excluido'])) {
    $sucesso = 'Cliente excluído com sucesso.';
}

// LISTAR CLIENTES
$clientes = $pdo->query(
    'SELECT * FROM clientes ORDER BY nome ASC'
)->fetchAll();

?>

<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clientes | Bistrô Sabor da Vila</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<header class="topbar">
    <div class="wrap topbar-inner">

        <a class="brand" href="index.php">
            <span class="brand-icon">SV</span>
            <span>
                Bistrô <b>Sabor da Vila</b>
                <small>PAINEL DE COMANDAS</small>
            </span>
        </a>

        <nav>
            <a href="index.php">Comandas</a>
            <a href="produtos.php">Produtos</a>
            <a class="nav-active" href="clientes.php">Clientes</a>
        </nav>

    </div>
</header>

<main class="wrap">

    <section class="hero">
        <div>
            <span class="eyebrow">CADASTRO E ORGANIZAÇÃO</span>
            <h1>Clientes</h1>
            <p>Cadastre, consulte, edite e exclua os clientes do restaurante.</p>
        </div>

        <div class="hero-mark">♙</div>
    </section>

    <?php if ($erro): ?>
        <div class="alert error"><?= e($erro) ?></div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="alert success"><?= e($sucesso) ?></div>
    <?php endif; ?>

    <section class="product-admin-grid">

        <!-- FORMULÁRIO -->
        <div class="panel">

            <div class="panel-heading">
                <div>
                    <span class="step">
                        <?= $editando ? 'EDITAR CLIENTE' : 'NOVO CADASTRO' ?>
                    </span>

                    <h2>
                        <?= $editando ? 'Editar cliente' : 'Adicionar cliente' ?>
                    </h2>
                </div>
            </div>

            <form method="post" class="admin-form">

                <input type="hidden" name="acao" value="salvar">

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int)($editando['id'] ?? 0) ?>"
                >

                <label>
                    Nome completo
                    <input
                        type="text"
                        name="nome"
                        required
                        maxlength="120"
                        value="<?= e($editando['nome'] ?? '') ?>"
                        placeholder="Ex.: Maria Silva"
                    >
                </label>

                <label>
                    Telefone
                    <input
                        type="text"
                        name="telefone"
                        maxlength="30"
                        value="<?= e($editando['telefone'] ?? '') ?>"
                        placeholder="Ex.: (49) 99999-9999"
                    >
                </label>

                <label>
                    E-mail
                    <input
                        type="email"
                        name="email"
                        maxlength="160"
                        value="<?= e($editando['email'] ?? '') ?>"
                        placeholder="Ex.: maria@email.com"
                    >
                </label>

                <label>
                    Endereço
                    <textarea
                        name="endereco"
                        rows="3"
                        maxlength="255"
                        placeholder="Rua, número e bairro"
                    ><?= e($editando['endereco'] ?? '') ?></textarea>
                </label>

                <button class="button primary full" type="submit">
                    <?= $editando ? 'Salvar alterações' : 'Cadastrar cliente' ?>
                </button>

                <?php if ($editando): ?>
                    <a class="cancel-link" href="clientes.php">
                        Cancelar edição
                    </a>
                <?php endif; ?>

            </form>

        </div>

        <!-- LISTAGEM -->
        <div class="panel inventory">

            <div class="section-heading">
                <div>
                    <span class="step">CADASTROS</span>
                    <h2>Clientes cadastrados</h2>
                </div>

                <span class="count">
                    <?= count($clientes) ?> clientes
                </span>
            </div>

            <?php if (!$clientes): ?>

                <div class="empty">
                    Nenhum cliente cadastrado ainda.
                </div>

            <?php else: ?>

                <div class="table-wrap">

                    <table>
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Telefone</th>
                                <th>E-mail</th>
                                <th>Ações</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php foreach ($clientes as $cliente): ?>

                            <tr>
                                <td>
                                    <strong><?= e($cliente['nome']) ?></strong>

                                    <?php if ($cliente['endereco']): ?>
                                        <small class="desc-cell">
                                            <?= e($cliente['endereco']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= e($cliente['telefone'] ?: '-') ?>
                                </td>

                                <td>
                                    <?= e($cliente['email'] ?: '-') ?>
                                </td>

                                <td>
                                    <div class="table-actions">

                                        <a
                                            class="small-link"
                                            href="clientes.php?editar=<?= (int)$cliente['id'] ?>"
                                        >
                                            Editar
                                        </a>

                                        <form
                                            method="post"
                                            onsubmit="return confirm('Deseja realmente excluir este cliente?')"
                                        >
                                            <input
                                                type="hidden"
                                                name="acao"
                                                value="excluir"
                                            >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int)$cliente['id'] ?>"
                                            >

                                            <button
                                                class="text-button"
                                                type="submit"
                                            >
                                                Excluir
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                        </tbody>
                    </table>

                </div>

            <?php endif; ?>

        </div>

    </section>

    <footer>
        Projeto didático • UC2 — Práticas DevOps em Projetos Web
        <span>
            <a href="index.php">Voltar às comandas</a>
        </span>
    </footer>

</main>

</body>
</html>
