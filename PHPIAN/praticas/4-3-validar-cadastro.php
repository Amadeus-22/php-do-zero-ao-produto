<?php

// PHPIAN · Módulo 4 · Aula 3 — Validação e sanitização
// Prática: "Valide um cadastro com nome (mín. 3 chars), e-mail válido e senha
// (mín. 8 chars). Liste os erros."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 4-3 — validar cadastro');

secao('O validador');

/** @return list<string> lista de erros; vazia = cadastro aceito */
function validarCadastro(array $dados): array
{
    $erros = [];

    // Sanitizar primeiro (limpa/normaliza), validar depois (decide se aceita) —
    // a ordem que a aula define.
    $nome = trim((string) ($dados['nome'] ?? ''));
    $email = trim((string) ($dados['email'] ?? ''));
    $senha = (string) ($dados['senha'] ?? '');   // senha NÃO leva trim: espaço é caractere válido

    if (mb_strlen($nome) < 3) {
        $erros[] = 'Nome precisa de ao menos 3 caracteres';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'E-mail inválido';
    }
    if (mb_strlen($senha) < 8) {
        $erros[] = 'Senha precisa de ao menos 8 caracteres';
    }

    return $erros;
}

$casos = [
    [['nome' => 'Ana Souza', 'email' => 'ana@exemplo.com', 'senha' => 'senha-de-estudo'], [], 'tudo certo'],
    [['nome' => 'Al', 'email' => 'ana@exemplo.com', 'senha' => 'senha-de-estudo'], ['Nome precisa de ao menos 3 caracteres'], 'nome curto'],
    [['nome' => 'Ana', 'email' => 'sem-arroba', 'senha' => 'senha-de-estudo'], ['E-mail inválido'], 'e-mail ruim'],
    [['nome' => 'Ana', 'email' => 'ana@exemplo.com', 'senha' => '1234567'], ['Senha precisa de ao menos 8 caracteres'], '7 caracteres'],
    [[], ['Nome precisa de ao menos 3 caracteres', 'E-mail inválido', 'Senha precisa de ao menos 8 caracteres'], 'nada enviado: 3 erros'],
];

foreach ($casos as [$entrada, $esperado, $porque]) {
    $erros = validarCadastro($entrada);
    checa(sprintf('%-30s -> %d erro(s)', $porque, count($erros)), $erros === $esperado,
        $erros === [] ? 'aceito' : implode(' · ', $erros));
}

secao('Os limites exatos');

checa('nome com 2 chars é recusado', validarCadastro(['nome' => 'Al', 'email' => 'a@b.co', 'senha' => '12345678']) !== []);
checa('nome com 3 chars é aceito', validarCadastro(['nome' => 'Ana', 'email' => 'a@b.co', 'senha' => '12345678']) === []);
checa('senha com 7 chars é recusada', validarCadastro(['nome' => 'Ana', 'email' => 'a@b.co', 'senha' => '1234567']) !== []);
checa('senha com 8 chars é aceita', validarCadastro(['nome' => 'Ana', 'email' => 'a@b.co', 'senha' => '12345678']) === []);

secao('mb_strlen, não strlen — o nome acentuado');

// "Ana" tem 3 letras; "Aná" tem 3 letras e 4 bytes. Com strlen os dois passam,
// mas "Zé" (2 letras, 3 bytes) passaria errado.
checa('mb_strlen("Zé") é 2 — recusa correta', mb_strlen('Zé') === 2);
checa('strlen("Zé") é 3 — aceitaria por engano', strlen('Zé') === 3);
checa('o validador recusa "Zé"', validarCadastro(['nome' => 'Zé', 'email' => 'a@b.co', 'senha' => '12345678']) !== []);

secao('Senha não leva trim');

// Espaço no meio ou nas pontas é caractere legítimo de senha; cortar reduz o
// espaço de busca e quebra o login de quem usou espaço de propósito.
checa('senha "  1234  " tem 8 chars e é aceita', validarCadastro(['nome' => 'Ana', 'email' => 'a@b.co', 'senha' => '  1234  ']) === []);

secao('Sanitizar x validar — a distinção da aula');

$sujo = '  ANA@EXEMPLO.COM  ';
$limpo = trim($sujo);
checa('sanitizar: tirou o espaço', $limpo === 'ANA@EXEMPLO.COM');
checa('validar: decide se aceita', filter_var($limpo, FILTER_VALIDATE_EMAIL) !== false);
checa('FILTER_VALIDATE_EMAIL aceita maiúsculo', filter_var('ANA@EXEMPLO.COM', FILTER_VALIDATE_EMAIL) !== false);

// FILTER_VALIDATE_INT devolve false para inválido e 0 para "0" — a confusão clássica.
checa('filter_var("0", INT) devolve int 0, não false', filter_var('0', FILTER_VALIDATE_INT) === 0);
checa('filter_var("abc", INT) devolve false', filter_var('abc', FILTER_VALIDATE_INT) === false);
checa('por isso se compara com === false, nunca com !', filter_var('0', FILTER_VALIDATE_INT) !== false,
    'if (!$idade) trataria idade 0 como erro');

$idade = filter_var('17', FILTER_VALIDATE_INT);
checa('idade 17 é recusada pela regra da aula', $idade === false || $idade < 18);
checa('idade 18 é aceita', filter_var('18', FILTER_VALIDATE_INT) >= 18);

fecharPratica();
