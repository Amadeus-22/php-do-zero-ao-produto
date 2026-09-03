<?php

// PHPAN · Módulo 1 · Aula 01 — O salto do PHPIAN: o que muda no dia a dia
// metadados em aulas.json · a ideia em 01-salto-do-phpian.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Domain\Cliente\Cliente;
use App\Domain\Cliente\ClienteInvalido;

titulo('Aula 1 — O salto do PHPIAN');

secao('O "antes": SQL por interpolação (estilo Mini CRM)');

// Trecho original da aula:
//     $pdo->exec("INSERT INTO clientes (nome, email) VALUES ('$nome', '$email')");
// Sem banco aqui — montamos a MESMA string para ver o que o atacante consegue.
$montarQueryInsegura = static fn (string $nome, string $email): string
    => "INSERT INTO clientes (nome, email) VALUES ('{$nome}', '{$email}')";

$queryNormal = $montarQueryInsegura('Ana Souza', 'ana@exemplo.com');
nota('entrada normal  -> ' . $queryNormal);

$nomeMalicioso = "x', 'y'); DROP TABLE clientes; --";
$queryAtaque = $montarQueryInsegura($nomeMalicioso, 'qualquer@exemplo.com');
nota('entrada hostil  -> ' . $queryAtaque);

checa(
    'entrada do usuário consegue fechar a string e emendar comando',
    str_contains($queryAtaque, 'DROP TABLE'),
    'a query deixou de ser um INSERT só',
);

// O outro defeito do "antes": a view ecoava o valor cru, sem htmlspecialchars.
// (CUIDADO: escrever a tag de fechamento dentro de um comentário // encerra o
//  bloco PHP de verdade — foi assim que este arquivo quebrou na primeira versão.)
$nomeComScript = '<script>alert(1)</script>';
checa(
    'saída sem escape devolveria o script intacto (XSS)',
    !str_contains($nomeComScript, '&lt;'),
    'htmlspecialchars daria: ' . htmlspecialchars($nomeComScript),
);

secao('O "depois": a mesma responsabilidade, em camadas');

// Domínio: a regra mora num lugar só e não tem como ser contornada.
$cliente = Cliente::novo('  Ana Souza  ', 'ana@exemplo.com');
checa('Cliente::novo() cria a entidade', $cliente->nome() === 'Ana Souza', 'nome normalizado com trim');
checa('nasce sem id (ainda não persistido)', $cliente->id() === null, '$id = null');
checa('nasce ativo', $cliente->estaAtivo(), 'StatusCliente::ATIVO');

checaExcecao(
    'e-mail inválido é recusado na criação',
    ClienteInvalido::class,
    static fn () => Cliente::novo('Ana', 'texto sem arroba'),
);

checaExcecao(
    'nome vazio é recusado na criação',
    ClienteInvalido::class,
    static fn () => Cliente::novo('   ', 'ana@exemplo.com'),
);

// Encapsulamento: não existe caminho para deixar o objeto inválido depois.
checaExcecao(
    'não dá para escrever direto na propriedade',
    \Error::class,
    static function () use ($cliente) {
        /** @phpstan-ignore-next-line demonstração proposital */
        $cliente->nome = 'quebrado';
    },
);

secao('Onde nenhum SQL aparece');

$arquivos = [
    'Domínio      ' => __DIR__ . '/../crm-produto/src/Domain/Cliente/Cliente.php',
    'Contrato     ' => __DIR__ . '/../crm-produto/src/Domain/Cliente/RepositorioDeClientes.php',
    'Aplicação    ' => __DIR__ . '/../crm-produto/src/Application/Cliente/CadastrarCliente.php',
];

foreach ($arquivos as $camada => $caminho) {
    // php_strip_whitespace: sem isto a palavra "delete" do COMENTÁRIO
    // "soft delete" contaria como SQL — falso positivo que já me pegou antes.
    $conteudo = php_strip_whitespace($caminho);
    checa(
        trim($camada) . ': sem SQL e sem PDO',
        preg_match('/\b(SELECT|INSERT|UPDATE|DELETE|PDO)\b/i', $conteudo) === 0,
        basename($caminho),
    );
}

nota('A camada de aplicação não sabe se os dados vão para MySQL, SQLite ou arquivo.');
nota('Essa é a virada do PHPAN: separar O QUE o negócio faz de COMO é persistido.');

fecharAula();
