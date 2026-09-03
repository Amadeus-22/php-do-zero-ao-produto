<?php

// PHPIAN · Módulo 5 · Aula 3 — Classes e objetos (intro)
// metadados em aulas.json (5-3)

class Contato
{
    public function __construct(
        public string $nome,
        public string $email,
        public ?string $telefone = null,
    ) {}

    public function iniciais(): string
    {
        $partes = preg_split('/\s+/', trim($this->nome)) ?: [];
        $ini = '';
        foreach ($partes as $p) {
            $ini .= mb_strtoupper(mb_substr($p, 0, 1));
        }
        return $ini;
    }
}

$c = new Contato('Ana Silva', 'ana@email.com');
echo $c->iniciais(); // AS