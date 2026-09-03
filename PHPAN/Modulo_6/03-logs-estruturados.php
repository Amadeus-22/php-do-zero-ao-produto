<?php

// PHPAN · Módulo 6 · Aula 03 — Logs estruturados
// metadados em aulas.json · a ideia em 03-logs-estruturados.md

declare(strict_types=1);

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

use App\Log\Logger;
use App\Log\LoggerFabrica;
use App\Log\Nivel;

$arquivo = sys_get_temp_dir() . '/aula-log.jsonl';
@unlink($arquivo);
$logger = new Logger($arquivo);

$linhas = static function () use ($arquivo): array {
    return array_map(
        static fn (string $l): array => json_decode($l, true),
        array_values(array_filter(explode("\n", (string) file_get_contents($arquivo)))),
    );
};

titulo('Aula 3 — Logs estruturados');

secao('Em produção não existe var_dump na tela');

nota('O que aconteceu só existe se ficou registrado. E texto livre');
nota('("Erro ao salvar cliente") não dá para filtrar, buscar nem agregar.');

secao('Uma linha = um objeto JSON (JSON Lines)');

$logger->info('cliente criado', ['cliente_id' => 517]);
$logger->error('falha ao criar cliente', ['excecao' => 'PDOException']);

$l = $linhas();
checa('duas linhas independentes', count($l) === 2, '');
checa('cada uma é um JSON válido', isset($l[0]['nivel'], $l[1]['nivel']), '');
checa('com timestamp ATOM', (bool) preg_match('/^\d{4}-\d{2}-\d{2}T/', $l[0]['timestamp']), $l[0]['timestamp']);

echo "\n  Exemplo de linha gerada:\n  ";
echo json_encode($l[0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";

secao('Por que isso é pesquisável');

$conteudo = (string) file_get_contents($arquivo);
$erros = array_filter(explode("\n", $conteudo), static fn (string $x): bool => str_contains($x, '"nivel":"error"'));

checa('grep por nível funciona', count($erros) === 1, 'grep \'"nivel":"error"\' app.jsonl');
nota('Sem ferramenta especial: grep, jq, ou importar numa plataforma de log.');

secao('Os cinco níveis, e o que cada um significa');

$significado = [
    'debug' => 'detalhe técnico útil só em desenvolvimento',
    'info' => 'evento normal (login feito, job processado)',
    'warning' => 'estranho, mas o sistema seguiu (retry, rate limit acionado)',
    'error' => 'uma operação falhou (exceção capturada, API externa fora)',
    'critical' => 'o sistema está comprometido (banco inacessível)',
];
foreach (Nivel::cases() as $nivel) {
    printf("  %-10s %s\n", $nivel->value, $significado[$nivel->value]);
}
nota('Se tudo é error, nada é error: o alerta perde sentido e ninguém olha mais.');

secao('CONTEXTO é o que torna o log útil');

@unlink($arquivo);
$logger = new Logger($arquivo);
$contexto = LoggerFabrica::contextoBase();
$logger->info('cliente criado', [...$contexto, 'cliente_id' => 517]);

$l = $linhas()[0]['contexto'];
checa('tem request_id', isset($l['request_id']), $l['request_id']);
checa('e usuario_id (null no CLI)', array_key_exists('usuario_id', $l), '');
checa('mais o dado do evento', $l['cliente_id'] === 517, '');

checa('o request_id é estável na mesma execução', LoggerFabrica::requestId() === $l['request_id'], 'correlaciona a requisição inteira');
nota('"erro ao processar" sem request_id, no meio de 500 linhas parecidas,');
nota('obriga a adivinhar o que aconteceu.');

secao('O QUE NUNCA PODE IR PARA O LOG');

@unlink($arquivo);
$logger = new Logger($arquivo);
$logger->error('falha no login', [
    'email' => 'ana@exemplo.com',
    'senha' => 'segredo123',
    'token' => 'abc123',
    'payload' => ['id' => 9, 'access' => 'tok-secreto'],
]);

$c = $linhas()[0]['contexto'];
checa('e-mail permanece (identifica o caso)', $c['email'] === 'ana@exemplo.com', '');
checa('senha é removida', $c['senha'] === '[REMOVIDO]', '');
checa('token é removido', $c['token'] === '[REMOVIDO]', '');
checa('e o filtro alcança contexto ANINHADO', $c['payload']['access'] === '[REMOVIDO]', '');
checa('sem afetar o resto do payload', $c['payload']['id'] === 9, '');

nota('Log tem retenção longa e é lido por mais gente que o banco de produção.');
nota('Trate como asset que também pode vazar — não como gaveta onde tudo cabe.');

secao('Log x auditoria (Módulo 5)');

printf("  %-12s %-26s %s\n", '', 'LOG', 'AUDITORIA');
printf("  %-12s %-26s %s\n", 'Para quem', 'devs', 'negócio/compliance');
printf("  %-12s %-26s %s\n", 'Apaga?', 'sim, rotaciona', 'nunca (append-only)');
printf("  %-12s %-26s %s\n", 'Retenção', 'dias/semanas', 'meses/anos');

@unlink($arquivo);
fecharAula();
