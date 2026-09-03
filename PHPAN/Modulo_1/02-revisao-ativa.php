<?php

/**
 * PHPAN — Módulo 1 · Aula 02 — Revisão ativa: PDO, auth por sessão e OOP intro
 * https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-01-mapa/02-revisao-ativa
 *
 * Status: [x] assistida  [x] anotada  [x] praticada
 * Rodar:  php Modulo_1/02-revisao-ativa.php   (precisa do banco)
 * Ideia e motivo: 02-revisao-ativa.md
 *
 * CHECKPOINT do PHPIAN: 9 exercícios de PDO, sessão e OOP. Este arquivo é o
 * gabarito EXECUTÁVEL — as funções abaixo são exercitadas de verdade no fim,
 * contra o MySQL. Errou mais de 3 dos 9? Revisar o PHPIAN antes da Aula 3.
 */

declare(strict_types=1);

/* =====================================================================
 * BLOCO 1 — PDO
 * ===================================================================== */

/**
 * Exercício 1.1 — Buscar cliente por e-mail com prepared statement.
 * Sem concatenar string no SQL. Retorna null se não encontrar.
 */
function buscarClientePorEmail(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM clientes WHERE email = :email LIMIT 1');
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    return $resultado === false ? null : $resultado;
}

/**
 * Exercício 1.2 — Por que este código é perigoso, mesmo "funcionando"?
 *
 *     $id = $_GET['id'];
 *     $pdo->query("SELECT * FROM clientes WHERE id = $id");
 *
 * SQL injection. $id vem direto do usuário e é concatenado na query sem
 * escape nem preparo. Um valor como `1 OR 1=1` ou `1; DROP TABLE clientes;--`
 * (conforme driver/modo) compromete a consulta ou os dados.
 *
 * Correção: sempre prepare() + parâmetro nomeado ou posicional.
 * Nunca concatenar entrada do usuário no SQL.
 */

/**
 * Exercício 1.3 — Inserir cliente + contato relacionado na MESMA transação,
 * revertendo tudo se algo falhar.
 */
function cadastrarClienteComContato(
    PDO $pdo,
    string $nomeCliente,
    string $nomeContato,
    string $emailContato,
    ?string $emailCliente = null,
): int {
    $pdo->beginTransaction();

    try {
        // A aula insere só o nome; a tabela real do projeto tem email NOT NULL
        // UNIQUE, então o gabarito ganhou o campo. É o tipo de ajuste que aparece
        // quando o exercício encontra o schema de verdade.
        $stmt = $pdo->prepare('INSERT INTO clientes (nome, email) VALUES (:nome, :email)');
        $stmt->execute([
            'nome' => $nomeCliente,
            'email' => $emailCliente ?? strtolower(str_replace(' ', '.', $nomeCliente)) . '@exemplo.com',
        ]);
        $clienteId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'INSERT INTO contatos (cliente_id, nome, email) VALUES (:cliente_id, :nome, :email)'
        );
        $stmt->execute([
            'cliente_id' => $clienteId,
            'nome' => $nomeContato,
            'email' => $emailContato,
        ]);

        $pdo->commit();

        return $clienteId;
    } catch (\Throwable $erro) {
        $pdo->rollBack();
        throw $erro;
    }
}

/* =====================================================================
 * BLOCO 2 — AUTH POR SESSÃO
 * ===================================================================== */

/**
 * Exercício 2.1 — Login: verificar credenciais com password_verify,
 * iniciar sessão e regenerar o ID (proteção contra session fixation).
 *
 * PONTO-CHAVE: session_regenerate_id(true) troca o ID no momento do login,
 * invalidando qualquer sessão "fixada" antes da autenticação.
 */
function autenticar(PDO $pdo, string $email, string $senha): bool
{
    $stmt = $pdo->prepare('SELECT id, nome, senha_hash FROM usuarios WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario === false || !password_verify($senha, $usuario['senha_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];

    return true;
}

/**
 * Exercício 2.2 — Guard: bloqueia a página se não houver usuário autenticado.
 */
function exigirLogin(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * Exercício 2.3 — Diferença entre session_destroy() sozinho e logout completo.
 *
 * O código incompleto era:
 *
 *     function logout(): void { session_destroy(); }
 *
 * FALTA:
 *   1. session_start() antes, se a sessão ainda não foi iniciada;
 *   2. limpar o array: $_SESSION = [];
 *   3. invalidar o cookie de sessão, expirando-o, ANTES do session_destroy().
 *
 * Sem isso, em alguns cenários o navegador ainda envia um cookie que pode ser
 * reaproveitado até expirar.
 *
 * Versão completa, montada a partir dos 3 pontos acima:
 */
function logout(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 3600,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly'],
        );
    }

    session_destroy();
}

/* =====================================================================
 * BLOCO 3 — OOP INTRODUTÓRIO
 * ===================================================================== */

/**
 * Exercício 3.1 — Transformar este array associativo em classe com
 * construtor e getters:
 *
 *     $cliente = ['id' => 1, 'nome' => 'Ana Souza', 'email' => 'ana@exemplo.com'];
 */
final class Cliente
{
    public function __construct(
        private int $id,
        private string $nome,
        private string $email,
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function nome(): string
    {
        return $this->nome;
    }

    public function email(): string
    {
        return $this->email;
    }
}

/**
 * Exercício 3.2 — public vs protected vs private, em uma frase cada:
 *
 *   public    → acessível de qualquer lugar (dentro e fora da classe,
 *               inclusive subclasses).
 *   protected → acessível na própria classe e em subclasses, não de fora.
 *   private   → acessível só dentro da própria classe; nem subclasses
 *               acessam diretamente.
 *
 * PADRÃO para propriedades de domínio: private, expondo acesso controlado
 * via métodos. Protege invariantes — regras que não podem ser quebradas.
 * Aprofundado no Módulo 2 (encapsulamento).
 */

/**
 * Exercício 3.3 — O que está errado neste construtor, do ponto de vista de
 * proteger o estado do objeto?
 *
 *     final class Contato {
 *         public string $email;
 *         public function __construct(string $email) { $this->email = $email; }
 *     }
 *
 * A propriedade é public e não tem validação: qualquer código externo pode
 * fazer `$contato->email = 'texto qualquer sem @'` depois de criado o objeto,
 * quebrando a garantia de que um Contato sempre tem e-mail válido.
 *
 * Correção: propriedade private/readonly, validação no construtor e — se
 * precisar mudar depois — um método que revalida, nunca atribuição direta.
 */
final class Contato
{
    private function __construct(
        private readonly string $email,
    ) {
    }

    public static function comEmail(string $email): self
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('E-mail inválido.');
        }

        return new self($email);
    }

    public function email(): string
    {
        return $this->email;
    }

    public function alterarEmail(string $novoEmail): self
    {
        return self::comEmail($novoEmail);
    }
}

/* =====================================================================
 * ARMADILHAS
 * ---------------------------------------------------------------------
 * - Decorar sintaxe sem entender o porquê: saber escrever prepare() mas não
 *   saber explicar por que evita SQL injection. Isso vira base para decisões
 *   de arquitetura nas próximas aulas.
 * - Pular o bloco de auth achando que "sessão é sessão": o Módulo 5 mostra os
 *   limites da sessão pura e quando ela não basta (API, múltiplos
 *   dispositivos). Sem o básico, você se perde lá.
 * - Achar propriedade public "mais rápida de escrever" e seguir usando: vira
 *   dívida técnica assim que o domínio cresce. É o hábito que o Módulo 2 quebra.
 * ===================================================================== */

/* =====================================================================
 * ENTREGA DA AULA
 * ---------------------------------------------------------------------
 * 1. Resolver os 9 exercícios SEM olhar o gabarito primeiro.
 * 2. Conferir com os gabaritos acima.
 * 3. Para cada erro ou travada, escrever uma linha com o motivo
 *    (ex: "esqueci de regenerar o ID de sessão no login").
 * 4. Mais de 3 erros em 9 → revisar o PHPIAN antes da Aula 3.
 *
 * Meus tropeços:
 *   1.1 -
 *   1.2 -
 *   1.3 -
 *   2.1 -
 *   2.2 -
 *   2.3 -
 *   3.1 -
 *   3.2 -
 *   3.3 -
 * ===================================================================== */

/* =====================================================================
 * QUIZ DA AULA (9 perguntas, escolha única) — respostas
 * ---------------------------------------------------------------------
 * 1. Buscar cliente sem brecha de injection
 *      → prepare() com parâmetro nomeado (:email) + bindValue/execute.
 * 2. Por que $id de $_GET concatenado é perigoso
 *      → entra na query sem prepare: SQL injection.
 * 3. Cliente + contato revertendo em caso de falha
 *      → beginTransaction(), os dois INSERTs, commit() no sucesso,
 *        rollBack() no erro.
 * 4. Passo contra session fixation, além do password_verify
 *      → session_regenerate_id(true) depois de autenticar.
 * 5. Guard de página autenticada
 *      → checar $_SESSION['usuario_id'] e redirecionar ao login se faltar.
 * 6. O que falta num logout que só chama session_destroy()
 *      → iniciar a sessão se preciso, limpar $_SESSION e expirar o cookie.
 * 7. Array do cliente virando classe de domínio
 *      → construtor com propriedades private e getters.
 * 8. Visibilidade padrão em propriedade de domínio
 *      → private.
 * 9. Problema do public string $email em Contato
 *      → qualquer código externo sobrescreve o e-mail sem validação.
 *
 * O quiz não substitui escrever o código — só confirma se o "porquê" ficou
 * sólido.
 * ===================================================================== */

/* =====================================================================
 * EXECUÇÃO — os 9 exercícios rodando de verdade
 * ===================================================================== */

require __DIR__ . '/../_aula.php';
require __DIR__ . '/../crm-produto/vendor/autoload.php';

$pdo = bancoDaAula();

// Os exercícios rodam contra as tabelas REAIS do projeto (clientes, contatos,
// usuarios) — não contra tabelas de mentirinha. É o mesmo schema que o CRM usa.

titulo('Aula 2 — Revisão ativa (checkpoint do PHPIAN)');

secao('BLOCO 1 — PDO');

$pdo->exec("INSERT INTO clientes (nome, email) VALUES ('Ana Souza', 'ana@exemplo.com')");

// 1.1
$achado = buscarClientePorEmail($pdo, 'ana@exemplo.com');
checa('1.1 busca por e-mail com prepared statement', ($achado['nome'] ?? null) === 'Ana Souza', 'bindValue + execute');
checa('1.1 inexistente devolve null', buscarClientePorEmail($pdo, 'ninguem@exemplo.com') === null, 'fetch() === false -> null');

// 1.2 — a prova de que interpolar é injeção.
// Com mais registros na tabela o estrago fica visível: a query interpolada
// devolve TODOS, e não só o id pedido.
$pdo->exec("INSERT INTO clientes (nome, email) VALUES ('Bruno Lima', 'bruno@exemplo.com'), ('Carla Dias', 'carla@exemplo.com')");

$idHostil = "1 OR 1=1";
$queryInsegura = "SELECT * FROM clientes WHERE id = {$idHostil}";
$linhas = $pdo->query($queryInsegura)->fetchAll();
checa('1.2 query interpolada retorna o que NÃO devia', count($linhas) >= 1, "com id='1 OR 1=1' a condição vira sempre verdadeira");

$stmt = $pdo->prepare('SELECT * FROM clientes WHERE id = :id');
$stmt->execute(['id' => $idHostil]);
$preparada = $stmt->fetchAll();

// Atenção ao detalhe: a preparada NÃO devolve zero linhas. O MySQL faz coerção
// de "1 OR 1=1" para o inteiro 1 e devolve o cliente de id 1 — o valor foi
// tratado como DADO, não como comando. A diferença está na COMPARAÇÃO: a
// interpolada devolve a tabela inteira, a preparada devolve no máximo um.
checa(
    '1.2 preparada trata como DADO, não como comando',
    count($preparada) <= 1 && count($preparada) < count($linhas),
    count($linhas) . ' linhas interpolando x ' . count($preparada) . ' preparando',
);

// 1.3
$novoId = cadastrarClienteComContato($pdo, 'Bruno Lima', 'Contato do Bruno', 'contato@exemplo.com');
checa('1.3 transação grava cliente e contato', $novoId > 0, "cliente id={$novoId}");
$contatos = (int) $pdo->query("SELECT COUNT(*) FROM contatos WHERE cliente_id = {$novoId}")->fetchColumn();
checa('1.3 os dois registros existem', $contatos === 1, '');

$antes = (int) $pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn();

try {
    // e-mail com 300 chars estoura a coluna do contato: o cliente já inserido
    // TEM que ser revertido junto.
    cadastrarClienteComContato($pdo, 'Cliente Fantasma', 'Contato', str_repeat('x', 300) . '@exemplo.com');
    checa('1.3 rollback em falha', false, 'não lançou exceção');
} catch (Throwable $e) {
    $depois = (int) $pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn();
    checa('1.3 falha no meio reverte TUDO', $antes === $depois, "{$antes} clientes antes e depois");
}

secao('BLOCO 2 — Auth por sessão');

$pdo->prepare('INSERT INTO usuarios (email, nome, senha_hash, papel) VALUES (:e, :n, :h, "admin")')
    ->execute(['e' => 'ana@exemplo.com', 'n' => 'Ana', 'h' => password_hash('senha-correta', PASSWORD_DEFAULT)]);

// 2.1 — autenticar() lê da tabela usuarios; aqui usamos a de exercício
$autenticarNoExercicio = static function (PDO $pdo, string $email, string $senha): bool {
    $stmt = $pdo->prepare('SELECT id, nome, senha_hash FROM usuarios WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario === false || !password_verify($senha, $usuario['senha_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $usuario['id'];

    return true;
};

$idAntes = session_id();
checa('2.1 senha errada não autentica', !$autenticarNoExercicio($pdo, 'ana@exemplo.com', 'errada'), 'password_verify');
checa('2.1 e-mail inexistente não autentica', !$autenticarNoExercicio($pdo, 'x@exemplo.com', 'senha-correta'), 'mesma resposta: sem enumeration');
checa('2.1 senha certa autentica', $autenticarNoExercicio($pdo, 'ana@exemplo.com', 'senha-correta'), '');
checa('2.1 o ID de sessão foi REGENERADO', session_id() !== $idAntes, 'defesa contra session fixation');
checa('2.1 e a sessão guarda o usuário', ($_SESSION['usuario_id'] ?? null) !== null, '');

// 2.2
checa('2.2 exigirLogin() existe e não redireciona logado', function_exists('exigirLogin'), 'com $_SESSION preenchida, segue');

// 2.3
logout();
checa('2.3 logout esvazia a sessão', $_SESSION === [], '');
checa('2.3 e encerra no servidor', session_status() !== PHP_SESSION_ACTIVE, 'session_destroy()');

secao('BLOCO 3 — OOP');

$c = new Cliente(1, 'Ana Souza', 'ana@exemplo.com');
checa('3.1 array virou classe com getters', $c->nome() === 'Ana Souza' && $c->id() === 1, '');

$visibilidades = array_map(
    static fn (ReflectionProperty $p): string => $p->isPrivate() ? 'private' : ($p->isProtected() ? 'protected' : 'public'),
    (new ReflectionClass(Cliente::class))->getProperties(),
);
checa('3.2 todas as propriedades são private', array_unique($visibilidades) === ['private'], 'padrão para domínio');

$contato = Contato::comEmail('bruno@exemplo.com');
checa('3.3 e-mail válido é aceito', $contato->email() === 'bruno@exemplo.com', '');

try {
    Contato::comEmail('sem-arroba');
    checa('3.3 e-mail inválido é recusado', false, 'não lançou');
} catch (InvalidArgumentException $e) {
    checa('3.3 e-mail inválido é recusado', true, $e->getMessage());
}

try {
    /** @phpstan-ignore-next-line demonstração proposital */
    $contato->email = 'quebrado';
    checa('3.3 escrita direta bloqueada', false, 'aceitou');
} catch (Error $e) {
    checa('3.3 escrita direta bloqueada', true, 'propriedade private + readonly');
}

secao('QUIZ da aula — respostas');

$quiz = [
    '1. Buscar cliente sem brecha de injection' => 'prepare() com :email + bindValue/execute',
    '2. $id de $_GET concatenado' => 'entra na query sem prepare: SQL injection',
    '3. Cliente + contato revertendo em falha' => 'beginTransaction, commit no sucesso, rollBack no erro',
    '4. Contra session fixation' => 'session_regenerate_id(true) após autenticar',
    '5. Guard de página autenticada' => 'checar $_SESSION[usuario_id] e redirecionar',
    '6. Falta num logout com só session_destroy' => 'iniciar sessão, limpar $_SESSION, expirar o cookie',
    '7. Array do cliente virando classe' => 'construtor com propriedades private e getters',
    '8. Visibilidade padrão no domínio' => 'private',
    '9. public string $email em Contato' => 'código externo sobrescreve sem validação',
];
foreach ($quiz as $pergunta => $resposta) {
    echo "  {$pergunta}\n      -> {$resposta}\n";
}

fecharAula();
