<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

function e($valor): string {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function brl($valor): string {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

// Recebe o ID da comanda pela URL.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Comanda inválida. Volte ao painel e tente novamente.');
}

// Busca a comanda.
$stmt = $pdo->prepare('SELECT * FROM comandas WHERE id = ?');
$stmt->execute([$id]);
$comanda = $stmt->fetch();

if (!$comanda) {
    http_response_code(404);
    exit('Comanda não encontrada.');
}

// Busca os itens registrados na comanda.
$stmtItens = $pdo->prepare(
    'SELECT nome_produto, quantidade, preco_unitario
     FROM itens_comanda
     WHERE comanda_id = ?
     ORDER BY id'
);
$stmtItens->execute([$id]);
$itens = $stmtItens->fetchAll();

$total = 0;
$totalUnidades = 0;

foreach ($itens as $item) {
    $total += (int)$item['quantidade'] * (float)$item['preco_unitario'];
    $totalUnidades += (int)$item['quantidade'];
}

$dataAbertura = date('d/m/Y H:i', strtotime($comanda['data_abertura']));
$status = $comanda['status'] === 'entregue' ? 'Entregue' : 'Em preparo';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprovante da comanda #<?= (int)$comanda['numero'] ?> | Bistrô Sabor da Vila</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .receipt-page { max-width: 760px; margin: 35px auto; }
        .receipt-paper {
            background: #fff;
            border: 1px solid #dfe5df;
            border-radius: 12px;
            padding: 38px;
            box-shadow: 0 8px 28px #17271f12;
        }
        .receipt-header { text-align: center; border-bottom: 2px dashed #cbd5cc; padding-bottom: 20px; }
        .receipt-header h1 { font-size: 1.8rem; margin: 0 0 5px; }
        .receipt-header p { color: #718078; margin: 3px 0; }
        .receipt-title { text-align: center; margin: 22px 0; font-weight: 900; letter-spacing: .12em; }
        .receipt-meta { display: flex; justify-content: space-between; gap: 15px; flex-wrap: wrap; padding: 12px 0; border-top: 1px solid #e1e8e2; border-bottom: 1px solid #e1e8e2; font-size: .9rem; }
        .receipt-table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: .9rem; }
        .receipt-table th, .receipt-table td { padding: 11px 8px; border-bottom: 1px solid #e1e8e2; text-align: left; }
        .receipt-table th { background: #f4f7f4; color: #536158; }
        .receipt-table .right { text-align: right; white-space: nowrap; }
        .receipt-grand-total { display: flex; justify-content: space-between; align-items: center; border-top: 2px solid #19251f; padding-top: 15px; font-size: 1.15rem; font-weight: 900; }
        .receipt-observation { margin-top: 20px; padding: 12px; background: #fff8ed; border-left: 3px solid #d7a46d; font-size: .88rem; overflow-wrap: anywhere; }
        .receipt-disclaimer { margin-top: 25px; padding-top: 15px; border-top: 1px dashed #cbd5cc; color: #718078; text-align: center; font-size: .75rem; }
        .receipt-actions { display: flex; justify-content: center; gap: 10px; margin: 20px 0; flex-wrap: wrap; }
        .receipt-action { margin-top: 10px; }
        @media print {
            @page { size: A4; margin: 14mm; }
            body { background: #fff !important; }
            .receipt-page { width: 100%; max-width: none; margin: 0; }
            .receipt-paper { border: 0; border-radius: 0; box-shadow: none; padding: 0; }
            .receipt-actions, .topbar, footer { display: none !important; }
            .receipt-table { page-break-inside: auto; }
            .receipt-table tr { page-break-inside: avoid; }
        }
        @media (max-width: 560px) {
            .receipt-page { margin: 15px auto; }
            .receipt-paper { padding: 20px 15px; }
            .receipt-meta { flex-direction: column; gap: 5px; }
        }
    </style>
</head>
<body>
<header class="topbar">
    <div class="wrap topbar-inner">
        <a class="brand" href="index.php">
            <span class="brand-icon">SV</span>
            <span>Bistrô <b>Sabor da Vila</b><small>PAINEL DE COMANDAS</small></span>
        </a>
        <nav><a href="index.php">Voltar às comandas</a></nav>
    </div>
</header>

<main class="wrap receipt-page">
    <div class="receipt-actions">
        <button class="button primary" type="button" onclick="window.print()">Imprimir / Salvar em PDF</button>
        <a class="button secondary" href="index.php">Voltar às comandas</a>
    </div>

    <section class="receipt-paper">
        <div class="receipt-header">
            <h1>Bistrô Sabor da Vila</h1>
            <p>Obrigado pela preferência!</p>
        </div>

        <div class="receipt-title">COMPROVANTE DE VENDA</div>

        <div class="receipt-meta">
            <span><strong>Comanda:</strong> #<?= (int)$comanda['numero'] ?></span>
            <span><strong>Data:</strong> <?= e($dataAbertura) ?></span>
            <span><strong>Status:</strong> <?= e($status) ?></span>
        </div>

        <?php if (!$itens): ?>
            <p class="empty">Esta comanda não possui itens registrados.</p>
        <?php else: ?>
            <table class="receipt-table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th class="right">Qtd.</th>
                        <th class="right">Valor unit.</th>
                        <th class="right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $item): ?>
                        <?php $subtotal = (int)$item['quantidade'] * (float)$item['preco_unitario']; ?>
                        <tr>
                            <td><?= e($item['nome_produto']) ?></td>
                            <td class="right"><?= (int)$item['quantidade'] ?></td>
                            <td class="right"><?= brl($item['preco_unitario']) ?></td>
                            <td class="right"><?= brl($subtotal) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="receipt-grand-total">
            <span>Total (<?= $totalUnidades ?> item(ns))</span>
            <span><?= brl($total) ?></span>
        </div>

        <?php if (!empty($comanda['observacao'])): ?>
            <div class="receipt-observation">
                <strong>Observação:</strong><br>
                <?= nl2br(e($comanda['observacao'])) ?>
            </div>
        <?php endif; ?>

        <div class="receipt-disclaimer">
            Documento não fiscal — comprovante de venda para fins demonstrativos.<br>
            Este documento não substitui uma NF-e ou NFC-e autorizada pela SEFAZ.
        </div>
    </section>
</main>
</body>
</html>
