<?php

// PHPIAN · Módulo 4 · Aula 1 — Request e response
// metadados em aulas.json (4-1)

$server = $_SERVER ?? [];
echo $server['REQUEST_METHOD'] ?? '';
echo $server['HTTP_USER_AGENT'] ?? '';
