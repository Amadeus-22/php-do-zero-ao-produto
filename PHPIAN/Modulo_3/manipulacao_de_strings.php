<?php

// PHPIAN · Módulo 3 · Aula 3 — Manipulação de strings
// metadados em aulas.json (3-3)

$texto = "  PHP Iniciante  ";
echo trim($texto);
echo strtoupper($texto);
echo strlen("ação"); // cuidado com multibyte!
echo mb_strlen("ação"); // 4 — use mb_* para UTF-8

$slug = str_replace(' ', '-', strtolower(trim($texto)));