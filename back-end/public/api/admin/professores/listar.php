<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');
exigir_identidade('admin');

$busca = trim((string) ($_GET['busca'] ?? ''));
$incluirInativos = (string) ($_GET['incluir_inativos'] ?? '') === '1';

$professores = [];
foreach (listar_usuarios($pdo, 'professor', $busca, $incluirInativos) as $linha) {
    $id = (int) $linha['id'];
    $professores[] = serializar_usuario($linha, turmas_do_usuario($pdo, 'professor', $id), 'professor');
}

responder_sucesso($professores);
