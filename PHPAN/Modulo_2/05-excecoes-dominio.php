<?php

// PHPAN · Módulo 2 · Aula 05 — Exceções de domínio
// metadados em aulas.json · a ideia em 05-excecoes-dominio.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Domain\Cliente\Cliente;
use App\Domain\Cliente\ClienteInvalido;
use App\Domain\Cliente\ClienteNaoEncontrado;
use App\Domain\Cliente\EmailJaCadastrado;
use App\Domain\ErroDeDominio;

titulo('Aula 5 — Exceções de domínio');

secao('O problema do die("erro")');

$problemas = [
    'die()/exit() encerra o processo' => 'inaceitável numa API, ou num teste que precisa capturar',
    'mensagem é string solta' => 'quem chama não distingue os erros sem str_contains — frágil',
    'não existe hierarquia' => 'não dá para dizer "capture qualquer erro de validação"',
];
foreach ($problemas as $problema => $consequencia) {
    printf("  %-38s %s\n", $problema, $consequencia);
}

secao('A hierarquia');

checa('ErroDeDominio é abstrata', (new ReflectionClass(ErroDeDominio::class))->isAbstract(), 'não se instancia direto');
checa('e estende DomainException', is_subclass_of(ErroDeDominio::class, DomainException::class), '');

foreach ([ClienteInvalido::class, ClienteNaoEncontrado::class, EmailJaCadastrado::class] as $classe) {
    checa('estende ErroDeDominio: ' . (new ReflectionClass($classe))->getShortName(), is_subclass_of($classe, ErroDeDominio::class), '');
}

secao('Fábricas NOMEADAS: o nome descreve a situação');

$erro = ClienteNaoEncontrado::comId(42);
checa('comId() monta a mensagem', str_contains($erro->getMessage(), '42'), $erro->getMessage());

$porEmail = ClienteNaoEncontrado::comEmail('ana@exemplo.com');
checa('comEmail() também', str_contains($porEmail->getMessage(), 'ana@exemplo.com'), $porEmail->getMessage());

secao('parent::__construct(): o esquecimento que zera getMessage()');

$duplicado = new EmailJaCadastrado('ana@exemplo.com');
checa('a mensagem chega à classe base', $duplicado->getMessage() !== '', $duplicado->getMessage());
checa('e o dado fica acessível ao chamador', $duplicado->email() === 'ana@exemplo.com', 'não só no texto');
nota('Sem parent::__construct(), getMessage() volta VAZIO — a mensagem nunca');
nota('chega na classe base, e o erro fica mudo no log.');

secao('CAPTURA ESCALONADA: do específico ao genérico');

$tratar = static function (callable $acao): array {
    try {
        $acao();

        return [200, 'ok'];
    } catch (EmailJaCadastrado $e) {
        return [409, $e->getMessage()];          // conflito de estado
    } catch (ErroDeDominio $e) {
        return [422, $e->getMessage()];          // qualquer regra de negócio
    } catch (Throwable $e) {
        return [500, 'Erro interno. Tente novamente.']; // não previsto
    }
};

[$status] = $tratar(static fn () => throw new EmailJaCadastrado('ana@exemplo.com'));
checa('EmailJaCadastrado -> 409', $status === 409, 'o catch mais específico venceu');

[$status, $msg] = $tratar(static fn () => Cliente::novo('', 'x'));
checa('ClienteInvalido -> 422', $status === 422, 'pegou no catch de ErroDeDominio');

[$status, $msg] = $tratar(static fn () => throw new RuntimeException('banco fora do ar'));
checa('exceção não prevista -> 500', $status === 500, '');
checa('e a mensagem interna NÃO vaza', !str_contains($msg, 'banco fora do ar'), $msg);
nota('A ORDEM importa: específico primeiro. Com ErroDeDominio antes de');
nota('EmailJaCadastrado, o 409 nunca aconteceria.');

secao('Erro de domínio x infraestrutura x bug');

printf("  %-14s %-38s %s\n", 'Domínio', 'e-mail já cadastrado', 'mensagem amigável (409/422)');
printf("  %-14s %-38s %s\n", 'Infraestrutura', 'banco fora do ar, timeout', 'log + mensagem genérica (500)');
printf("  %-14s %-38s %s\n", 'Programação', 'TypeError, ArgumentCountError', 'PHPStan e testes pegam antes');

secao('Traduzir erro TÉCNICO em erro de DOMÍNIO');

$repo = php_strip_whitespace(__DIR__ . '/../crm-produto/src/Infrastructure/Cliente/RepositorioDeClientesPdo.php');

checa('a infra captura PDOException', str_contains($repo, 'catch (PDOException'), '');
checa('reconhece o código 1062 do MySQL', str_contains($repo, '1062'), 'chave duplicada');
checa('e lança EmailJaCadastrado', str_contains($repo, 'throw new EmailJaCadastrado'), '');
checa('erro técnico genuíno sobe', str_contains($repo, 'throw $e;'), 'não é engolido');
nota('A infraestrutura sabe interpretar o erro do MySQL; o domínio não precisa');
nota('saber que MySQL existe.');

secao('Quando NÃO usar exceção');

nota('"Não encontrado" numa busca opcional é situação esperada: retorne null.');
nota('Exceção é para o que IMPEDE a operação de continuar.');

$fonteService = php_strip_whitespace(__DIR__ . '/../crm-produto/src/Application/Cliente/ClienteService.php');
checa('buscarPorId() devolve null', str_contains($fonteService, 'public function buscarPorId(int $id): ?Cliente'), 'busca opcional');
checa('buscar() lança', str_contains($fonteService, 'throw ClienteNaoEncontrado::comId($id)'), 'quando a ausência é erro');

secao('Nunca engolir em silêncio');

$vazios = [];
foreach (glob(__DIR__ . '/../crm-produto/src/**/*.php') ?: [] as $arquivo) {
    if (preg_match('/catch\s*\([^)]+\)\s*\{\s*\}/', php_strip_whitespace($arquivo)) === 1) {
        $vazios[] = basename($arquivo);
    }
}
checa('nenhum catch vazio no projeto', $vazios === [], $vazios === [] ? '' : implode(', ', $vazios));

fecharAula();
