<?php
require_once 'config.php';

$mensagem = '';
$tipo_mensagem = 'success';

// Ações POST: Criar, Atualizar ou Excluir Produto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        if ($acao === 'criar') {
            $nome = trim($_POST['nome'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $preco = (float) str_replace(',', '.', $_POST['preco'] ?? 0);
            $quantidade = (int) ($_POST['quantidade'] ?? 0);

            if ($nome === '') {
                throw new Exception('O nome do produto é obrigatório.');
            }

            $stmt = $pdo->prepare("INSERT INTO produtos (nome, descricao, preco, quantidade) VALUES (:nome, :descricao, :preco, :quantidade)");
            $stmt->execute([
                ':nome' => $nome,
                ':descricao' => $descricao !== '' ? $descricao : null,
                ':preco' => $preco,
                ':quantidade' => $quantidade,
            ]);
            $mensagem = 'Produto cadastrado com sucesso!';
        } elseif ($acao === 'editar') {
            $id = (int) ($_POST['id'] ?? 0);
            $nome = trim($_POST['nome'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $preco = (float) str_replace(',', '.', $_POST['preco'] ?? 0);
            $quantidade = (int) ($_POST['quantidade'] ?? 0);

            if ($id <= 0 || $nome === '') {
                throw new Exception('Dados inválidos para atualização.');
            }

            // Este UPDATE dispara automaticamente a trigger `trg_produto_update` no MySQL!
            $stmt = $pdo->prepare("UPDATE produtos SET nome = :nome, descricao = :descricao, preco = :preco, quantidade = :quantidade WHERE id = :id");
            $stmt->execute([
                ':nome' => $nome,
                ':descricao' => $descricao !== '' ? $descricao : null,
                ':preco' => $preco,
                ':quantidade' => $quantidade,
                ':id' => $id,
            ]);
            $mensagem = 'Produto atualizado! A trigger `trg_produto_update` registrou as alterações no histórico.';
        } elseif ($acao === 'excluir') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $mensagem = 'Produto removido com sucesso.';
            }
        }
    } catch (Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// Busca todos os produtos
$produtos = $pdo->query("SELECT * FROM produtos ORDER BY id DESC")->fetchAll();

// Busca histórico gerado pela trigger
$historico = $pdo->query("
    SELECT h.*, p.nome AS produto_nome
    FROM historico_alteracoes h
    LEFT JOIN produtos p ON p.id = h.id_produto
    ORDER BY h.data_alteracao DESC, h.id DESC
    LIMIT 50
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loja Atividade — Sistema de Estoque & Auditoria via Trigger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            📦 Loja Atividade <span class="badge bg-primary ms-2 fw-normal">MySQL Trigger</span>
        </a>
        <div class="d-flex gap-2">
            <a href="documentacao.html" class="btn btn-outline-light btn-sm">
                📄 Documentação do Projeto
            </a>
        </div>
    </div>
</nav>

<main class="container py-4">

    <?php if ($mensagem !== ''): ?>
        <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensagem) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Formulário de Cadastro -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">
                    ➕ Novo Produto
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php">
                        <input type="hidden" name="acao" value="criar">
                        <div class="mb-3">
                            <label class="form-label">Nome *</label>
                            <input type="text" name="nome" class="form-control" placeholder="Ex: SSD NVMe 1TB" required maxlength="150">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea name="descricao" class="form-control" rows="2" placeholder="Detalhes técnicos..."></textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Preço (R$) *</label>
                                <input type="number" step="0.01" min="0" name="preco" class="form-control" placeholder="0.00" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Quantidade *</label>
                                <input type="number" min="0" name="quantidade" class="form-control" placeholder="0" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Cadastrar Produto</button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3 bg-primary-subtle">
                <div class="card-body small">
                    <strong>Como testar a Trigger:</strong><br>
                    Clique em <b>Editar</b> em qualquer produto ao lado, altere preço, nome, descrição ou quantidade e salve. O banco disparará <code>trg_produto_update</code> e preencherá a tabela de auditoria automaticamente.
                </div>
            </div>
        </div>

        <!-- Tabela de Produtos -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold">📋 Produtos Cadastrados (<?= count($produtos) ?>)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Produto</th>
                                <th>Preço</th>
                                <th>Qtd</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($produtos)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Nenhum produto cadastrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($produtos as $p): ?>
                                <tr>
                                    <td>#<?= $p['id'] ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($p['nome']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($p['descricao'] ?? 'Sem descrição') ?></small>
                                    </td>
                                    <td>R$ <?= number_format((float)$p['preco'], 2, ',', '.') ?></td>
                                    <td>
                                        <span class="badge <?= $p['quantidade'] <= 10 ? 'bg-warning text-dark' : 'bg-success' ?>">
                                            <?= (int)$p['quantidade'] ?> un
                                        </span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditar"
                                            data-id="<?= $p['id'] ?>"
                                            data-nome="<?= htmlspecialchars($p['nome'], ENT_QUOTES) ?>"
                                            data-descricao="<?= htmlspecialchars($p['descricao'] ?? '', ENT_QUOTES) ?>"
                                            data-preco="<?= $p['preco'] ?>"
                                            data-quantidade="<?= $p['quantidade'] ?>"
                                        >
                                            Editar
                                        </button>
                                        <form method="POST" action="index.php" class="d-inline" onsubmit="return confirm('Excluir este produto?');">
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabela de Auditoria (alimentada pela Trigger) -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold">🕵️ Histórico de Alterações (Gerado pela Trigger <code>trg_produto_update</code>)</span>
                    <span class="badge bg-secondary"><?= count($historico) ?> registros</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Data/Hora</th>
                                <th>Produto</th>
                                <th>Campo</th>
                                <th>Valor Antigo</th>
                                <th>Valor Novo</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($historico)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Nenhuma alteração registrada ainda. Edite um produto acima para disparar a trigger!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($historico as $h): ?>
                                <tr>
                                    <td class="text-nowrap small"><?= date('d/m/Y H:i:s', strtotime($h['data_alteracao'])) ?></td>
                                    <td>
                                        <span class="fw-semibold"><?= htmlspecialchars($h['produto_nome'] ?? 'Produto #' . $h['id_produto']) ?></span>
                                    </td>
                                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($h['campo_alterado']) ?></span></td>
                                    <td class="text-danger small text-decoration-line-through"><?= htmlspecialchars($h['valor_antigo'] ?? 'NULL') ?></td>
                                    <td class="text-success fw-semibold small"><?= htmlspecialchars($h['valor_novo'] ?? 'NULL') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal de Edição -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="index.php" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">✏️ Editar Produto (Dispara Trigger)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id" id="edit-id">
                <div class="mb-3">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="nome" id="edit-nome" class="form-control" required maxlength="150">
                </div>
                <div class="mb-3">
                    <label class="form-label">Descrição</label>
                    <textarea name="descricao" id="edit-descricao" class="form-control" rows="2"></textarea>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">Preço (R$) *</label>
                        <input type="number" step="0.01" min="0" name="preco" id="edit-preco" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Quantidade *</label>
                        <input type="number" min="0" name="quantidade" id="edit-quantidade" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<footer class="container py-4 mt-4 border-top text-center text-muted small">
    Atividade de Banco de Dados — Modelo Relacional &amp; Trigger MySQL |
    <a href="documentacao.html">Acessar Documentação Técnica</a>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modalEditar = document.getElementById('modalEditar');
modalEditar.addEventListener('show.bs.modal', event => {
    const btn = event.relatedTarget;
    document.getElementById('edit-id').value = btn.getAttribute('data-id');
    document.getElementById('edit-nome').value = btn.getAttribute('data-nome');
    document.getElementById('edit-descricao').value = btn.getAttribute('data-descricao');
    document.getElementById('edit-preco').value = btn.getAttribute('data-preco');
    document.getElementById('edit-quantidade').value = btn.getAttribute('data-quantidade');
});
</script>
</body>
</html>
