-- ============================================================================

-- PRIMEIRO TRECHO

-- ETAPA C: SCRIPT DDL - SISTEMA DE GESTÃO DE SEMINÁRIOS ACADÊMICOS
-- SGBD: MySQL 8.0.13+ | Engine: InnoDB | Charset: utf8mb4

-- ARQUIVO 1: 01_schema_ddl.sql

-- ============================================================================


SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS gestao_seminarios;
CREATE DATABASE gestao_seminarios
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE gestao_seminarios;

-- 1. USUÁRIOS E ESTRUTURA ACADÊMICA
-- Exclusão lógica: alunos e professores não são apagados fisicamente.
-- O endpoint de exclusão altera status para 'inativo' e preserva o histórico.
CREATE TABLE alunos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    matricula VARCHAR(30) NOT NULL,
    ano_matricula INT UNSIGNED NOT NULL,
    semestre_entrada TINYINT UNSIGNED NOT NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT uk_alunos_email UNIQUE (email),
    CONSTRAINT uk_alunos_matricula UNIQUE (matricula),
    CONSTRAINT chk_alunos_semestre CHECK (semestre_entrada IN (1, 2)),
    INDEX idx_alunos_busca (nome_completo, ano_matricula),
    INDEX idx_alunos_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE professores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    registro VARCHAR(30) NOT NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT uk_professores_email UNIQUE (email),
    CONSTRAINT uk_professores_registro UNIQUE (registro),
    INDEX idx_professores_busca (nome_completo, registro),
    INDEX idx_professores_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cursos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sigla VARCHAR(20) NOT NULL,
    nome VARCHAR(120) NOT NULL,
    descricao TEXT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT uk_cursos_sigla UNIQUE (sigla),
    INDEX idx_cursos_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE disciplinas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    curso_id INT UNSIGNED NOT NULL,
    professor_id INT UNSIGNED NULL,
    nome VARCHAR(120) NOT NULL,
    codigo VARCHAR(20) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT uk_disciplinas_codigo UNIQUE (codigo),
    CONSTRAINT fk_disciplinas_curso FOREIGN KEY (curso_id) 
        REFERENCES cursos (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_disciplinas_professor FOREIGN KEY (professor_id) 
        REFERENCES professores (id) ON DELETE SET NULL ON UPDATE CASCADE,
        
    INDEX idx_disciplinas_curso (curso_id),
    INDEX idx_disciplinas_professor (professor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE disciplina_aluno (
    disciplina_id INT UNSIGNED NOT NULL,
    aluno_id INT UNSIGNED NOT NULL,
    matriculado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (disciplina_id, aluno_id),
    CONSTRAINT fk_discaluno_disciplina FOREIGN KEY (disciplina_id) 
        REFERENCES disciplinas (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_discaluno_aluno FOREIGN KEY (aluno_id) 
        REFERENCES alunos (id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_discaluno_aluno (aluno_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. CONFIGURAÇÃO DO SEMINÁRIO
CREATE TABLE seminarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    disciplina_id INT UNSIGNED NOT NULL,
    professor_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(180) NOT NULL,
    descricao TEXT NULL,
    tempo_apresentacao INT UNSIGNED NOT NULL,
    peso_professor INT UNSIGNED NOT NULL,
    peso_alunos INT UNSIGNED NOT NULL,
    status ENUM('pendente', 'agendado', 'em_andamento', 'concluido') NOT NULL DEFAULT 'pendente',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_seminarios_disciplina FOREIGN KEY (disciplina_id) 
        REFERENCES disciplinas (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_seminarios_professor FOREIGN KEY (professor_id) 
        REFERENCES professores (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_seminarios_pesos CHECK ((peso_professor + peso_alunos) = 100),
    CONSTRAINT chk_seminarios_tempo CHECK (tempo_apresentacao > 0),
    
    INDEX idx_seminarios_disciplina (disciplina_id),
    INDEX idx_seminarios_professor (professor_id),
    INDEX idx_seminarios_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE seminario_datas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seminario_id INT UNSIGNED NOT NULL,
    data_apresentacao DATE NOT NULL,
    horario TIME NOT NULL,
    status ENUM('agendada', 'em_andamento', 'concluida', 'cancelada') NOT NULL DEFAULT 'agendada',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_semdatas_seminario FOREIGN KEY (seminario_id) 
        REFERENCES seminarios (id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_semdatas_cronologico (seminario_id, data_apresentacao, horario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE seminario_criterios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seminario_id INT UNSIGNED NOT NULL,
    nome_criterio VARCHAR(100) NOT NULL,
    peso_criterio INT UNSIGNED NOT NULL,
    tipo_avaliacao ENUM('individual', 'grupo') NOT NULL,
    
    CONSTRAINT fk_semcriterios_seminario FOREIGN KEY (seminario_id) 
        REFERENCES seminarios (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_semcriterios_peso CHECK (peso_criterio > 0 AND peso_criterio <= 100),
    INDEX idx_semcriterios_seminario (seminario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. GESTÃO DE SESSÕES
CREATE TABLE grupos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seminario_data_id INT UNSIGNED NOT NULL,
    tema VARCHAR(200) NOT NULL,
    ordem_apresentacao INT UNSIGNED NULL,
    status ENUM('pendente', 'em_apresentacao', 'avaliado') NOT NULL DEFAULT 'pendente',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_grupos_seminario_data FOREIGN KEY (seminario_data_id) 
        REFERENCES seminario_datas (id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_grupos_data_ordem (seminario_data_id, ordem_apresentacao),
    INDEX idx_grupos_status (seminario_data_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE grupo_alunos (
    grupo_id INT UNSIGNED NOT NULL,
    aluno_id INT UNSIGNED NOT NULL,
    
    PRIMARY KEY (grupo_id, aluno_id),
    CONSTRAINT fk_grupoalunos_grupo FOREIGN KEY (grupo_id) 
        REFERENCES grupos (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_grupoalunos_aluno FOREIGN KEY (aluno_id) 
        REFERENCES alunos (id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_grupoalunos_aluno (aluno_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE presencas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seminario_data_id INT UNSIGNED NOT NULL,
    aluno_id INT UNSIGNED NOT NULL,
    presente BOOLEAN NOT NULL DEFAULT 0,
    registrado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT uk_presencas_data_aluno UNIQUE (seminario_data_id, aluno_id),
    CONSTRAINT fk_presencas_seminario_data FOREIGN KEY (seminario_data_id) 
        REFERENCES seminario_datas (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_presencas_aluno FOREIGN KEY (aluno_id) 
        REFERENCES alunos (id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_presencas_aluno (aluno_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. AVALIAÇÃO E RESULTADOS
CREATE TABLE avaliacoes_notas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seminario_data_id INT UNSIGNED NOT NULL,
    grupo_id INT UNSIGNED NOT NULL,
    criterio_id INT UNSIGNED NOT NULL,
    avaliador_id INT UNSIGNED NOT NULL,
    tipo_avaliador ENUM('professor', 'aluno') NOT NULL,
    aluno_avaliado_id INT UNSIGNED NULL,
    nota DECIMAL(4,2) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_avalnotas_seminario_data FOREIGN KEY (seminario_data_id) 
        REFERENCES seminario_datas (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_avalnotas_grupo FOREIGN KEY (grupo_id) 
        REFERENCES grupos (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_avalnotas_criterio FOREIGN KEY (criterio_id) 
        REFERENCES seminario_criterios (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_avalnotas_aluno_avaliado FOREIGN KEY (aluno_avaliado_id) 
        REFERENCES alunos (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_avalnotas_valor CHECK (nota >= 0.00 AND nota <= 10.00),
    
    UNIQUE INDEX uk_avalnotas_upsert (
        seminario_data_id, 
        grupo_id, 
        criterio_id, 
        tipo_avaliador, 
        avaliador_id, 
        (COALESCE(aluno_avaliado_id, 0))
    ),
    
    INDEX idx_avalnotas_ranking (seminario_data_id, grupo_id, tipo_avaliador),
    INDEX idx_avalnotas_avaliador (tipo_avaliador, avaliador_id),
    INDEX idx_avalnotas_criterio (criterio_id),
    INDEX idx_avalnotas_aluno_avaliado (aluno_avaliado_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE avaliacoes_feedbacks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seminario_data_id INT UNSIGNED NOT NULL,
    grupo_id INT UNSIGNED NOT NULL,
    avaliador_id INT UNSIGNED NOT NULL,
    tipo_avaliador ENUM('professor', 'aluno') NOT NULL,
    feedback_texto TEXT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT uk_avalfeedbacks_upsert UNIQUE (seminario_data_id, grupo_id, tipo_avaliador, avaliador_id),
    CONSTRAINT fk_avalfeedbacks_seminario_data FOREIGN KEY (seminario_data_id) 
        REFERENCES seminario_datas (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_avalfeedbacks_grupo FOREIGN KEY (grupo_id) 
        REFERENCES grupos (id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_avalfeedbacks_grupo (grupo_id),
    INDEX idx_avalfeedbacks_avaliador (tipo_avaliador, avaliador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;