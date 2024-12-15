-- Schema Legado

CREATE SCHEMA IF NOT EXISTS `0temp` DEFAULT CHARACTER SET utf8; 
USE `0temp`;

-- Table Pacientes
DROP TABLE IF EXISTS `pacientes`;
CREATE TABLE IF NOT EXISTS `pacientes`(
    `cod_paciente` VARCHAR(10) NOT NULL,
    `nome_paciente` VARCHAR(255) NOT NULL,
    `nasc_paciente` DATE NOT NULL,
    `pai_paciente` VARCHAR(255) NOT NULL,
    `mae_paciente` VARCHAR(255) NOT NULL,
    `cpf_paciente` VARCHAR(20) NULL,
    `rg_paciente` VARCHAR(20) NULL,
    `sex_pac` VARCHAR(10) NOT NULL,
    `id_conv` VARCHAR(10) NOT NULL,
    `convenio` VARCHAR(50) NOT NULL,
    `obs_clinicas` VARCHAR(255) NULL, 
    PRIMARY KEY(`cod_paciente`)
);

-- Table Agendamentos
DROP TABLE IF EXISTS `agendamentos`;
CREATE TABLE IF NOT EXISTS `agendamentos`(
    `cod_agendamento` INT NOT NULL,
    `descricao` VARCHAR(255) NULL,
    `dia` DATE NOT NULL,
    `hora_inicio` TIME NOT NULL,
    `hora_fim` TIME NOT NULL,
    `cod_paciente` INT NOT NULL,
    `paciente` VARCHAR(255) NOT NULL,
    `cod_medico` INT NOT NULL,
    `medico` VARCHAR(255) NOT NULL,
    `cod_convenio` INT NOT NULL,
    `convenio` VARCHAR(255) NOT NULL,
    `procedimento` VARCHAR(255) NOT NULL,
    PRIMARY KEY(`cod_agendamento`)
);

