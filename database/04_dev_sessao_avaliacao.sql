-- ============================================================================
-- SCRIPT OPCIONAL DE DESENVOLVIMENTO — NÃO FAZ PARTE DOS SCRIPTS OFICIAIS
--
-- Habilita a sessão 2 (2026-12-01) com um grupo elegível e com o aluno 1
-- presente na chamada. Assim, o botão "Avaliar agora" do portal do aluno abre
-- o formulário com inputs e com os critérios reais cadastrados pelo professor.
--
-- Uso: mysql -u root -p < database/04_dev_sessao_avaliacao.sql
-- Pode ser reaplicado: os dados anteriores dessa sessão são removidos primeiro.
-- ============================================================================

USE gestao_seminarios;

DELETE FROM avaliacoes_notas WHERE seminario_data_id = 2;
DELETE FROM avaliacoes_feedbacks WHERE seminario_data_id = 2;
DELETE FROM grupo_alunos WHERE grupo_id IN (SELECT id FROM grupos WHERE seminario_data_id = 2);
DELETE FROM grupos WHERE seminario_data_id = 2;
DELETE FROM presencas WHERE seminario_data_id = 2;

UPDATE seminario_datas SET status = 'em_andamento' WHERE id = 2;
UPDATE seminarios SET status = 'em_andamento' WHERE id = 1;

INSERT INTO grupos (seminario_data_id, tema, ordem_apresentacao, status)
VALUES (2, 'Grupo de demonstração: avaliação por pares', 1, 'pendente');

SET @grupo_demo = LAST_INSERT_ID();

-- O aluno 1 (perfil usado nos testes) não é integrante deste grupo.
INSERT INTO grupo_alunos (grupo_id, aluno_id) VALUES
(@grupo_demo, 4),
(@grupo_demo, 5);

-- O backend exige presença do avaliador na chamada da sessão.
INSERT INTO presencas (seminario_data_id, aluno_id, presente) VALUES
(2, 1, 1),
(2, 2, 1),
(2, 3, 1),
(2, 4, 1),
(2, 5, 1),
(2, 6, 0);
