-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 28/01/2026 às 14:05
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `estoque`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `parent_id` int(11) DEFAULT NULL,
  `quantidade_minima` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dados reais removidos; exemplos ficticios para a tabela `categorias`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `itens`
--

CREATE TABLE `itens` (
  `id` int(11) NOT NULL,
  `nome` varchar(120) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `localizacao` varchar(120) DEFAULT NULL,
  `foto_loc` longblob NOT NULL,
  `quantidade` int(11) DEFAULT 0,
  `quantidade_minima` int(11) DEFAULT 1,
  `status` enum('Normal','Baixo','Zerado') GENERATED ALWAYS AS (case when `quantidade` = 0 then 'Zerado' when `quantidade` <= `quantidade_minima` then 'Baixo' else 'Normal' end) STORED,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `observacao` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dados reais removidos; exemplos ficticios para a tabela `itens`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_em_uso`
--

CREATE TABLE `itens_em_uso` (
  `id` int(11) NOT NULL,
  `patrimonio` varchar(50) DEFAULT NULL,
  `nome` varchar(120) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `setor_id` int(11) NOT NULL,
  `foto_loc` longblob DEFAULT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 1,
  `data_saida` timestamp NOT NULL DEFAULT current_timestamp(),
  `observacao` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dados reais removidos; exemplos ficticios para a tabela `itens_em_uso`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `nome` varchar(120) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dados reais removidos; exemplos ficticios para a tabela `locations`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `acao` varchar(255) DEFAULT NULL,
  `detalhes` text DEFAULT NULL,
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dados reais removidos; exemplos ficticios para a tabela `logs`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes`
--

CREATE TABLE `movimentacoes` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('Entrada','Saída') NOT NULL,
  `quantidade` int(11) NOT NULL,
  `observacao` text DEFAULT NULL,
  `data_movimentacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dados reais removidos; exemplos ficticios para a tabela `movimentacoes`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes_destinos`
--

CREATE TABLE `movimentacoes_destinos` (
  `id` int(11) NOT NULL,
  `movimentacao_id` int(11) NOT NULL,
  `setor_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dados reais removidos; exemplos ficticios para a tabela `movimentacoes_destinos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `setores`
--

CREATE TABLE `setores` (
  `id` int(11) NOT NULL,
  `andar` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dados reais removidos; exemplos ficticios para a tabela `setores`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `foto` longblob DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `setor_id` int(11) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo` enum('admin','usuario') NOT NULL DEFAULT 'usuario'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dados reais removidos; exemplos ficticios para a tabela `usuarios`
--


--
--
-- Dados ficticios de exemplo
-- Senha dos usuarios de exemplo: admin123
--

INSERT INTO categorias (id, nome, ativo, criado_em, parent_id, quantidade_minima) VALUES
(1, 'Perifericos', 1, '2026-01-01 09:00:00', NULL, 2),
(2, 'Teclado', 1, '2026-01-01 09:05:00', 1, 1);

INSERT INTO setores (id, andar, nome, descricao, ativo) VALUES
(1, 1, 'Tecnologia da Informacao', 'Setor responsavel por suporte e infraestrutura', 1),
(2, 2, 'Administrativo', 'Area administrativa de exemplo', 1);

INSERT INTO usuarios (id, nome, email, senha, foto, cargo, setor_id, ativo, criado_em, tipo) VALUES
(1, 'Administrador Exemplo', 'admin@example.com', '$2y$10$EGv4EK7sH0bbf.Tc.v5gWO0xF421VCuzFUn5GtPnHEPXBXYMhMf4K', NULL, 'Administrador de TI', 1, 1, '2026-01-01 09:10:00', 'admin'),
(2, 'Usuario Exemplo', 'usuario@example.com', '$2y$10$EGv4EK7sH0bbf.Tc.v5gWO0xF421VCuzFUn5GtPnHEPXBXYMhMf4K', NULL, 'Analista de Suporte', 1, 1, '2026-01-01 09:15:00', 'usuario');

INSERT INTO locations (id, nome, parent_id, descricao, ativo, criado_em, atualizado_em) VALUES
(1, 'Almoxarifado TI', NULL, 'Local principal de armazenamento', 1, '2026-01-01 09:20:00', '2026-01-01 09:20:00'),
(2, 'Prateleira A', 1, 'Prateleira de perifericos', 1, '2026-01-01 09:25:00', '2026-01-01 09:25:00');

INSERT INTO itens (id, nome, categoria_id, localizacao, foto_loc, quantidade, quantidade_minima, criado_em, observacao) VALUES
(1, 'Teclado USB Exemplo', 2, 'Almoxarifado TI > Prateleira A', '', 10, 2, '2026-01-01 09:30:00', 'Item ficticio para demonstracao'),
(2, 'Mouse Wireless Exemplo', 1, 'Almoxarifado TI > Prateleira A', '', 1, 2, '2026-01-01 09:35:00', 'Exemplo de item em estoque baixo');

INSERT INTO itens_em_uso (id, patrimonio, nome, categoria_id, setor_id, foto_loc, quantidade, data_saida, observacao, ativo) VALUES
(1, 'PAT-0001', 'Notebook Exemplo', 1, 1, NULL, 1, '2026-01-01 10:00:00', 'Patrimonio ficticio em uso', 1),
(2, 'PAT-0002', 'Monitor Exemplo', 1, 2, NULL, 1, '2026-01-01 10:10:00', 'Patrimonio ficticio desativado', 0);

INSERT INTO logs (id, usuario_id, acao, detalhes, data_hora) VALUES
(1, 1, 'CRIACAO DE ITEM', 'Item ficticio criado para demonstracao', '2026-01-01 10:20:00'),
(2, 1, 'MOVIMENTACAO', 'Entrada ficticia de estoque', '2026-01-01 10:25:00');

INSERT INTO movimentacoes (id, item_id, usuario_id, tipo, quantidade, observacao, data_movimentacao) VALUES
(1, 1, 1, 'Entrada', 10, 'Carga inicial ficticia', '2026-01-01 10:30:00'),
(2, 2, 1, 'Saída', 1, 'Saida ficticia para demonstracao', '2026-01-01 10:35:00');

INSERT INTO movimentacoes_destinos (id, movimentacao_id, setor_id, quantidade) VALUES
(1, 2, 1, 1),
(2, 2, 2, 1);

-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`),
  ADD KEY `fk_categoria_pai` (`parent_id`);

--
-- Índices de tabela `itens`
--
ALTER TABLE `itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Índices de tabela `itens_em_uso`
--
ALTER TABLE `itens_em_uso`
  ADD PRIMARY KEY (`id`),
  ADD KEY `setor_id` (`setor_id`);

--
-- Índices de tabela `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_ativo` (`ativo`);

--
-- Índices de tabela `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `movimentacoes`
--
ALTER TABLE `movimentacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `movimentacoes_destinos`
--
ALTER TABLE `movimentacoes_destinos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movimentacao_id` (`movimentacao_id`),
  ADD KEY `setor_id` (`setor_id`);

--
-- Índices de tabela `setores`
--
ALTER TABLE `setores`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `idx_email` (`email`),
  ADD KEY `setor_id` (`setor_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `itens`
--
ALTER TABLE `itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `itens_em_uso`
--
ALTER TABLE `itens_em_uso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `movimentacoes`
--
ALTER TABLE `movimentacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `movimentacoes_destinos`
--
ALTER TABLE `movimentacoes_destinos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `setores`
--
ALTER TABLE `setores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `categorias`
--
ALTER TABLE `categorias`
  ADD CONSTRAINT `fk_categoria_pai` FOREIGN KEY (`parent_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `itens`
--
ALTER TABLE `itens`
  ADD CONSTRAINT `itens_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `itens_em_uso`
--
ALTER TABLE `itens_em_uso`
  ADD CONSTRAINT `itens_em_uso_ibfk_1` FOREIGN KEY (`setor_id`) REFERENCES `setores` (`id`);

--
-- Restrições para tabelas `locations`
--
ALTER TABLE `locations`
  ADD CONSTRAINT `fk_locations_parent` FOREIGN KEY (`parent_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `movimentacoes`
--
ALTER TABLE `movimentacoes`
  ADD CONSTRAINT `movimentacoes_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `itens` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `movimentacoes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `movimentacoes_destinos`
--
ALTER TABLE `movimentacoes_destinos`
  ADD CONSTRAINT `movimentacoes_destinos_ibfk_1` FOREIGN KEY (`movimentacao_id`) REFERENCES `movimentacoes` (`id`),
  ADD CONSTRAINT `movimentacoes_destinos_ibfk_2` FOREIGN KEY (`setor_id`) REFERENCES `setores` (`id`);

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`setor_id`) REFERENCES `setores` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
