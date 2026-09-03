<?php

// PHPAN · Módulo 3 · Aula 05 — Validação centralizada e feedback de formulário
// metadados em aulas.json · a ideia em 05-validacao-formularios.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Http\Kernel;
use App\Http\Request;
use App\Support\Container;
use App\Support\Csrf;
use App\Support\Validator;
use App\Validation\ClienteValidator;

// A partir do Módulo 5 a API exige Bearer e o painel exige sessão. Esta aula é
// anterior a isso: entra autenticada para continuar exercitando o que ensina.
bancoDaAula();
$token = tokenDeAula();
logadoNoPainel();

titulo('Aula 5 — Validação centralizada e feedback de formulário');

secao('Validator genérico: regras encadeadas, erro por campo');

$v = (new Validator(['nome' => '', 'email' => 'sem-arroba']))
    ->required('nome', 'Informe o nome.')
    ->email('email', 'E-mail inválido.');

checa('encadeamento devolve self', $v instanceof Validator, 'leitura de cima para baixo');
checa('falhou() acusa erro', $v->falhou(), '');
checa('erro é agrupado POR CAMPO', array_keys($v->erros()) === ['nome', 'email'], 'não uma string solta');

$ok = (new Validator(['nome' => 'Ana']))->required('nome', 'x');
checa('dado válido não gera erro', !$ok->falhou(), '');

secao('ClienteValidator: fonte ÚNICA de regra de formato');

$erros = ClienteValidator::validar(['nome' => '', 'email' => '', 'telefone' => str_repeat('9', 30)])->erros();
checa('nome obrigatório', isset($erros['nome']), $erros['nome'][0] ?? '');
checa('email obrigatório', isset($erros['email']), $erros['email'][0] ?? '');
checa('telefone tem limite de tamanho', isset($erros['telefone']), $erros['telefone'][0] ?? '');

$nomeLongo = ClienteValidator::validar(['nome' => str_repeat('a', 121), 'email' => 'a@b.com'])->erros();
checa('nome > 120 caracteres é recusado', isset($nomeLongo['nome']), 'max()');

secao('Formato NÃO consulta banco; negócio SIM');

$fonteValidator = (string) file_get_contents(__DIR__ . '/../crm-produto/src/Support/Validator.php');
checa('Validator não conhece PDO nem repositório', !preg_match('/PDO|Repositorio/i', $fonteValidator) , 'síncrono, sem I/O');
nota('Duplicidade de e-mail precisa de consulta -> mora no ClienteService (aula 3).');
nota('Ordem no controller: valida formato primeiro (barato), só então consulta.');

secao('O formulário devolvendo erro E o que o usuário já digitou');

$_SESSION['csrf_token'] = null;
$token = Csrf::token();

$resp = Kernel::router()->resolver(Request::falsa('POST', '/clientes', [
    '_token' => $token,
    'nome' => '',
    'email' => 'email-quebrado',
    'telefone' => '11999990000',
]));

checa('formulário inválido devolve 422', $resp->status === 422, "HTTP {$resp->status}");
checa('a mensagem do campo aparece na tela', str_contains($resp->body, 'Informe o nome do cliente.'), '');
checa('OLD INPUT: o telefone digitado volta preenchido', str_contains($resp->body, 'value="11999990000"'), 'o usuário não redigita tudo');
nota('Sem old input, o usuário erra uma letra, perde tudo e desiste na 3ª tentativa.');

secao('Caminho feliz: mesma rota, agora aceita');

$ok = Kernel::router()->resolver(Request::falsa('POST', '/clientes', [
    '_token' => $token,
    'nome' => 'Ana Souza',
    'email' => 'ana@exemplo.com',
]));
checa('dado válido redireciona (302)', $ok->status === 302, 'PRG: post-redirect-get');
checa('vai para a listagem', ($ok->headers['Location'] ?? '') === '/clientes', '');

fecharAula();
