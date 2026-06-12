-- ============================================================
-- sistema_labs.sql
-- Schema completo do banco de dados — LabHub UNICEPLAC
-- Gerado por engenharia reversa dos Models e Painéis PHP
-- Compatível com MySQL 5.7+ / MariaDB 10.4+
-- Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ------------------------------------------------------------
-- 1. Banco de dados
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `sistema_labs`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `sistema_labs`;

-- ============================================================
-- TABELAS DE DOMÍNIO (sem dependências externas)
-- ============================================================

-- ------------------------------------------------------------
-- 2. usuarios
-- Fonte: app/Models/User.php
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`                 INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `nome`               VARCHAR(150)     NOT NULL,
  `email`              VARCHAR(150)     NOT NULL,
  `senha`              VARCHAR(255)     NULL      DEFAULT NULL COMMENT 'NULL quando autenticação é somente via Google',
  `perfil`             ENUM('coordenador','professor','suporte') NOT NULL DEFAULT 'professor',
  `email_verificado`   TINYINT(1)       NOT NULL DEFAULT 0,
  `token_verificacao`  VARCHAR(255)     NULL      DEFAULT NULL,
  `token_expira_em`    DATETIME         NULL      DEFAULT NULL COMMENT 'Expiração do token (redefinição de senha)',
  `google_id`          VARCHAR(100)     NULL      DEFAULT NULL,
  `foto_perfil`        VARCHAR(500)     NULL      DEFAULT NULL COMMENT 'Caminho local (uploads/) ou URL do avatar Google',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email`     (`email`),
  UNIQUE KEY `uq_google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. laboratorios
-- Fonte: painel_coordenador.php — INSERT INTO laboratorios
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `laboratorios` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nome`        VARCHAR(100)  NOT NULL,
  `capacidade`  INT           NOT NULL DEFAULT 0,
  `localizacao` VARCHAR(150)  NULL DEFAULT NULL,
  `andar`       VARCHAR(50)   NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. disciplinas
-- Fonte: painel_coordenador.php — INSERT INTO disciplinas
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `disciplinas` (
  `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. cursos
-- Fonte: painel_coordenador.php — INSERT INTO cursos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cursos` (
  `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. semestres
-- Fonte: painel_coordenador.php — INSERT INTO semestres
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `semestres` (
  `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(50)  NOT NULL COMMENT 'Ex: 2026.1, 2026.2',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. blocos
-- Fonte: painel_coordenador.php — INSERT INTO blocos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blocos` (
  `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(50)  NOT NULL COMMENT 'Ex: A, B, C',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. andares
-- Fonte: painel_coordenador.php — INSERT INTO andares
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `andares` (
  `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(50)  NOT NULL COMMENT 'Ex: Térreo, 1º Andar',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. salas
-- Fonte: painel_coordenador.php — INSERT INTO salas
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `salas` (
  `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(50)  NOT NULL COMMENT 'Ex: 101, 203',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELAS RELACIONAIS
-- ============================================================

-- ------------------------------------------------------------
-- 10. agendamentos
-- Fonte: app/Models/Agendamento.php
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `agendamentos` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_laboratorio` INT UNSIGNED NOT NULL,
  `id_professor`   INT UNSIGNED NOT NULL,
  `id_disciplina`  INT UNSIGNED NOT NULL,
  `turno`          ENUM('Matutino','Vespertino','Noturno') NOT NULL,
  `periodo`        ENUM('1º e 2º Horários','1º Horário','2º Horário') NOT NULL,
  `data_reserva`   DATE         NOT NULL,
  `status`         ENUM('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'pendente',
  PRIMARY KEY (`id`),
  KEY `idx_laboratorio_data_turno` (`id_laboratorio`, `data_reserva`, `turno`),
  KEY `idx_professor`              (`id_professor`),
  UNIQUE KEY `uq_lab_data_turno_periodo` (`id_laboratorio`, `data_reserva`, `turno`, `periodo`),
  CONSTRAINT `fk_ag_laboratorio` FOREIGN KEY (`id_laboratorio`) REFERENCES `laboratorios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ag_professor`   FOREIGN KEY (`id_professor`)   REFERENCES `usuarios`      (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ag_disciplina`  FOREIGN KEY (`id_disciplina`)  REFERENCES `disciplinas`   (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 11. controle_chaves
-- Fonte: painel_professor.php — INSERT INTO controle_chaves
--        painel_suporte.php   — UPDATE controle_chaves
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `controle_chaves` (
  `id`                     INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `id_agendamento`         INT UNSIGNED  NOT NULL,
  `professor_nome`         VARCHAR(150)  NOT NULL,
  `laboratorio`            VARCHAR(100)  NOT NULL,
  `data_uso`               DATE          NOT NULL,
  `celular`                VARCHAR(20)   NULL DEFAULT NULL,
  `hora_retirada`          TIME          NOT NULL,
  `hora_devolucao_prevista` TIME         NOT NULL,
  `hora_devolucao_real`    TIME          NULL DEFAULT NULL,
  `funcionario_entrega`    VARCHAR(100)  NOT NULL,
  `funcionario_recebimento` VARCHAR(100) NULL DEFAULT NULL,
  `status`                 ENUM('em_uso','devolvido') NOT NULL DEFAULT 'em_uso',
  PRIMARY KEY (`id`),
  KEY `idx_chaves_agendamento` (`id_agendamento`),
  KEY `idx_chaves_status`      (`status`),
  CONSTRAINT `fk_chaves_agendamento` FOREIGN KEY (`id_agendamento`) REFERENCES `agendamentos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 12. chamados_suporte
-- Fonte: app/Models/SOS.php
--        painel_professor.php — INSERT INTO chamados_suporte
--        painel_suporte.php   — SELECT ... ORDER BY data_hora
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chamados_suporte` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `id_professor`  INT UNSIGNED  NOT NULL,
  `professor_nome` VARCHAR(150) NOT NULL,
  `laboratorio`   VARCHAR(100) NOT NULL,
  `mensagem`      TEXT         NOT NULL,
  `status`        ENUM('pendente','resolvido') NOT NULL DEFAULT 'pendente',
  `data_hora`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sos_status`    (`status`),
  KEY `idx_sos_professor` (`id_professor`),
  CONSTRAINT `fk_sos_professor` FOREIGN KEY (`id_professor`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 13. quadros_horarios
-- Fonte: painel_coordenador.php
--        SELECT * FROM quadros_horarios ORDER BY data_criacao DESC
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `quadros_horarios` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nome`          VARCHAR(150)  NOT NULL COMMENT 'Ex: Grade 2026.1',
  `periodo_letivo` VARCHAR(50)  NOT NULL COMMENT 'Ex: 2026.1',
  `data_criacao`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 14. quadro_aulas
-- Fonte: painel_coordenador.php — INSERT INTO quadro_aulas
--        com colunas: id_quadro, turno, dia_semana, curso, semestre,
--        id_disciplina, modalidade, numero_alunos, id_professor,
--        id_laboratorio, horario, bloco, andar, sala,
--        carga_horaria_total, horas_laboratorio
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `quadro_aulas` (
  `id`                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `id_quadro`          INT UNSIGNED  NOT NULL,
  `turno`              ENUM('Matutino','Vespertino','Noturno') NOT NULL,
  `dia_semana`         ENUM('Segunda','Terça','Quarta','Quinta','Sexta','Sábado','Domingo') NOT NULL,
  `curso`              VARCHAR(150)  NOT NULL,
  `semestre`           VARCHAR(50)   NOT NULL COMMENT 'Ex: 1º Semestre',
  `id_disciplina`      INT UNSIGNED  NOT NULL,
  `modalidade`         VARCHAR(100)  NULL DEFAULT NULL COMMENT 'Ex: Presencial, EAD',
  `numero_alunos`      INT           NULL DEFAULT NULL,
  `id_professor`       INT UNSIGNED  NULL DEFAULT NULL,
  `id_laboratorio`     INT UNSIGNED  NULL DEFAULT NULL,
  `horario`            ENUM('1º e 2º Horários','1º Horário','2º Horário') NOT NULL,
  `bloco`              VARCHAR(50)   NULL DEFAULT NULL,
  `andar`              VARCHAR(50)   NULL DEFAULT NULL,
  `sala`               VARCHAR(50)   NULL DEFAULT NULL,
  `carga_horaria_total` DECIMAL(5,2) NULL DEFAULT NULL COMMENT 'Total de horas da disciplina',
  `horas_laboratorio`  DECIMAL(5,2)  NULL DEFAULT NULL COMMENT 'Horas práticas em laboratório',
  PRIMARY KEY (`id`),
  KEY `idx_qa_quadro`      (`id_quadro`),
  KEY `idx_qa_professor`   (`id_professor`),
  KEY `idx_qa_laboratorio` (`id_laboratorio`),
  KEY `idx_qa_dia_turno`   (`dia_semana`, `turno`),
  CONSTRAINT `fk_qa_quadro`      FOREIGN KEY (`id_quadro`)      REFERENCES `quadros_horarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qa_disciplina`  FOREIGN KEY (`id_disciplina`)  REFERENCES `disciplinas`      (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qa_professor`   FOREIGN KEY (`id_professor`)   REFERENCES `usuarios`         (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_qa_laboratorio` FOREIGN KEY (`id_laboratorio`) REFERENCES `laboratorios`     (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 15. ensalamento
-- Fonte: painel_coordenador.php
--        INSERT INTO ensalamento (id_professor, id_disciplina,
--        curso, bloco, andar, sala, categoria, turno)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ensalamento` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_professor`  INT UNSIGNED NOT NULL,
  `id_disciplina` INT UNSIGNED NOT NULL,
  `curso`         VARCHAR(150) NOT NULL,
  `bloco`         VARCHAR(50)  NOT NULL,
  `andar`         VARCHAR(50)  NOT NULL,
  `sala`          VARCHAR(50)  NOT NULL,
  `categoria`     VARCHAR(100) NULL DEFAULT NULL COMMENT 'Ex: Presencial, EAD Polo',
  `turno`         ENUM('Matutino','Vespertino','Noturno') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ens_professor`  (`id_professor`),
  KEY `idx_ens_sala_turno` (`bloco`, `andar`, `sala`, `turno`),
  CONSTRAINT `fk_ens_professor`  FOREIGN KEY (`id_professor`)  REFERENCES `usuarios`    (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ens_disciplina` FOREIGN KEY (`id_disciplina`) REFERENCES `disciplinas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DADOS INICIAIS (seed)
-- ============================================================

-- Usuário administrador padrão (coordenador)
-- Senha: password  →  hash bcrypt gerado por password_hash('password', PASSWORD_DEFAULT)
INSERT INTO `usuarios` (`nome`, `email`, `senha`, `perfil`, `email_verificado`) VALUES
('Administrador', 'admin@uniceplac.edu.br',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'coordenador', 1),
('Prof. Ana Silva', 'professor@uniceplac.edu.br',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'professor', 1),
('Prof. João Mendes', 'joao@uniceplac.edu.br',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'professor', 1),
('Técnico Carlos Suporte', 'suporte@uniceplac.edu.br',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'suporte', 1);

-- Laboratórios de exemplo
INSERT INTO `laboratorios` (`nome`, `capacidade`, `localizacao`, `andar`) VALUES
('Laboratório de Informática 01', 40, 'Bloco A', '1º Andar'),
('Laboratório de Informática 02', 40, 'Bloco A', '1º Andar'),
('Laboratório de Redes',          30, 'Bloco B', '2º Andar'),
('Laboratório Multimídia',        35, 'Bloco C', 'Térreo');

-- Disciplinas de exemplo
INSERT INTO `disciplinas` (`nome`) VALUES
('Algoritmos e Estruturas de Dados'),
('Banco de Dados'),
('Engenharia de Software'),
('Programação Web'),
('Redes de Computadores'),
('Inteligência Artificial'),
('Sistemas Operacionais');

-- Cursos de exemplo
INSERT INTO `cursos` (`nome`) VALUES
('Ciência da Computação'),
('Sistemas de Informação'),
('Engenharia de Software'),
('Análise e Desenvolvimento de Sistemas');

-- Semestres
INSERT INTO `semestres` (`nome`) VALUES
('2026.1'),
('2026.2');

-- Estrutura física (blocos, andares, salas)
INSERT INTO `blocos`  (`nome`) VALUES ('A'), ('B'), ('C'), ('D');
INSERT INTO `andares` (`nome`) VALUES ('Térreo'), ('1º Andar'), ('2º Andar');
INSERT INTO `salas`   (`nome`) VALUES ('101'), ('102'), ('103'), ('201'), ('202'), ('203'), ('301');

-- ============================================================
-- FIM DO SCRIPT
-- ============================================================
