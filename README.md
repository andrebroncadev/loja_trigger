# Projeto Loja Atividade — CRUD + MySQL Trigger

## Estrutura de Arquivos
- `index.php`: Painel principal (Cadastro, Edição, Exclusão e visualização em tempo real do histórico da trigger).
- `documentacao.html`: Página de documentação técnica para avaliação do professor (acessível pelo botão no topo e no rodapé).
- `config.php`: Arquivo de conexão PDO com o MySQL.
- `database.sql`: Script completo de criação das tabelas (`produtos`, `historico_alteracoes`), da trigger (`trg_produto_update`) e dados iniciais.
- `assets/style.css`: Estilos complementares.

## Como Hospedar Rapidamente (Gratuito)
1. **InfinityFree** (ou **000webhost** / **AwardSpace** / **Hostinger**):
   - Crie uma conta gratuita e um site PHP.
   - No painel (**MySQL Databases**), crie um banco de dados e anote: `Host`, `Database Name`, `Username` e `Password`.
   - Abra o **phpMyAdmin** do painel, selecione o banco criado, vá na aba **Importar** (ou **SQL**) e execute o arquivo `database.sql`.
   - Edite o arquivo `config.php` colocando os dados do seu banco.
   - Suba todos os arquivos (`index.php`, `documentacao.html`, `config.php` e a pasta `assets/`) para a pasta `htdocs` (ou `public_html`) via Gerenciador de Arquivos ou FTP.

> **Nota sobre Trigger em Hospedagem Gratuita:** Alguns planos gratuitos bloqueiam `CREATE TRIGGER` via phpMyAdmin por permissão `SUPER`/`TRIGGER`. Se isso ocorrer no InfinityFree, use **Railway.app** (plano trial/hobby com PHP + MySQL), **AlwaysData.com** (plano gratuito de 100MB com suporte completo a Triggers e SSH/phpMyAdmin) ou **FreeSQLDatabase / Filess.io** para o banco.
