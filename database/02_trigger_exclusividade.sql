-- ============================================================================
-- SEGUNDO TRECHO

-- ARQUIVO 2: 02_trigger_exclusividade.sql
-- ============================================================================

USE gestao_seminarios;

DELIMITER $$

DROP TRIGGER IF EXISTS trg_grupo_alunos_exclusividade_data$$

CREATE TRIGGER trg_grupo_alunos_exclusividade_data
BEFORE INSERT ON grupo_alunos
FOR EACH ROW
BEGIN
    DECLARE v_seminario_data_id INT UNSIGNED;
    DECLARE v_conflito INT DEFAULT 0;

    SELECT seminario_data_id INTO v_seminario_data_id
    FROM grupos
    WHERE id = NEW.grupo_id;

    SELECT COUNT(*) INTO v_conflito
    FROM grupo_alunos ga
    INNER JOIN grupos g ON ga.grupo_id = g.id
    WHERE ga.aluno_id = NEW.aluno_id
      AND g.seminario_data_id = v_seminario_data_id;

    IF v_conflito > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Regra violada: Este aluno ja pertence a um grupo nesta mesma data de seminario.';
    END IF;
END$$

DROP TRIGGER IF EXISTS trg_grupo_alunos_exclusividade_update$$

CREATE TRIGGER trg_grupo_alunos_exclusividade_update
BEFORE UPDATE ON grupo_alunos
FOR EACH ROW
BEGIN
    DECLARE v_seminario_data_id_update INT UNSIGNED;
    DECLARE v_conflito_update INT DEFAULT 0;

    SELECT seminario_data_id INTO v_seminario_data_id_update
    FROM grupos
    WHERE id = NEW.grupo_id;

    SELECT COUNT(*) INTO v_conflito_update
    FROM grupo_alunos ga
    INNER JOIN grupos g ON ga.grupo_id = g.id
    WHERE ga.aluno_id = NEW.aluno_id
      AND g.seminario_data_id = v_seminario_data_id_update
      AND NOT (ga.grupo_id = OLD.grupo_id AND ga.aluno_id = OLD.aluno_id);

    IF v_conflito_update > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Regra violada: Este aluno ja pertence a um grupo nesta mesma data de seminario.';
    END IF;
END$$

DELIMITER ;