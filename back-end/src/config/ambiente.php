<?php

declare(strict_types=1);

function ambiente(string $chave, string $padrao = ''): string
{
    $valor = getenv($chave);

    return $valor === false ? $padrao : $valor;
}
