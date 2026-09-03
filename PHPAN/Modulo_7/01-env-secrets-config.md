# Aula 01 — `.env`, secrets e config por ambiente

**Código:** [01-env-secrets-config.php](01-env-secrets-config.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-07-producao/01-env-secrets-config)

## A ideia

**Código não muda entre ambientes; configuração muda.** Se você precisa editar uma
linha de PHP para rodar em staging, não tem configuração — tem gambiarra.

Três categorias que se confundem:

| | Varia por ambiente? | É segredo? | Onde |
|---|---|---|---|
| **Config** | sim | não | `.env` (`APP_ENV`, `APP_URL`) |
| **Secret** | sim | sim | `.env` (`DB_PASSWORD`, `API_KEY`) |
| **Constante de domínio** | não | não | código (enums, regras) |

## Por que uma classe `Config` e não `getenv()` espalhado

Espalhar `$_ENV['DB_HOST']` é o mesmo erro de espalhar `new PDO(...)`: acopla o sistema
inteiro à forma de leitura. Com um ponto único dá para auditar o que o CRM depende de
configuração abrindo um arquivo — e a aula verifica que não sobrou nenhum `getenv()`
solto fora dele.

## O bug que o `bool()` existe para evitar

```php
$_ENV['APP_DEBUG'] = 'false';
(bool) $_ENV['APP_DEBUG']              // true  ← string não-vazia é truthy
Config::bool('APP_DEBUG')              // false ← FILTER_VALIDATE_BOOLEAN
```

`getenv()` **sempre** devolve string. `"false"` é uma string não-vazia, logo verdadeira
em PHP. É o clássico "desliguei o debug e continua ligado" — e com `APP_DEBUG` ligado em
produção, o handler de erro imprime stack trace com string de conexão.

`APP_DEBUG` é questão de segurança, não de conforto.

## Falhar no boot, não na primeira query

`Config::string('DB_PASSWORD')` sem valor e sem padrão **lança exceção**. É deliberado:
melhor quebrar no boot com "Config obrigatória ausente: DB_PASSWORD" do que 40 minutos
depois, na primeira query, com um erro que não diz o que faltou.

## `safeLoad()` e não `load()`

Em produção as variáveis podem vir do systemd ou do container, sem arquivo `.env` em
disco. `safeLoad()` não quebra quando o arquivo não existe — o código funciona nos dois
modos.

## `.env.example` em sincronia

Mesmas chaves, valores fake. A aula compara os dois arquivos e falha se divergirem:
divergência entre eles é a causa nº 1 de "funciona na minha máquina".

## Commitou o `.env` uma vez?

O segredo está no histórico do Git **para sempre**. `git rm` não resolve — rotacionar a
credencial é obrigatório.
