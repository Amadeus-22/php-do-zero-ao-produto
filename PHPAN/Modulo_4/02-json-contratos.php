<?php

// PHPAN · Módulo 4 · Aula 02 — JSON de entrada e saída (contratos estáveis)
// metadados em aulas.json · a ideia em 02-json-contratos.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Domain\Cliente\Cliente;
use App\Http\Kernel;
use App\Http\Request;
use App\Http\Resources\ClienteResource;
use App\Support\Container;

// A partir do Módulo 5 a API exige Bearer e o painel exige sessão. Esta aula é
// anterior a isso: entra autenticada para continuar exercitando o que ensina.
bancoDaAula();
$token = tokenDeAula();
logadoNoPainel();
$router = Kernel::router();

titulo('Aula 2 — JSON de entrada e saída (contratos estáveis)');

secao('O Resource decide EXATAMENTE quais campos saem');

$cliente = Cliente::novo('Ana Souza', 'ana@exemplo.com', '11999990000');
$saida = ClienteResource::um($cliente);

checa(
    'o contrato tem exatamente os campos previstos',
    array_keys($saida) === ['id', 'nome', 'email', 'telefone', 'ativo', 'criado_em'],
    implode(', ', array_keys($saida)),
);
checa('a data sai como string ATOM', is_string($saida['criado_em']), $saida['criado_em']);
nota('json_encode do DateTimeImmutable cru daria {"date":...,"timezone_type":3,...}.');

secao('Envelope de sucesso: sempre {"data": ...}');

$criado = $router->resolver(Request::comToken('POST', '/api/v1/clientes', $token, ['nome' => 'Ana Souza', 'email' => 'ana@exemplo.com']));
$corpo = json_decode($criado->body, true);

checa('resposta de sucesso tem a chave data', array_key_exists('data', $corpo), '');
checa('e NÃO tem a chave error', !array_key_exists('error', $corpo), '');
checa('criação devolve 201', $criado->status === 201, '');

secao('Envelope de erro: sempre {"error": {code, message, details?}}');

$erro = json_decode($router->resolver(Request::comToken('POST', '/api/v1/clientes', $token, ['nome' => '', 'email' => 'x']))->body, true);

checa('erro traz code legível por máquina', ($erro['error']['code'] ?? '') === 'validation_failed', $erro['error']['code'] ?? '');
checa('erro traz message legível por humano', isset($erro['error']['message']), $erro['error']['message'] ?? '');
checa('details separa o erro POR CAMPO', isset($erro['error']['details']['nome'], $erro['error']['details']['email']), 'o front sabe onde marcar');

$semDetalhe = json_decode($router->resolver(Request::comToken('GET', '/api/v1/clientes/999', $token))->body, true);
checa('erro sem detalhe não inventa details vazio', !isset($semDetalhe['error']['details']), 'chave só aparece quando há o quê dizer');

secao('MASS ASSIGNMENT: campo extra no JSON é simplesmente ignorado');

$ataque = $router->resolver(Request::comToken('POST', '/api/v1/clientes', $token, [
    'nome' => 'Bruno Lima',
    'email' => 'bruno@exemplo.com',
    'id' => 999,              // tentando forçar o id
    'senha_hash' => 'hack',   // tentando gravar campo que nem existe
    'ativo' => false,         // tentando nascer inativo
]));
$dados = json_decode($ataque->body, true)['data'];

checa('o id enviado pelo cliente foi ignorado', $dados['id'] === 2, "id={$dados['id']}, não 999");
checa('senha_hash não entrou no contrato', !array_key_exists('senha_hash', $dados), '');
checa('ativo continua sendo decidido pelo domínio', $dados['ativo'] === true, 'Cliente::novo() nasce ATIVO');
nota('A defesa é montar $dados campo a campo — nunca passar $request->body inteiro.');

secao('Contrato estável = o painel do curso também depende dele');

$lista = json_decode($router->resolver(Request::comToken('GET', '/api/v1/clientes', $token))->body, true);
checa('lista devolve data + meta', isset($lista['data'], $lista['meta']), 'formato fixo');
nota('Remover um campo daqui quebraria o JS da aula 5 sem aviso — por isso versiona-se.');

fecharAula();
