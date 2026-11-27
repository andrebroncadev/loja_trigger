<?php
$db_host = getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: 'mysql.railway.internal');
$db_name = getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: 'railway');
$db_user = getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: 'root');
$db_pass = getenv('MYSQLPASSWORD') ?: (getenv('DB_PASS') ?: '');
$db_port = getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: '3306');

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `produtos` (
          `id` int NOT NULL AUTO_INCREMENT,
          `nome` varchar(150) NOT NULL,
          `descricao` text,
          `preco` decimal(10,2) NOT NULL,
          `quantidade` int NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `historico_alteracoes` (
          `id` int NOT NULL AUTO_INCREMENT,
          `id_produto` int NOT NULL,
          `data_alteracao` datetime DEFAULT CURRENT_TIMESTAMP,
          `campo_alterado` varchar(50) NOT NULL,
          `valor_antigo` text,
          `valor_novo` text,
          PRIMARY KEY (`id`),
          KEY `fk_produto_historico` (`id_produto`),
          CONSTRAINT `fk_produto_historico` FOREIGN KEY (`id_produto`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
    ");

    $stmtTrigger = $pdo->prepare("
        SELECT COUNT(*) FROM information_schema.TRIGGERS
        WHERE TRIGGER_SCHEMA = :schema_name AND TRIGGER_NAME = 'trg_produto_update'
    ");
    $stmtTrigger->execute([':schema_name' => $db_name]);
    if ((int) $stmtTrigger->fetchColumn() === 0) {
        $pdo->exec("
            CREATE TRIGGER `trg_produto_update` AFTER UPDATE ON `produtos` FOR EACH ROW
            BEGIN
                IF NOT (OLD.nome <=> NEW.nome) THEN
                    INSERT INTO historico_alteracoes (id_produto, data_alteracao, campo_alterado, valor_antigo, valor_novo)
                    VALUES (NEW.id, NOW(), 'nome', OLD.nome, NEW.nome);
                END IF;

                IF NOT (OLD.descricao <=> NEW.descricao) THEN
                    INSERT INTO historico_alteracoes (id_produto, data_alteracao, campo_alterado, valor_antigo, valor_novo)
                    VALUES (NEW.id, NOW(), 'descricao', OLD.descricao, NEW.descricao);
                END IF;

                IF NOT (OLD.preco <=> NEW.preco) THEN
                    INSERT INTO historico_alteracoes (id_produto, data_alteracao, campo_alterado, valor_antigo, valor_novo)
                    VALUES (NEW.id, NOW(), 'preco', OLD.preco, NEW.preco);
                END IF;

                IF NOT (OLD.quantidade <=> NEW.quantidade) THEN
                    INSERT INTO historico_alteracoes (id_produto, data_alteracao, campo_alterado, valor_antigo, valor_novo)
                    VALUES (NEW.id, NOW(), 'quantidade', OLD.quantidade, NEW.quantidade);
                END IF;
            END
        ");
    }

    $qtdProdutos = (int) $pdo->query("SELECT COUNT(*) FROM `produtos`")->fetchColumn();
    if ($qtdProdutos === 0) {
        $pdo->exec("
            INSERT INTO `produtos` (`nome`, `descricao`, `preco`, `quantidade`) VALUES
            ('Teclado Mecanico ABNT2', 'Switch tactile, retroiluminado', 289.90, 15),
            ('Mouse Sem Fio Ergonomico', 'Sensor 1600 DPI, recarregavel', 119.50, 30),
            ('Monitor 24 IPS Full HD', '75Hz, HDMI e DisplayPort', 849.00, 8),
            ('Cabo USB-C 2m', 'Trancado, suporte a 60W', 39.90, 50)
        ");
    }
} catch (PDOException $e) {
    die("Erro de conexao com o banco: " . htmlspecialchars($e->getMessage()));
}
