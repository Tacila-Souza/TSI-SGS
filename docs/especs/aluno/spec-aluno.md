> **Status:** especificação funcional de referência.
> **Leia antes:** `README.md`, `docs/documentacao_de_contextualizacao_tecnica.md` e `docs/contrato_api_frontend.md`.
> **Fonte de verdade:** schema oficial em `/database`; catálogo de endpoints na seção 6.3; contrato JSON no documento de contrato. Os blocos SQL deste arquivo são ilustrativos e não devem ser copiados.

# ESPECIFICAÇÃO TÉCNICA: PORTAL DO ALUNO (AVALIAÇÃO E HISTÓRICO)

**1\. Identificação e Objetivo**

* **Rotas canônicas:** `/aluno/seminarios`, `/aluno/seminarios/{id}/ao-vivo` (avaliação ao vivo) e `/aluno/resultados` (resultados consolidados). A rota de UI não é o endpoint da API; o endpoint permanece o da seção 6.3.7.  

* **Rotas legadas:** `/aluno/seminarios/{id}/avaliar` e `/aluno/seminarios/{id}/resultados` aparecem em versões antigas da spec, mas não existem no protótipo. Usar as rotas canônicas acima.  

* **Perfil:** Aluno  

* **Objetivo:** Visualizar o histórico de seminários das disciplinas cursadas, consultar os resultados das sessões concluídas e participar como avaliador (Peer Review) nas apresentações ao vivo.

**2\. Regras de Negócio e Comportamento**

* **RN-001 \- Histórico de Seminários:** Na rota principal (/aluno/seminarios), o sistema consulta as tabelas disciplina\_aluno e seminarios para listar automaticamente todos os seminários das disciplinas em que o aluno está matriculado (agendados, em andamento e concluídos).  

* **RN-002 \- Visualização de Resultados:** Para seminários com status "concluído", o aluno pode visualizar o ranking geral e a média final dos grupos na rota `/aluno/resultados`.  

  1. *Regra de privacidade:* O aluno só pode visualizar os feedbacks e as notas detalhadas individuais referentes ao seu próprio grupo.  

* **RN-003 \- Permissão para Avaliar ao Vivo:** O botão "Avaliar Apresentação" (acesso à rota `/aluno/seminarios/{id}/ao-vivo`) só é liberado quando todas as condições abaixo forem atendidas:  

  1. A data/sessão do seminário estiver com status ativo / "em andamento".  

  2. A tabela presencas atestar que o aluno está marcado como presente na data da apresentação.  

  3. O grupo que está se apresentando no momento não tiver o aluno identificado por `aluno_id` como integrante (bloqueio de autoavaliação).  

* **RN-004 \- Unicidade de Avaliação:** Cada aluno elegível pode registrar apenas uma única avaliação por grupo avaliado.

**3\. Dinâmica de Avaliação e Interface (Front-end e Back-end)**

> **Identidade no MVP:** todos os endpoints do aluno exigem `?aluno_id={id}`. Não há autenticação real; a limitação está registrada na seção 6.3.15 do documento técnico.

* **Espelhamento com Escopo Reduzido:** O formulário de avaliação do aluno segue a mesma estrutura visual do formulário do professor, carregando dinamicamente os mesmos critérios definidos na Etapa 1\. Entretanto, todos os controles administrativos e de condução da aula são ocultados (sem cronômetro, sem edição de lista de presença, sem gestão de grupos e sem botão de finalizar data).  

* **Campos de Avaliação:**  

  * **Notas por Critério:** O aluno insere notas (0 a 10\) para os critérios coletivos (aplicadas ao grupo todo) e para os critérios individuais (para cada integrante no palco).  

  * **Feedback Escrito:** Campo de texto descritivo onde o aluno registra seu parecer/relato sobre a apresentação do grupo.  

* **Persistência de Dados:** Ao submeter o formulário (`POST /api/aluno/avaliacoes/salvar.php?aluno_id={id}&data_id={id}&grupo_id={id}`), os registros são salvos na tabela avaliacoes_notas com tipo_avaliador = 'aluno' e avaliador_id igual ao aluno_id informado na requisição, acompanhados do feedback textual vinculado ao grupo avaliado. A avaliação real do aluno é persistida; o mock do professor cobre avaliações ausentes e não sobrescreve avaliações reais.

---

**Manutenção:** qualquer alteração de endpoint, campo, enum ou regra deve ser refletida também em `docs/contrato_api_frontend.md` e na seção 6.3 do documento técnico.

