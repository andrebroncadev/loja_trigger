-- Banco de Dados: loja_atividade
-- Sistema de Gestão de Produtos com Auditoria via Trigger (MySQL 8+)

CREATE DATABASE IF NOT EXISTS `loja_atividade`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_0900_ai_ci;

USE `loja_atividade`;

DROP TABLE IF EXISTS `historico_alteracoes`;
DROP TABLE IF EXISTS `produtos`;

CREATE TABLE `produtos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `descricao` text,
  `preco` decimal(10,2) NOT NULL,
  `quantidade` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `historico_alteracoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_produto` int NOT NULL,
  `data_alteracao` datetime DEFAULT CURRENT_TIMESTAMP,
  `campo_alterado` varchar(50) NOT NULL,
  `valor_antigo` text,
  `valor_novo` text,
  PRIMARY KEY (`id`),
  KEY `fk_produto_historico` (`id_produto`),
  CONSTRAINT `fk_produto_historico` FOREIGN KEY (`id_produto`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DELIMITER ;;
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
END;;
DELIMITER ;

-- Dados iniciais para demonstração
INSERT INTO `produtos` (`nome`, `descricao`, `preco`, `quantidade`) VALUES
('Teclado Mecânico ABNT2', 'Switch tactile, retroiluminado', 289.90, 15),
('Mouse Sem Fio Ergonômico', 'Sensor 1600 DPI, recarregável', 119.50, 30),
('Monitor 24 IPS Full HD', '75Hz, HDMI e DisplayPort', 849.00, 8),
('Cabo USB-C 2m', 'Trançado, suporte a 60W', 39.90, 50);
