<?php
// Conexão PDO. Usa as variáveis do serviço MySQL do Railway (MYSQL*) e,
// como alternativa, as antigas DB_* ou valores locais.
$db_host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'loja_atividade';
$db_user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '';
$db_port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . htmlspecialchars($e->getMessage()));
}

// Cria tabelas e a trigger trg_produto_update na primeira execução.
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS produtos (
      id int NOT NULL AUTO_INCREMENT,
      nome varchar(150) NOT NULL,
      descricao text,
      preco decimal(10,2) NOT NULL,
      quantidade int NOT NULL,
      PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS historico_alteracoes (
      id int NOT NULL AUTO_INCREMENT,
      id_produto int NOT NULL,
      data_alteracao datetime DEFAULT CURRENT_TIMESTAMP,
      campo_alterado varchar(50) NOT NULL,
      valor_antigo text,
      valor_novo text,
      PRIMARY KEY (id),
      KEY fk_produto_historico (id_produto),
      CONSTRAINT fk_produto_historico FOREIGN KEY (id_produto) REFERENCES produtos (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = 'trg_produto_update'");
    $st->execute();
    if ((int)$st->fetchColumn() === 0) {
        $log = function ($campo) {
            return "IF NOT (OLD.$campo <=> NEW.$campo) THEN
                INSERT INTO historico_alteracoes (id_produto, data_alteracao, campo_alterado, valor_antigo, valor_novo)
                VALUES (NEW.id, NOW(), '$campo', OLD.$campo, NEW.$campo);
            END IF;";
        };
        $pdo->exec("CREATE TRIGGER trg_produto_update AFTER UPDATE ON produtos FOR EACH ROW BEGIN "
            . $log('nome') . $log('descricao') . $log('preco') . $log('quantidade') . " END");
    }
    if ((int)$pdo->query("SELECT COUNT(*) FROM produtos")->fetchColumn() === 0) {
        $pdo->exec("INSERT INTO produtos (nome, descricao, preco, quantidade) VALUES
          ('Teclado Mecânico ABNT2', 'Switch tactile, retroiluminado', 289.90, 15),
          ('Mouse Sem Fio Ergonômico', 'Sensor 1600 DPI, recarregável', 119.50, 30),
          ('Monitor 24 IPS Full HD', '75Hz, HDMI e DisplayPort', 849.00, 8),
          ('Cabo USB-C 2m', 'Trançado, suporte a 60W', 39.90, 50)");
    }
} catch (PDOException $e) {
    die("Erro ao preparar o banco de dados: " . htmlspecialchars($e->getMessage()));
}
