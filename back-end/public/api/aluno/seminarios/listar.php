<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');
$alunoId = exigir_identidade('aluno');

$seminarios = buscar_seminarios_matriculados($pdo, $alunoId);
$dados = [];

foreach ($seminarios as $seminario) {
    $seminarioId = (int) $seminario['id'];
    $datas = buscar_datas_seminario($pdo, $seminarioId);
    $criterios = buscar_criterios_seminario($pdo, $seminarioId);
    $gruposPorData = [];
    $presencaPorData = [];

    foreach ($datas as $data) {
        $dataId = (int) $data['id'];
        $gruposPorData[$dataId] = buscar_grupos_da_data($pdo, $dataId);
        $presencaPorData[$dataId] = buscar_presenca_da_data($pdo, $dataId);
    }

    $dados[] = serializar_seminario($seminario, $datas, $criterios, $gruposPorData, $presencaPorData);
}

responder_sucesso($dados);
