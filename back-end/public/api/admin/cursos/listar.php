<?php
declare(strict_types=1);

require dirname(__DIR__, 4) . '/src/bootstrap.php';

exigir_metodo('GET');
exigir_identidade('admin');

responder_sucesso(array_map('serializar_curso', listar_cursos($pdo)));
