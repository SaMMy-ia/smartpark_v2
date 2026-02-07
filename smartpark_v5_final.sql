-- SmartPark v5 Complete Database Schema
-- Consolidated by Antigravity
-- Date: 2026-02-07
CREATE DATABASE IF NOT EXISTS `smartpark_v6` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `smartpark_v6`;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
-- 1. Table: estacionamentos
DROP TABLE IF EXISTS `estacionamentos`;
CREATE TABLE `estacionamentos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nome` varchar(100) NOT NULL,
    `endereco` text NOT NULL,
    `capacidade_total` int(11) NOT NULL,
    `vagas_disponiveis` int(11) NOT NULL,
    `preco_hora` decimal(10, 2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_nome` (`nome`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
INSERT INTO `estacionamentos` (
        `id`,
        `nome`,
        `endereco`,
        `capacidade_total`,
        `vagas_disponiveis`,
        `preco_hora`
    )
VALUES (
        1,
        'Parque Rivas',
        'Avenida Principal, 1000 - Centro',
        50,
        50,
        10.00
    );
-- 2. Table: usuarios
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nome` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `senha` varchar(255) NOT NULL,
    `role` enum(
        'admin',
        'funcionario',
        'usuario',
        'contabilista_estagiario',
        'contabilista_senior'
    ) NOT NULL DEFAULT 'usuario',
    `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    KEY `idx_email` (`email`),
    KEY `idx_role` (`role`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
-- Default Admin (Password: @Admin2025 or whatever was the original hash)
INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `role`)
VALUES (
        1,
        'Administrador',
        'admin@smartpark.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'admin'
    );
-- 3. Table: vagas
DROP TABLE IF EXISTS `vagas`;
CREATE TABLE `vagas` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `estacionamento_id` int(11) NOT NULL,
    `numero_vaga` varchar(20) NOT NULL,
    `status` enum('livre', 'ocupada', 'reservada', 'ocupada_multa') NOT NULL DEFAULT 'livre',
    `tipo` enum('normal', 'deficiente', 'eletrico') NOT NULL DEFAULT 'normal',
    `ocupada` tinyint(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_estacionamento` (`estacionamento_id`),
    KEY `idx_status` (`status`),
    KEY `idx_tipo` (`tipo`),
    CONSTRAINT `vagas_ibfk_1` FOREIGN KEY (`estacionamento_id`) REFERENCES `estacionamentos` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
-- Initial vagas for Parque Rivas
-- Initial vagas for Parque Rivas
-- Normal spots (1-40)
INSERT INTO `vagas` (
        `estacionamento_id`,
        `numero_vaga`,
        `status`,
        `tipo`
    )
VALUES (1, 'Vaga 1', 'livre', 'normal'),
    (1, 'Vaga 2', 'livre', 'normal'),
    (1, 'Vaga 3', 'livre', 'normal'),
    (1, 'Vaga 4', 'livre', 'normal'),
    (1, 'Vaga 5', 'livre', 'normal'),
    (1, 'Vaga 6', 'livre', 'normal'),
    (1, 'Vaga 7', 'livre', 'normal'),
    (1, 'Vaga 8', 'livre', 'normal'),
    (1, 'Vaga 9', 'livre', 'normal'),
    (1, 'Vaga 10', 'livre', 'normal'),
    (1, 'Vaga 11', 'livre', 'normal'),
    (1, 'Vaga 12', 'livre', 'normal'),
    (1, 'Vaga 13', 'livre', 'normal'),
    (1, 'Vaga 14', 'livre', 'normal'),
    (1, 'Vaga 15', 'livre', 'normal'),
    (1, 'Vaga 16', 'livre', 'normal'),
    (1, 'Vaga 17', 'livre', 'normal'),
    (1, 'Vaga 18', 'livre', 'normal'),
    (1, 'Vaga 19', 'livre', 'normal'),
    (1, 'Vaga 20', 'livre', 'normal'),
    (1, 'Vaga 21', 'livre', 'normal'),
    (1, 'Vaga 22', 'livre', 'normal'),
    (1, 'Vaga 23', 'livre', 'normal'),
    (1, 'Vaga 24', 'livre', 'normal'),
    (1, 'Vaga 25', 'livre', 'normal'),
    (1, 'Vaga 26', 'livre', 'normal'),
    (1, 'Vaga 27', 'livre', 'normal'),
    (1, 'Vaga 28', 'livre', 'normal'),
    (1, 'Vaga 29', 'livre', 'normal'),
    (1, 'Vaga 30', 'livre', 'normal'),
    (1, 'Vaga 31', 'livre', 'normal'),
    (1, 'Vaga 32', 'livre', 'normal'),
    (1, 'Vaga 33', 'livre', 'normal'),
    (1, 'Vaga 34', 'livre', 'normal'),
    (1, 'Vaga 35', 'livre', 'normal'),
    (1, 'Vaga 36', 'livre', 'normal'),
    (1, 'Vaga 37', 'livre', 'normal'),
    (1, 'Vaga 38', 'livre', 'normal'),
    (1, 'Vaga 39', 'livre', 'normal'),
    (1, 'Vaga 40', 'livre', 'normal');
-- Deficiente spots (41-45)
INSERT INTO `vagas` (
        `estacionamento_id`,
        `numero_vaga`,
        `status`,
        `tipo`
    )
VALUES (1, 'Vaga 41', 'livre', 'deficiente'),
    (1, 'Vaga 42', 'livre', 'deficiente'),
    (1, 'Vaga 43', 'livre', 'deficiente'),
    (1, 'Vaga 44', 'livre', 'deficiente'),
    (1, 'Vaga 45', 'livre', 'deficiente');
-- Eletrico spots (46-50)
INSERT INTO `vagas` (
        `estacionamento_id`,
        `numero_vaga`,
        `status`,
        `tipo`
    )
VALUES (1, 'Vaga 46', 'livre', 'eletrico'),
    (1, 'Vaga 47', 'livre', 'eletrico'),
    (1, 'Vaga 48', 'livre', 'eletrico'),
    (1, 'Vaga 49', 'livre', 'eletrico'),
    (1, 'Vaga 50', 'livre', 'eletrico');
-- 4. Table: veiculos
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
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_matricula_unique` (`matricula`),
    KEY `idx_usuario_veiculo` (`usuario_id`),
    CONSTRAINT `fk_veiculo_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
-- 5. Table: reservas
DROP TABLE IF EXISTS `reservas`;
CREATE TABLE `reservas` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `usuario_id` int(11) NOT NULL,
    `vaga_id` int(11) NOT NULL,
    `veiculo_id` int(11) DEFAULT NULL,
    `data_inicio` datetime NOT NULL,
    `data_fim` datetime NOT NULL,
    `status` enum('ativa', 'cancelada', 'concluida', 'em_multa') NOT NULL DEFAULT 'ativa',
    `valor_total` decimal(10, 2) NOT NULL,
    `marca_veiculo` varchar(50) NOT NULL,
    `matricula` varchar(20) NOT NULL,
    `proprietario` varchar(100) NOT NULL,
    `contacto` varchar(20) NOT NULL,
    `valor_multa` decimal(10, 2) DEFAULT 0.00,
    `status_saida` enum('nenhum', 'pendente', 'autorizada', 'negada') NOT NULL DEFAULT 'nenhum',
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
    CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
    CONSTRAINT `reservas_ibfk_2` FOREIGN KEY (`vaga_id`) REFERENCES `vagas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reserva_veiculo` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`id`) ON DELETE
    SET NULL,
        CONSTRAINT `fk_reserva_autorizador` FOREIGN KEY (`autorizado_por`) REFERENCES `usuarios` (`id`) ON DELETE
    SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
-- 6. Table: pagamentos
DROP TABLE IF EXISTS `pagamentos`;
CREATE TABLE `pagamentos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `reserva_id` int(11) NOT NULL,
    `metodo` enum('cartao', 'pix', 'boleto') NOT NULL,
    `status` enum('pendente', 'pago', 'falha') NOT NULL DEFAULT 'pendente',
    `valor` decimal(10, 2) NOT NULL,
    `descricao` varchar(255) DEFAULT NULL,
    `data_pagamento` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_reserva` (`reserva_id`),
    KEY `idx_status` (`status`),
    CONSTRAINT `pagamentos_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
-- 7. Table: multas
DROP TABLE IF EXISTS `multas`;
CREATE TABLE `multas` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `reserva_id` int(11) NOT NULL,
    `valor_multa` decimal(10, 2) NOT NULL,
    `resolvida` tinyint(1) DEFAULT 0,
    `data_resolucao` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_reserva_multa` (`reserva_id`),
    CONSTRAINT `multas_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
-- 8. Table: logs
DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `usuario_id` int(11) DEFAULT NULL,
    `acao` varchar(255) NOT NULL,
    `data` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_usuario` (`usuario_id`),
    KEY `idx_data` (`data`),
    CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE
    SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
-- 9. Table: solicitacoes_saida (Redundant with reservas status_saida but kept if needed for historical logging)
DROP TABLE IF EXISTS `solicitacoes_saida`;
CREATE TABLE `solicitacoes_saida` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `reserva_id` int(11) NOT NULL,
    `usuario_id` int(11) NOT NULL,
    `funcionario_id` int(11) DEFAULT NULL,
    `status` enum('pendente', 'autorizada', 'negada') NOT NULL DEFAULT 'pendente',
    `motivo_negacao` text DEFAULT NULL,
    `data_solicitacao` timestamp NOT NULL DEFAULT current_timestamp(),
    `data_resposta` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_reserva_saida` (`reserva_id`),
    KEY `idx_usuario_solicitante` (`usuario_id`),
    KEY `idx_funcionario_autorizador` (`funcionario_id`),
    CONSTRAINT `fk_saida_reserva` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_saida_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_saida_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `usuarios` (`id`) ON DELETE
    SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 1;