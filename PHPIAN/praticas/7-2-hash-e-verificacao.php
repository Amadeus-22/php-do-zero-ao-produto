<?php

// PHPIAN · Módulo 7 · Aula 2 — Hash de senhas
// Prática: "Crie um script que gera hash de uma senha e outro que verifica se a
// digitação confere."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 7-2 — hash e verificação de senha');

$raiz = areaTemporaria('7-2');
$cofre = $raiz . '/hash.txt';

secao('Script 1 — gera o hash');

file_put_contents($raiz . '/gerar.php', <<<'PHP'
<?php
declare(strict_types=1);
$senha = $argv[1] ?? '';
if ($senha === '') { fwrite(STDERR, "uso: php gerar.php SENHA\n"); exit(1); }
$hash = password_hash($senha, PASSWORD_DEFAULT);
file_put_contents(__DIR__ . '/hash.txt', $hash);
echo $hash;
PHP);

$senha = 'senha-de-estudo';
$hash = trim((string) shell_exec(sprintf('php %s %s', escapeshellarg($raiz . '/gerar.php'), escapeshellarg($senha))));

nota($hash);
checa('gerou um hash', $hash !== '');
checa('salvou no arquivo', trim((string) file_get_contents($cofre)) === $hash);
checa('a senha pura NÃO aparece no hash', !str_contains($hash, $senha), 'é hash, não criptografia reversível');

secao('Script 2 — verifica a digitação');

file_put_contents($raiz . '/verificar.php', <<<'PHP'
<?php
declare(strict_types=1);
$digitada = $argv[1] ?? '';
$hash = trim((string) file_get_contents(__DIR__ . '/hash.txt'));
echo password_verify($digitada, $hash) ? 'CONFERE' : 'NAO CONFERE';
PHP);

$verificar = static fn (string $tentativa): string => trim((string) shell_exec(
    sprintf('php %s %s', escapeshellarg($raiz . '/verificar.php'), escapeshellarg($tentativa))
));

checa('a senha certa CONFERE', $verificar($senha) === 'CONFERE');
foreach (['senha-de-Estudo' => 'maiúscula diferente', 'senha-de-estud' => 'faltando letra', 'senha-de-estudo ' => 'espaço no fim', '' => 'vazia', 'outra' => 'outra senha'] as $errada => $porque) {
    checa(sprintf('"%s" NÃO confere', $errada), $verificar($errada) === 'NAO CONFERE', $porque);
}

secao('Como o hash é por dentro');

$info = password_get_info($hash);
checa('algoritmo bcrypt (o PASSWORD_DEFAULT atual)', $info['algoName'] === 'bcrypt', $info['algoName']);
checa('o hash tem 60 caracteres', strlen($hash) === 60);
checa('começa com $2y$', str_starts_with($hash, '$2y$'));
checa('o custo está embutido', isset($info['options']['cost']), 'cost=' . ($info['options']['cost'] ?? '?'));

secao('O sal — por que dois hashes da MESMA senha diferem');

$h1 = password_hash($senha, PASSWORD_DEFAULT);
$h2 = password_hash($senha, PASSWORD_DEFAULT);
checa('dois hashes da mesma senha são DIFERENTES', $h1 !== $h2, 'cada um tem seu sal aleatório');
checa('mas os dois verificam a mesma senha', password_verify($senha, $h1) && password_verify($senha, $h2));
nota('é isso que impede rainbow table: o atacante teria de quebrar cada linha isolada');

secao('O callout: "nunca MD5/SHA1 na mão"');

$md5 = md5($senha);
checa('MD5 do mesmo texto é SEMPRE igual', md5($senha) === $md5, $md5);
checa('MD5 não tem sal', strlen($md5) === 32);
// Velocidade é o problema: hash rápido = ataque rápido.
$t0 = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    md5($senha . $i);
}
$tempoMd5 = microtime(true) - $t0;
$t0 = microtime(true);
password_hash($senha, PASSWORD_DEFAULT);
$tempoBcrypt = microtime(true) - $t0;
checa('1000 MD5 são mais rápidos que 1 bcrypt',
    $tempoMd5 < $tempoBcrypt,
    sprintf('1000×md5=%.1fms · 1×bcrypt=%.1fms', $tempoMd5 * 1000, $tempoBcrypt * 1000));
nota('lentidão é a defesa: o atacante testa milhares por segundo em vez de bilhões');

secao('password_needs_rehash');

checa('hash atual não precisa de rehash', !password_needs_rehash($hash, PASSWORD_DEFAULT));
$fraco = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 4]);
checa('hash com custo baixo PRECISA de rehash', password_needs_rehash($fraco, PASSWORD_DEFAULT, ['cost' => 12]),
    'é o gancho para reforçar o hash no próximo login, sem pedir a senha de novo');
checa('e o hash fraco ainda verifica normalmente', password_verify($senha, $fraco),
    'o usuário entra, e o sistema atualiza por baixo');

secao('Senha nunca em texto puro');

// Simulação de vazamento: o que o atacante leva se o banco for copiado.
$banco = ['ana@exemplo.com' => $hash];
$vazado = json_encode($banco);
checa('o vazamento não contém a senha', !str_contains($vazado, $senha));
checa('e não dá para reverter o hash', password_verify('qualquer-chute', $hash) === false);

fecharPratica();
