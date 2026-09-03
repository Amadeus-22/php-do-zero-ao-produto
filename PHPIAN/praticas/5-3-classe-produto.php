<?php

// PHPIAN · Módulo 5 · Aula 3 — Classes e objetos (intro)
// Prática: "Crie uma classe Produto com nome, preco e método formatarPreco(): string."

declare(strict_types=1);

require __DIR__ . '/_pratica.php';

titulo('Prática 5-3 — classe Produto');

secao('A classe');

class Produto
{
    // Promoção de propriedades no construtor — a sintaxe PHP 8 que a aula usa
    // na classe Contato.
    public function __construct(
        public string $nome,
        public float $preco,
        public int $estoque = 0,
    ) {
    }

    public function formatarPreco(): string
    {
        return 'R$ ' . number_format($this->preco, 2, ',', '.');
    }

    public function disponivel(): bool
    {
        return $this->estoque > 0;
    }
}

$p = new Produto('Teclado mecânico', 349.9, 12);

checa('o objeto foi criado', $p instanceof Produto);
checa('nome guardado', $p->nome === 'Teclado mecânico');
checa('preco guardado', $p->preco === 349.9);
checa('estoque tem valor padrão', (new Produto('X', 10.0))->estoque === 0);

secao('formatarPreco()');

$casos = [
    [349.9, 'R$ 349,90', 'zero final que a interpolação perdia na aula 1-5'],
    [1234.5, 'R$ 1.234,50', 'separador de milhar'],
    [0.0, 'R$ 0,00', 'zero'],
    [1000000.0, 'R$ 1.000.000,00', 'milhão'],
    [0.999, 'R$ 1,00', 'arredonda'],
];
foreach ($casos as [$valor, $esperado, $porque]) {
    checa(sprintf('%-12s -> %s', $valor, (new Produto('X', $valor))->formatarPreco()),
        (new Produto('X', $valor))->formatarPreco() === $esperado, $porque);
}

secao('Tipagem das propriedades');

checaExcecao('passar string onde se espera float lança TypeError', \TypeError::class,
    static fn () => new Produto('X', 'caro'));
checa('int vira float sem reclamar', (new Produto('X', 10))->preco === 10.0, 'int é widening seguro para float');

secao('O vocabulário da aula, verificado');

$outro = new Produto('Mouse', 89.5);
checa('classe é o molde, objeto é a instância', $p !== $outro && $p instanceof Produto && $outro instanceof Produto);
checa('cada objeto tem seu próprio estado', $p->nome !== $outro->nome);

$r = new ReflectionClass(Produto::class);
checa('tem 3 propriedades', count($r->getProperties()) === 3);
checa('tem os métodos formatarPreco e disponivel',
    $r->hasMethod('formatarPreco') && $r->hasMethod('disponivel'));
checa('formatarPreco declara retorno string', (string) $r->getMethod('formatarPreco')->getReturnType() === 'string');

secao('Visibilidade — o que public deixa acontecer');

// A aula usa public em tudo e diz "sem drama". O custo é este: qualquer código
// pode invalidar o objeto. O PHPAN resolve isso com private + readonly.
$p->preco = -100.0;
checa('com public dá para pôr preço negativo', $p->preco === -100.0, 'o objeto ficou inválido');
nota('encapsulamento (private + método) é o que impede isso — vem no PHPAN');

class ProdutoProtegido
{
    private float $preco;

    public function __construct(public readonly string $nome, float $preco)
    {
        $this->definirPreco($preco);
    }

    public function definirPreco(float $preco): void
    {
        if ($preco < 0) {
            throw new InvalidArgumentException('Preço não pode ser negativo');
        }
        $this->preco = $preco;
    }

    public function formatarPreco(): string
    {
        return 'R$ ' . number_format($this->preco, 2, ',', '.');
    }
}

$seguro = new ProdutoProtegido('Teclado', 349.9);
checa('a versão protegida formata igual', $seguro->formatarPreco() === 'R$ 349,90');
checaExcecao('e recusa preço negativo', \InvalidArgumentException::class,
    static fn () => new ProdutoProtegido('X', -1.0));
checaExcecao('não dá para escrever na propriedade privada', \Error::class,
    static fn () => $seguro->preco = -100.0);
checaExcecao('readonly não aceita reatribuição', \Error::class,
    static fn () => $seguro->nome = 'outro');

secao('A classe Contato do código da aula');

class Contato
{
    public function __construct(
        public string $nome,
        public string $email,
        public ?string $telefone = null,
    ) {
    }

    public function iniciais(): string
    {
        $partes = preg_split('/\s+/u', trim($this->nome), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $ini = '';
        foreach ($partes as $p) {
            $ini .= mb_strtoupper(mb_substr($p, 0, 1, 'UTF-8'), 'UTF-8');
        }
        return $ini;
    }
}

$c = new Contato('Ana Silva', 'ana@email.com');
checa('Contato->iniciais() devolve AS', $c->iniciais() === 'AS', 'o mesmo resultado da prática 3-3');
checa('telefone é opcional e nasce null', $c->telefone === null);
checa('?string aceita null explicitamente', (new Contato('X', 'x@y.z', null))->telefone === null);

fecharPratica();
