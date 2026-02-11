-- SmartPark v9 Database Dump
-- Generated: 2026-02-10 11:43:42

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+02:00";

-- --------------------------------------------------------

-- Table structure for table `estacionamentos`

DROP TABLE IF EXISTS `estacionamentos`;
CREATE TABLE `estacionamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `endereco` text NOT NULL,
  `capacidade_total` int(11) NOT NULL,
  `vagas_disponiveis` int(11) NOT NULL,
  `preco_hora` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `estacionamentos`

INSERT INTO `estacionamentos` (`id`, `nome`, `endereco`, `capacidade_total`, `vagas_disponiveis`, `preco_hora`) VALUES
('1', 'Parque Rivas', 'Avenida Principal, 1000 - Centro', '50', '50', '10.00');

-- --------------------------------------------------------

-- Table structure for table `logs`

DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `acao` varchar(255) NOT NULL,
  `data` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_data` (`data`),
  CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `logs`

INSERT INTO `logs` (`id`, `usuario_id`, `acao`, `data`) VALUES
('1', '2', 'Conta e veículo registrados', '2026-02-10 12:36:25'),
('2', '2', 'Login realizado', '2026-02-10 12:36:26');

-- --------------------------------------------------------

-- Table structure for table `multas`

DROP TABLE IF EXISTS `multas`;
CREATE TABLE `multas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reserva_id` int(11) NOT NULL,
  `valor_multa` decimal(10,2) NOT NULL,
  `resolvida` tinyint(1) DEFAULT 0,
  `data_resolucao` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reserva_multa` (`reserva_id`),
  CONSTRAINT `multas_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `multas`

-- --------------------------------------------------------

-- Table structure for table `pagamentos`

DROP TABLE IF EXISTS `pagamentos`;
CREATE TABLE `pagamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reserva_id` int(11) NOT NULL,
  `metodo` enum('cartao','pix','boleto','mpesa','emola') NOT NULL,
  `status` enum('pendente','pago','falha') NOT NULL DEFAULT 'pendente',
  `valor` decimal(10,2) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `data_pagamento` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reserva` (`reserva_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `pagamentos_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `pagamentos`

-- --------------------------------------------------------

-- Table structure for table `reservas`

DROP TABLE IF EXISTS `reservas`;
CREATE TABLE `reservas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `vaga_id` int(11) NOT NULL,
  `veiculo_id` int(11) DEFAULT NULL,
  `data_inicio` datetime NOT NULL,
  `data_fim` datetime NOT NULL,
  `status` enum('ativa','cancelada','concluida','em_multa') NOT NULL DEFAULT 'ativa',
  `valor_total` decimal(10,2) NOT NULL,
  `marca_veiculo` varchar(50) NOT NULL,
  `matricula` varchar(20) NOT NULL,
  `proprietario` varchar(100) NOT NULL,
  `contacto` varchar(20) NOT NULL,
  `valor_multa` decimal(10,2) DEFAULT 0.00,
  `status_saida` enum('nenhum','pendente','autorizada','negada') NOT NULL DEFAULT 'nenhum',
  `autorizado_por` int(11) DEFAULT NULL,
  `data_multa` datetime DEFAULT NULL,
  `data_saida_real` datetime DEFAULT NULL,
  `multa_fechada` tinyint(1) DEFAULT 0,
  `data_liberacao_vaga` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_vaga` (`vaga_id`),
  KEY `idx_status` (`status`),
  KEY `idx_data_inicio` (`data_inicio`),
  KEY `idx_matricula` (`matricula`),
  KEY `fk_reserva_veiculo` (`veiculo_id`),
  KEY `fk_reserva_autorizador` (`autorizado_por`),
  CONSTRAINT `fk_reserva_autorizador` FOREIGN KEY (`autorizado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_reserva_veiculo` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservas_ibfk_2` FOREIGN KEY (`vaga_id`) REFERENCES `vagas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `reservas`

-- --------------------------------------------------------

-- Table structure for table `solicitacoes_saida`

DROP TABLE IF EXISTS `solicitacoes_saida`;
CREATE TABLE `solicitacoes_saida` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reserva_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `funcionario_id` int(11) DEFAULT NULL,
  `status` enum('pendente','autorizada','negada') NOT NULL DEFAULT 'pendente',
  `motivo_negacao` text DEFAULT NULL,
  `data_solicitacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_resposta` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reserva_saida` (`reserva_id`),
  KEY `idx_usuario_solicitante` (`usuario_id`),
  KEY `idx_funcionario_autorizador` (`funcionario_id`),
  CONSTRAINT `fk_saida_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_saida_reserva` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_saida_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `solicitacoes_saida`

-- --------------------------------------------------------

-- Table structure for table `solicitacoes_veiculos`

DROP TABLE IF EXISTS `solicitacoes_veiculos`;
CREATE TABLE `solicitacoes_veiculos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `veiculo_id` int(11) NOT NULL,
  `tipo` enum('editar','eliminar') NOT NULL,
  `status` enum('pendente','aprovada','rejeitada') NOT NULL DEFAULT 'pendente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `visto_pelo_usuario` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_solicitacao` (`usuario_id`),
  KEY `idx_veiculo_solicitacao` (`veiculo_id`),
  CONSTRAINT `solicitacoes_veiculos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `solicitacoes_veiculos_ibfk_2` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `solicitacoes_veiculos`

-- --------------------------------------------------------

-- Table structure for table `usuarios`

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `role` enum('admin','funcionario','usuario','contabilista_estagiario','contabilista_senior') NOT NULL DEFAULT 'usuario',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `usuarios`

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `role`, `data_criacao`) VALUES
('1', 'Administrador', 'admin@smartpark.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-02-10 12:35:27'),
('2', 'admin', 'admin@gmail.com', '$2y$10$No8XOuc4SL0njRmRZ0QS.eWXLkS8E/eYac39PcZbSQMTTRTgUOCR.', 'admin', '2026-02-10 12:36:25');

-- --------------------------------------------------------

-- Table structure for table `vagas`

DROP TABLE IF EXISTS `vagas`;
CREATE TABLE `vagas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estacionamento_id` int(11) NOT NULL,
  `numero_vaga` varchar(20) NOT NULL,
  `status` enum('livre','ocupada','reservada','ocupada_multa') NOT NULL DEFAULT 'livre',
  `tipo` enum('normal','deficiente','eletrico') NOT NULL DEFAULT 'normal',
  `ocupada` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_estacionamento` (`estacionamento_id`),
  KEY `idx_status` (`status`),
  KEY `idx_tipo` (`tipo`),
  CONSTRAINT `vagas_ibfk_1` FOREIGN KEY (`estacionamento_id`) REFERENCES `estacionamentos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `vagas`

INSERT INTO `vagas` (`id`, `estacionamento_id`, `numero_vaga`, `status`, `tipo`, `ocupada`) VALUES
('1', '1', 'Vaga 1', 'livre', 'normal', '0'),
('2', '1', 'Vaga 2', 'livre', 'normal', '0'),
('3', '1', 'Vaga 3', 'livre', 'normal', '0'),
('4', '1', 'Vaga 4', 'livre', 'normal', '0'),
('5', '1', 'Vaga 5', 'livre', 'normal', '0'),
('6', '1', 'Vaga 6', 'livre', 'normal', '0'),
('7', '1', 'Vaga 7', 'livre', 'normal', '0'),
('8', '1', 'Vaga 8', 'livre', 'normal', '0'),
('9', '1', 'Vaga 9', 'livre', 'normal', '0'),
('10', '1', 'Vaga 10', 'livre', 'normal', '0'),
('11', '1', 'Vaga 11', 'livre', 'normal', '0'),
('12', '1', 'Vaga 12', 'livre', 'normal', '0'),
('13', '1', 'Vaga 13', 'livre', 'normal', '0'),
('14', '1', 'Vaga 14', 'livre', 'normal', '0'),
('15', '1', 'Vaga 15', 'livre', 'normal', '0'),
('16', '1', 'Vaga 16', 'livre', 'normal', '0'),
('17', '1', 'Vaga 17', 'livre', 'normal', '0'),
('18', '1', 'Vaga 18', 'livre', 'normal', '0'),
('19', '1', 'Vaga 19', 'livre', 'normal', '0'),
('20', '1', 'Vaga 20', 'livre', 'normal', '0'),
('21', '1', 'Vaga 21', 'livre', 'normal', '0'),
('22', '1', 'Vaga 22', 'livre', 'normal', '0'),
('23', '1', 'Vaga 23', 'livre', 'normal', '0'),
('24', '1', 'Vaga 24', 'livre', 'normal', '0'),
('25', '1', 'Vaga 25', 'livre', 'normal', '0'),
('26', '1', 'Vaga 26', 'livre', 'normal', '0'),
('27', '1', 'Vaga 27', 'livre', 'normal', '0'),
('28', '1', 'Vaga 28', 'livre', 'normal', '0'),
('29', '1', 'Vaga 29', 'livre', 'normal', '0'),
('30', '1', 'Vaga 30', 'livre', 'normal', '0'),
('31', '1', 'Vaga 31', 'livre', 'normal', '0'),
('32', '1', 'Vaga 32', 'livre', 'normal', '0'),
('33', '1', 'Vaga 33', 'livre', 'normal', '0'),
('34', '1', 'Vaga 34', 'livre', 'normal', '0'),
('35', '1', 'Vaga 35', 'livre', 'normal', '0'),
('36', '1', 'Vaga 36', 'livre', 'normal', '0'),
('37', '1', 'Vaga 37', 'livre', 'normal', '0'),
('38', '1', 'Vaga 38', 'livre', 'normal', '0'),
('39', '1', 'Vaga 39', 'livre', 'normal', '0'),
('40', '1', 'Vaga 40', 'livre', 'normal', '0'),
('41', '1', 'Vaga 41', 'livre', 'deficiente', '0'),
('42', '1', 'Vaga 42', 'livre', 'deficiente', '0'),
('43', '1', 'Vaga 43', 'livre', 'deficiente', '0'),
('44', '1', 'Vaga 44', 'livre', 'deficiente', '0'),
('45', '1', 'Vaga 45', 'livre', 'deficiente', '0'),
('46', '1', 'Vaga 46', 'livre', 'eletrico', '0'),
('47', '1', 'Vaga 47', 'livre', 'eletrico', '0'),
('48', '1', 'Vaga 48', 'livre', 'eletrico', '0'),
('49', '1', 'Vaga 49', 'livre', 'eletrico', '0'),
('50', '1', 'Vaga 50', 'livre', 'eletrico', '0');

-- --------------------------------------------------------

-- Table structure for table `veiculos`

DROP TABLE IF EXISTS `veiculos`;
CREATE TABLE `veiculos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `matricula` varchar(20) NOT NULL,
  `cor` varchar(50) DEFAULT NULL,
  `proprietario_nome` varchar(100) DEFAULT NULL,
  `contacto` varchar(20) DEFAULT NULL,
  `pode_editar` tinyint(1) DEFAULT 0,
  `pode_eliminar` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_matricula_unique` (`matricula`),
  KEY `idx_usuario_veiculo` (`usuario_id`),
  CONSTRAINT `fk_veiculo_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `veiculos`

INSERT INTO `veiculos` (`id`, `usuario_id`, `marca`, `modelo`, `matricula`, `cor`, `proprietario_nome`, `contacto`, `pode_editar`, `pode_eliminar`, `created_at`) VALUES
('1', '2', 'teste', 'teste', 'TES-001-TT', 'teste', NULL, NULL, '0', '0', '2026-02-10 12:36:25');

SET FOREIGN_KEY_CHECKS=1;
COMMIT;
