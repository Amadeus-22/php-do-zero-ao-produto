<?php

// PHPAN · Módulo 3 · Aula 04 — Views PHP: layouts e partials
// metadados em aulas.json · a ideia em 04-views-layouts.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Domain\Cliente\Cliente;
use App\Domain\Cliente\StatusCliente;
use App\Support\View;

titulo('Aula 4 — Views PHP: layouts e partials');

secao('View::e() — escape é a regra, não a exceção');

$hostil = '<script>alert("xss")</script>';
$escapado = View::e($hostil);
checa('tag vira entidade', !str_contains($escapado, '<script>'), $escapado);
checa('aspas também são escapadas', str_contains(View::e('a"b'), '&quot;'), 'ENT_QUOTES');

secao('Layout + partials: a página montada por camadas');

$html = View::render('clientes/index', ['titulo' => 'Clientes', 'clientes' => []]);

checa('o layout envolveu o conteúdo', str_contains($html, '<!doctype html>'), 'layouts/app.php');
checa('a partial de navegação entrou', str_contains($html, 'class="nav"'), 'partials/nav.php');
checa('a view da página entrou', str_contains($html, '<h1>Clientes</h1>'), 'clientes/index.php');
checa('o <title> veio dos dados', str_contains($html, '<title>Clientes</title>'), '$titulo');

secao('Sem layout: a mesma view, só o miolo');

$soMiolo = View::render('clientes/index', ['clientes' => []], layout: null);
checa('layout: null devolve só o conteúdo', !str_contains($soMiolo, '<!doctype html>'), 'útil para parciais via fetch');

secao('O escape protegendo dado real');

$malicioso = Cliente::reconstituir(
    id: 1,
    nome: '<script>roubarSessao()</script>',
    email: 'x@exemplo.com',
    status: StatusCliente::ATIVO,
    criadoEm: new DateTimeImmutable(),
);

$listagem = View::render('clientes/index', ['titulo' => 'Clientes', 'clientes' => [$malicioso]]);
checa('nome hostil NÃO virou script executável', !str_contains($listagem, '<script>roubarSessao'), 'View::e() na view');
checa('mas o texto continua visível ao usuário', str_contains($listagem, '&lt;script&gt;'), 'escapado, não removido');

secao('View não encontrada falha alto, não silenciosamente');

checaExcecao(
    'view inexistente lança RuntimeException',
    RuntimeException::class,
    static fn () => View::render('clientes/nao-existe'),
);

secao('Caminho relativo à raiz, não absoluto');

$fonte = (string) file_get_contents(__DIR__ . '/../crm-produto/src/Support/View.php');
checa('usa dirname(__DIR__, 2), não caminho fixo', str_contains($fonte, 'dirname(__DIR__, 2)'), 'não quebra se o projeto mudar de lugar');

fecharAula();
