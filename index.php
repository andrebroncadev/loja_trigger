<?php
require_once 'config.php';

$aviso = '';
$erro = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        if ($acao === 'criar') {
            $nome = trim($_POST['nome'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $preco = (float) str_replace(',', '.', $_POST['preco'] ?? 0);
            $quantidade = (int) ($_POST['quantidade'] ?? 0);

            if ($nome === '') {
                throw new RuntimeException('Informe o nome do produto.');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO produtos (nome, descricao, preco, quantidade) VALUES (:nome, :descricao, :preco, :quantidade)'
            );
            $stmt->execute([
                ':nome' => $nome,
                ':descricao' => $descricao !== '' ? $descricao : null,
                ':preco' => $preco,
                ':quantidade' => $quantidade,
            ]);
            $aviso = 'Registro inserido em produtos.';
        } elseif ($acao === 'editar') {
            $id = (int) ($_POST['id'] ?? 0);
            $nome = trim($_POST['nome'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $preco = (float) str_replace(',', '.', $_POST['preco'] ?? 0);
            $quantidade = (int) ($_POST['quantidade'] ?? 0);

            if ($id <= 0 || $nome === '') {
                throw new RuntimeException('Dados invalidos para atualizacao.');
            }

            $stmt = $pdo->prepare(
                'UPDATE produtos SET nome = :nome, descricao = :descricao, preco = :preco, quantidade = :quantidade WHERE id = :id'
            );
            $stmt->execute([
                ':nome' => $nome,
                ':descricao' => $descricao !== '' ? $descricao : null,
                ':preco' => $preco,
                ':quantidade' => $quantidade,
                ':id' => $id,
            ]);
            $aviso = 'Produto atualizado. Alteracoes monitoradas pela trigger trg_produto_update.';
        } elseif ($acao === 'excluir') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare('DELETE FROM produtos WHERE id = :id');
                $stmt->execute([':id' => $id]);
                $aviso = 'Produto excluido.';
            }
        }
    } catch (Throwable $e) {
        $aviso = 'Falha na operacao: ' . $e->getMessage();
        $erro = true;
    }
}

$produtos = $pdo->query('SELECT * FROM produtos ORDER BY id DESC')->fetchAll();

$historico = $pdo->query(
    'SELECT h.*, p.nome AS produto_nome
     FROM historico_alteracoes h
     LEFT JOIN produtos p ON p.id = h.id_produto
     ORDER BY h.data_alteracao DESC, h.id DESC
     LIMIT 60'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Controle de Produtos e Auditoria (Trigger)</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <header class="topbar">
    <div class="wrap topbar-inner">
      <span class="brand">Controle de Produtos &middot; Trigger MySQL</span>
      <a class="doc-link" href="documentacao.html">Documentacao do trabalho</a>
    </div>
  </header>

  <main class="wrap">
    <?php if ($aviso !== ''): ?>
      <div class="alert <?= $erro ? 'alert-err' : 'alert-ok' ?>">
        <?= htmlspecialchars($aviso) ?>
      </div>
    <?php endif; ?>

    <div class="layout-grid">
      <aside>
        <section class="panel">
          <div class="panel-head">Novo produto</div>
          <div class="panel-body">
            <form method="post" action="index.php">
              <input type="hidden" name="acao" value="criar">
              <div class="field">
                <label for="nome">Nome</label>
                <input id="nome" name="nome" type="text" maxlength="150" required>
              </div>
              <div class="field">
                <label for="descricao">Descricao</label>
                <textarea id="descricao" name="descricao" rows="2"></textarea>
              </div>
              <div class="two-cols">
                <div class="field">
                  <label for="preco">Preco (R$)</label>
                  <input id="preco" name="preco" type="number" step="0.01" min="0" required>
                </div>
                <div class="field">
                  <label for="quantidade">Quantidade</label>
                  <input id="quantidade" name="quantidade" type="number" min="0" required>
                </div>
              </div>
              <button class="btn btn-primary btn-full" type="submit">Salvar produto</button>
            </form>
          </div>
        </section>

        <section class="panel">
          <div class="panel-head">Como testar a trigger</div>
          <div class="panel-body muted">
            Edite qualquer item ao lado e salve. A trigger <code>trg_produto_update</code> compara os valores antigos e novos no banco e grava cada coluna alterada em <code>historico_alteracoes</code>.
          </div>
        </section>

        <section class="panel">
          <div class="panel-head">Integrantes</div>
          <div class="panel-body">
            <ul style="margin:0;padding-left:18px;font-size:13px;">
              <li>André Bronca</li>
              <li>Guilherme Amâncio Hashish</li>
              <li>Luiza Moraes Moura</li>
              <li>Matheus Venâncio</li>
              <li>Ruan Cardoso</li>
            </ul>
          </div>
        </section>
      </aside>

      <div>
        <section class="panel">
          <div class="panel-head">
            <span>Tabela: produtos</span>
            <span class="muted"><?= count($produtos) ?> itens</span>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Produto</th>
                  <th class="num">Preco</th>
                  <th class="num">Qtd</th>
                  <th class="actions-col">Acoes</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$produtos): ?>
                  <tr><td colspan="5" class="muted">Sem registros na tabela produtos.</td></tr>
                <?php else: ?>
                  <?php foreach ($produtos as $item): ?>
                    <tr>
                      <td><?= (int) $item['id'] ?></td>
                      <td>
                        <div><strong><?= htmlspecialchars($item['nome']) ?></strong></div>
                        <?php if ($item['descricao'] !== null && $item['descricao'] !== ''): ?>
                          <div class="muted"><?= htmlspecialchars($item['descricao']) ?></div>
                        <?php endif; ?>
                      </td>
                      <td class="num">R$ <?= number_format((float) $item['preco'], 2, ',', '.') ?></td>
                      <td class="num">
                        <?php $qtd = (int) $item['quantidade']; ?>
                        <span class="tag <?= $qtd <= 10 ? 'tag-low' : 'tag-ok' ?>"><?= $qtd ?></span>
                      </td>
                      <td class="actions-col">
                        <button
                          type="button"
                          class="btn btn-ghost btn-sm js-editar"
                          data-id="<?= (int) $item['id'] ?>"
                          data-nome="<?= htmlspecialchars($item['nome'], ENT_QUOTES) ?>"
                          data-descricao="<?= htmlspecialchars($item['descricao'] ?? '', ENT_QUOTES) ?>"
                          data-preco="<?= htmlspecialchars((string) $item['preco'], ENT_QUOTES) ?>"
                          data-quantidade="<?= (int) $item['quantidade'] ?>"
                        >Editar</button>
                        <form method="post" action="index.php" style="display:inline" onsubmit="return confirm('Excluir este produto?');">
                          <input type="hidden" name="acao" value="excluir">
                          <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                          <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="panel">
          <div class="panel-head">
            <span>Tabela: historico_alteracoes (via trigger)</span>
            <span class="muted"><?= count($historico) ?> eventos</span>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Data / Hora</th>
                  <th>Produto</th>
                  <th>Campo</th>
                  <th>Antes</th>
                  <th>Depois</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$historico): ?>
                  <tr><td colspan="5" class="muted">Nenhuma alteracao gravada ainda.</td></tr>
                <?php else: ?>
                  <?php foreach ($historico as $log): ?>
                    <tr>
                      <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($log['data_alteracao']))) ?></td>
                      <td><?= htmlspecialchars($log['produto_nome'] ?? ('ID ' . $log['id_produto'])) ?></td>
                      <td><span class="tag"><?= htmlspecialchars($log['campo_alterado']) ?></span></td>
                      <td class="old-val"><?= htmlspecialchars($log['valor_antigo'] ?? 'NULL') ?></td>
                      <td class="new-val"><?= htmlspecialchars($log['valor_novo'] ?? 'NULL') ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </div>
  </main>

  <dialog id="dlgEditar">
    <form method="post" action="index.php">
      <div class="dialog-head">
        <span>Editar produto</span>
        <button type="button" class="btn btn-ghost btn-sm" id="btnFecharDlg">X</button>
      </div>
      <div class="dialog-body">
        <input type="hidden" name="acao" value="editar">
        <input type="hidden" name="id" id="editId">
        <div class="field">
          <label for="editNome">Nome</label>
          <input id="editNome" name="nome" type="text" maxlength="150" required>
        </div>
        <div class="field">
          <label for="editDescricao">Descricao</label>
          <textarea id="editDescricao" name="descricao" rows="2"></textarea>
        </div>
        <div class="two-cols">
          <div class="field">
            <label for="editPreco">Preco (R$)</label>
            <input id="editPreco" name="preco" type="number" step="0.01" min="0" required>
          </div>
          <div class="field">
            <label for="editQuantidade">Quantidade</label>
            <input id="editQuantidade" name="quantidade" type="number" min="0" required>
          </div>
        </div>
      </div>
      <div class="dialog-foot">
        <button type="button" class="btn btn-ghost btn-sm" id="btnCancelarDlg">Cancelar</button>
        <button type="submit" class="btn btn-primary btn-sm">Gravar alteracao</button>
      </div>
    </form>
  </dialog>

  <footer class="wrap">
    <span>André Bronca &middot; Guilherme Amâncio Hashish &middot; Luiza Moraes Moura &middot; Matheus Venâncio &middot; Ruan Cardoso</span>
    <a href="documentacao.html">Documentacao do trabalho</a>
  </footer>

  <script>
    const dlg = document.getElementById('dlgEditar');
    const fechar = () => dlg.close();

    document.getElementById('btnFecharDlg').addEventListener('click', fechar);
    document.getElementById('btnCancelarDlg').addEventListener('click', fechar);

    document.querySelectorAll('.js-editar').forEach((btn) => {
      btn.addEventListener('click', () => {
        document.getElementById('editId').value = btn.dataset.id;
        document.getElementById('editNome').value = btn.dataset.nome;
        document.getElementById('editDescricao').value = btn.dataset.descricao;
        document.getElementById('editPreco').value = btn.dataset.preco;
        document.getElementById('editQuantidade').value = btn.dataset.quantidade;
        dlg.showModal();
      });
    });
  </script>
</body>
</html>
