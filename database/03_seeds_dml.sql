-- ============================================================================

-- TERCEIRO TRECHO

-- ARQUIVO 3: 03_seeds_dml.sql
-- ============================================================================

USE gestao_seminarios;

INSERT INTO cursos (id, sigla, nome, descricao) VALUES
(1, 'TSI', 'Tecnologia em Sistemas para Internet', 'Curso superior focado em desenvolvimento Full Stack, banco de dados, redes e design de interação.'),
(2, 'ES', 'Engenharia de Software', 'Curso voltado à arquitetura, qualidade e gestão de projetos de software.');

INSERT INTO professores (id, nome_completo, email, registro) VALUES
(1, 'Prof. Dr. Carlos Eduardo Albuquerque', 'carlos.albuquerque@instituicao.edu.br', 'SIAPE-102938'),
(2, 'Profa. Dra. Fernanda Lima Cavalcanti', 'fernanda.cavalcanti@instituicao.edu.br', 'SIAPE-495821');

INSERT INTO alunos (id, nome_completo, email, matricula, ano_matricula, semestre_entrada) VALUES
(1, 'Tacila Souza Silva', 'tacila.souza@aluno.edu.br', '20261TSI001', 2026, 1),
(2, 'Nathasha Scanoni', 'nathasha.scanoni@aluno.edu.br', '20261TSI002', 2026, 1),
(3, 'Lucas Gabriel Mendes', 'lucas.mendes@aluno.edu.br', '20261TSI003', 2026, 1),
(4, 'Beatriz Helena Costa', 'beatriz.costa@aluno.edu.br', '20261TSI004', 2026, 1),
(5, 'Rafael Oliveira Santos', 'rafael.santos@aluno.edu.br', '20262TSI005', 2026, 2),
(6, 'Juliana Martins Pereira', 'juliana.pereira@aluno.edu.br', '20262TSI006', 2026, 2);

INSERT INTO disciplinas (id, curso_id, professor_id, nome, codigo) VALUES
(1, 1, 1, 'Banco de Dados Relacionais', 'TSI-BDR01'),
(2, 1, 2, 'Design de Interação e UX', 'TSI-DIU02'),
(3, 1, NULL, 'Arquitetura de Computadores', 'TSI-ARC03');

INSERT INTO disciplina_aluno (disciplina_id, aluno_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6);

INSERT INTO seminarios (id, disciplina_id, professor_id, titulo, descricao, tempo_apresentacao, peso_professor, peso_alunos, status) VALUES
(1, 1, 1, 'Seminário Integrador: Modelagem e Otimização SQL', 'Apresentação prática de modelagem relacional, indexação e normalização.', 20, 70, 30, 'em_andamento');

INSERT INTO seminario_datas (id, seminario_id, data_apresentacao, horario, status) VALUES
(1, 1, '2026-11-24', '14:00:00', 'concluida'),
(2, 1, '2026-12-01', '14:00:00', 'agendada');

INSERT INTO seminario_criterios (id, seminario_id, nome_criterio, peso_criterio, tipo_avaliacao) VALUES
(1, 1, 'Qualidade da Modelagem e Script SQL', 40, 'grupo'),
(2, 1, 'Organização Visual dos Slides e Tempo', 20, 'grupo'),
(3, 1, 'Domínio Técnico e Oratória Individual', 40, 'individual');

INSERT INTO grupos (id, seminario_data_id, tema, ordem_apresentacao, status) VALUES
(1, 1, 'Grupo A: Arquitetura InnoDB, Índices B-Tree e ACID', 1, 'avaliado'),
(2, 1, 'Grupo B: Normalização (1FN à 3FN) e Relacionamentos Polimórficos', 2, 'pendente');

INSERT INTO grupo_alunos (grupo_id, aluno_id) VALUES
(1, 1), (1, 2), (1, 3),
(2, 4), (2, 5);

INSERT INTO presencas (seminario_data_id, aluno_id, presente) VALUES
(1, 1, 1), (1, 2, 1), (1, 3, 1), (1, 4, 1), (1, 5, 1), (1, 6, 0);

INSERT INTO avaliacoes_notas (seminario_data_id, grupo_id, criterio_id, avaliador_id, tipo_avaliador, aluno_avaliado_id, nota) VALUES
(1, 1, 1, 1, 'professor', NULL, 9.50),
(1, 1, 2, 1, 'professor', NULL, 9.00),
(1, 1, 3, 1, 'professor', 1, 10.00),
(1, 1, 3, 1, 'professor', 2, 9.50),
(1, 1, 3, 1, 'professor', 3, 8.80);

INSERT INTO avaliacoes_feedbacks (seminario_data_id, grupo_id, avaliador_id, tipo_avaliador, feedback_texto) VALUES
(1, 1, 1, 'professor', 'Excelente demonstração prática dos índices compostos e clareza na explicação das constraints.');