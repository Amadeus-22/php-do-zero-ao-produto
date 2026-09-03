<?php

// PHPAN · Módulo 5 · Aula 04 — Reset de senha por e-mail
// metadados em aulas.json · a ideia em 04-reset-senha-email.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Auth\ResetSenhaService;
use App\Domain\Notificacao\RemetenteDeEmail;
use App\Domain\Usuario\Papel;
use App\Domain\Usuario\Usuario;
use App\Support\Container;

$pdo = bancoDaAula();

/** Espião: guarda o e-mail em memória para a aula inspecionar o link. */
$remetente = new class implements RemetenteDeEmail {
    /** @var list<array{para: string, corpo: string}> */
    public array $enviados = [];

    public function enviar(string $destinatario, string $assunto, string $corpo): void
    {
        $this->enviados[] = ['para' => $destinatario, 'corpo' => $corpo];
    }
};

$servico = new ResetSenhaService(
    $pdo,
    Container::repositorioDeUsuarios(),
    $remetente,
    Container::tokenService(),
    'http://localhost:8000',
);

$tokenDoUltimoEmail = static function () use ($remetente): string {
    $ultimo = end($remetente->enviados);
    preg_match('/token=([0-9a-f]{64})/', $ultimo === false ? '' : $ultimo['corpo'], $m);

    return $m[1] ?? '';
};

$usuario = Container::repositorioDeUsuarios()->salvar(
    Usuario::novo('Ana Vendedora', 'ana@exemplo.com', 'senha-de-estudo', Papel::VENDEDOR),
);
$id = (int) $usuario->id();

titulo('Aula 4 — Reset de senha por e-mail');

secao('PASSO 1 — pedido: a resposta não pode revelar se a conta existe');

$servico->solicitar('ninguem@exemplo.com');
checa('e-mail inexistente: nada é enviado', $remetente->enviados === [], '');
checa('e nenhum registro é criado', (int) $pdo->query('SELECT COUNT(*) FROM resets_senha')->fetchColumn() === 0, '');

$servico->solicitar('ana@exemplo.com');
checa('e-mail existente: link enviado', count($remetente->enviados) === 1, $remetente->enviados[0]['para']);

nota('O controller responde a MESMA frase nos dois casos:');
nota('"Se esse e-mail estiver cadastrado, você vai receber um link."');
nota('Resposta diferente = user enumeration: o atacante descobre quem tem conta.');

secao('PASSO 2 — o token');

$token = $tokenDoUltimoEmail();
checa('64 hex = random_bytes(32)', strlen($token) === 64, 'nada de uniqid() ou rand()');

$hashes = $pdo->query('SELECT token_hash FROM resets_senha')->fetchAll(PDO::FETCH_COLUMN);
checa('guardado como hash', in_array(hash('sha256', $token), $hashes, true), '');
checa('o token em claro NÃO está no banco', !in_array($token, $hashes, true), '');

$expira = $pdo->query('SELECT expira_em FROM resets_senha')->fetchColumn();
checa('tem prazo de validade', $expira !== false && $expira > date('Y-m-d H:i:s'), "expira em {$expira}");

secao('Pedido novo invalida o anterior');

$primeiro = $token;
$servico->solicitar('ana@exemplo.com');
$segundo = $tokenDoUltimoEmail();

checa('o link ANTIGO deixa de funcionar', !$servico->redefinir($primeiro, 'senha-nova-123'), 'usado_em preenchido');
nota('Se o usuário pediu 3 vezes, só o último link vale — os outros viram lixo inerte.');

secao('PASSO 3 — trocar a senha derruba o resto');

// simula sessão de API que o invasor teria deixado aberta
Container::tokenService()->emitirPar($id);
$ativosAntes = (int) $pdo->query('SELECT COUNT(*) FROM tokens WHERE revogado_em IS NULL')->fetchColumn();

checa('havia token ativo antes', $ativosAntes === 2, "{$ativosAntes} ativos");
checa('redefinição com token válido funciona', $servico->redefinir($segundo, 'senha-nova-123'), '');

$ativosDepois = (int) $pdo->query('SELECT COUNT(*) FROM tokens WHERE revogado_em IS NULL')->fetchColumn();
checa('todos os tokens do usuário foram revogados', $ativosDepois === 0, "{$ativosDepois} ativos");
nota('Se a troca foi motivada por invasão, deixar a sessão do invasor viva anula o reset.');

$atualizado = Container::repositorioDeUsuarios()->buscarPorId($id);
checa('a senha nova funciona', $atualizado?->senhaConfere('senha-nova-123') === true, '');
checa('a senha antiga não funciona mais', $atualizado?->senhaConfere('senha-de-estudo') === false, '');

secao('O token serve UMA vez');

checa('reapresentar o mesmo token falha', !$servico->redefinir($segundo, 'outra-senha-123'), 'usado_em já preenchido');

secao('Token expirado');

$servico->solicitar('ana@exemplo.com');
$novo = $tokenDoUltimoEmail();
$pdo->exec('UPDATE resets_senha SET expira_em = DATE_SUB(NOW(), INTERVAL 1 MINUTE) WHERE usado_em IS NULL');

checa('link vencido não redefine', !$servico->redefinir($novo, 'senha-nova-456'), 'expira_em > NOW()');
nota('Um link de reset de 2019 que ainda funciona é porta aberta permanente.');

secao('Senha nova também é validada');

$servico->solicitar('ana@exemplo.com');
checa('senha curta é recusada', !$servico->redefinir($tokenDoUltimoEmail(), 'curta'), 'mínimo 8 caracteres');

secao('O que NUNCA deve ir para o log');

nota('Nunca logar a URL completa do link de reset: o log tem retenção longa e é');
nota('lido por mais gente que o banco. O token no log é uma conta comprometida.');

fecharAula();
