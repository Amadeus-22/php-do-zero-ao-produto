# Aula 02 — JSON de entrada e saída (contratos estáveis)

**Código executável:** [02-json-contratos.php](02-json-contratos.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-04-api/02-json-contratos)

## A ideia

Contrato é a **promessa de formato** que você faz a quem integra. Precisa ser
explícito (você decide os campos, não o `json_encode`), estável (mudar quebra quem já
integrou) e simétrico na entrada (nada de gravar o que chegou).

## Por que um Resource e não `json_encode($cliente)`

Duas razões, e a segunda é a que morde:

1. **Formato** — `DateTimeImmutable` vira um objeto esquisito de três campos.
2. **Vazamento** — no dia em que `Cliente` ganhar `senhaHash` ou uma nota interna, esse
   campo entra na resposta **sozinho**, sem ninguém decidir. O Resource lista o que
   sai; o que não está na lista não vaza.

`DATE_ATOM` é escolha de projeto: um formato de data para a API inteira. Misturar
`Y-m-d` num endpoint e timestamp em outro é dívida garantida.

## O envelope

```json
{"data": ...}                                        // sucesso
{"error": {"code": "...", "message": "...", "details": {}}}  // falha
```

- `code` é para **máquina** (o front faz `switch` nele; texto muda, code não).
- `message` é para **humano**.
- `details` só aparece quando há erro por campo — chave vazia inventada só polui.

## Mass assignment: a defesa é montar campo a campo

```php
$dados = [
    'nome' => $request->texto('nome'),
    'email' => $request->texto('email'),
    'telefone' => $request->texto('telefone') ?: null,
];
```

Nunca `$request->body` inteiro. Quem enviar `{"id":999,"senha_hash":"hack"}` recebe
resposta normal com o id que o **domínio** atribuiu — os campos extras nunca chegaram a
existir no array. O `.php` desta aula executa esse ataque e mostra o resultado.

## O detalhe do `fputcsv` (aparece no gerador de relatório)

No PHP 8.4 é preciso passar o parâmetro `$escape` explicitamente, senão vem deprecation
— por isso o projeto usa `fputcsv($buffer, $linha, ',', '"', '\\')`.

## O que quebra sem isso

- Mudar formato sem versionar: quebra todo cliente, **inclusive o próprio painel** da
  aula 5.
- `getTraceAsString()` no JSON de erro: entrega caminho de arquivo, versão de
  biblioteca e estrutura do código para quem estiver sondando a API.
