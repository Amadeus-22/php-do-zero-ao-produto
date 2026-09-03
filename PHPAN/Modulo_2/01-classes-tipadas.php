<?php

// PHPAN · Módulo 2 · Aula 01 — Classes, propriedades tipadas e métodos com intenção
// metadados em aulas.json · a ideia em 01-classes-tipadas.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Domain\Cliente\Cliente;
use App\Domain\Cliente\ClienteInvalido;
use App\Domain\Cliente\StatusCliente;

titulo('Aula 1 — Classes, propriedades tipadas e métodos com intenção clara');

secao('declare(strict_types=1): o erro aparece no LUGAR CERTO');

$soma = static fn (int $a, int $b): int => $a + $b;

checaExcecao(
    'passar "10" onde se espera int lança TypeError',
    TypeError::class,
    static fn () => $soma('10', 5),
);
nota('Sem strict_types o PHP converteria "10" para 10 silenciosamente — e o bug');
nota('apareceria 3 camadas depois, num lugar que não tem nada a ver.');

secao('Toda propriedade tem tipo, e é private');

$reflexo = new ReflectionClass(Cliente::class);
$semTipo = [];
$publicas = [];

foreach ($reflexo->getProperties() as $prop) {
    if (!$prop->hasType()) {
        $semTipo[] = $prop->getName();
    }

    if ($prop->isPublic()) {
        $publicas[] = $prop->getName();
    }
}

checa('nenhuma propriedade sem tipo', $semTipo === [], count($reflexo->getProperties()) . ' propriedades');
checa('nenhuma propriedade public', $publicas === [], 'acesso só por método');

secao('readonly: imutabilidade onde faz sentido');

$readonly = array_map(
    static fn (ReflectionProperty $p): string => $p->getName(),
    array_filter($reflexo->getProperties(), static fn (ReflectionProperty $p): bool => $p->isReadOnly()),
);
sort($readonly);

checa('id, email e criadoEm são readonly', $readonly === ['criadoEm', 'email', 'id'], implode(', ', $readonly));
nota('E nome NÃO é: ele muda, mas por renomear(), que revalida.');
nota('Mutação sem controle é o problema — não mutação em si.');

$cliente = Cliente::novo('Ana Souza', 'ana@exemplo.com');

checaExcecao(
    'escrever direto na propriedade falha',
    Error::class,
    static function () use ($cliente) {
        /** @phpstan-ignore-next-line demonstração proposital */
        $cliente->nome = 'invadido';
    },
);

secao('Construtor privado + fábricas NOMEADAS');

checa('o construtor é privado', $reflexo->getConstructor()?->isPrivate() === true, 'não dá para instanciar direto');
checa('novo() existe', $reflexo->hasMethod('novo'), 'criação: valida tudo');
checa('reconstituir() existe', $reflexo->hasMethod('reconstituir'), 'carga do banco: não revalida');

$tresAnosAtras = new DateTimeImmutable('-3 years');
$antigo = Cliente::reconstituir(42, 'Cliente Antigo', 'antigo@exemplo.com', StatusCliente::INATIVO, $tresAnosAtras);

checa('reconstituir aceita data velha', $antigo->criadoEm() === $tresAnosAtras, 'registro de 3 anos atrás');
checa('e status inativo', !$antigo->estaAtivo(), '');
nota('Se reconstituir revalidasse "criadoEm = agora", carregar um registro');
nota('antigo do banco seria impossível. São responsabilidades diferentes.');

secao('Validação na criação — não tem como criar inválido');

checaExcecao('nome vazio', ClienteInvalido::class, static fn () => Cliente::novo('   ', 'a@b.com'));
checaExcecao('e-mail sem arroba', ClienteInvalido::class, static fn () => Cliente::novo('Ana', 'sem-arroba'));
checa('nome com espaço extra é normalizado', Cliente::novo('  Ana  ', 'a@b.com')->nome() === 'Ana', 'trim na fábrica');

secao('SEM setter genérico: o nome do método diz o que aconteceu');

$metodos = array_map(
    static fn (ReflectionMethod $m): string => $m->getName(),
    $reflexo->getMethods(ReflectionMethod::IS_PUBLIC),
);
$setters = array_filter($metodos, static fn (string $m): bool => str_starts_with($m, 'set'));

checa('nenhum método setX', $setters === [], implode(', ', $metodos));

$cliente->renomear('Ana S. Souza');
checa('renomear() funciona', $cliente->nome() === 'Ana S. Souza', '');
checaExcecao('e revalida', ClienteInvalido::class, static fn () => $cliente->renomear('  '));

$cliente->desativar();
checa('desativar() muda o status', !$cliente->estaAtivo(), '');

printf("\n  %-28s %s\n", "\$cliente->setStatus('inativo')", 'descreve troca de campo');
printf("  %-28s %s\n", '$cliente->desativar()', 'descreve o FATO DE NEGÓCIO');

secao('Tipos que valem conhecer');

$tipos = [
    '?int / ?string' => 'id antes de persistir (null = não salvo)',
    'list<Contato> no PHPDoc' => 'PHP não tem generics; o PHPStan lê isso',
    'self / static' => 'retorno de fábrica (Cliente::novo)',
    '\DateTimeImmutable' => 'toda data — nunca \DateTime mutável',
    'enum backed' => 'conjunto fechado (papel, status, canal)',
];
foreach ($tipos as $tipo => $uso) {
    printf("  %-26s %s\n", $tipo, $uso);
}

checa('criadoEm é DateTimeImmutable', $cliente->criadoEm() instanceof DateTimeImmutable, 'não DateTime');
nota('DateTime permite que qualquer código com a referência altere a data');
nota('por baixo dos panos. Immutable devolve nova instância em cada operação.');

fecharAula();
