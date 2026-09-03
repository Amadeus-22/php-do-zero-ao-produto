# Divergências do material — PHPAN

Pontos em que o material do curso e a realidade não batem, achados ao **rodar o código
de cada aula contra o projeto e o banco de verdade**. Já estavam anotados, cada um na
sua aula; aqui ficam reunidos para reportar à plataforma.

Diferente do [PHPIAN/DIVERGENCIAS.md](../PHPIAN/DIVERGENCIAS.md), estes não vieram de
uma auditoria: apareceram sozinhos porque **cada aula do PHPAN roda e se verifica**
(47 aulas · 704 asserções · 0 falhas). O que quebrou, quebrou na frente.

Ambiente: PHP 8.4.25 · MySQL 8.4 em Docker · Linux. Data: 01/09/2026.

```bash
docker start crm-mysql
php rodar-todas.php
```

---

## A. Divergências do material do curso

### A1. Módulo 7, Aula 2 — a transação em volta da migração não funciona no MySQL

**O material** envolve cada migração numa transação:

```php
$pdo->beginTransaction();
$pdo->exec($sqlDaMigracao);
$pdo->commit();
```

**O que acontece:** DDL (`CREATE`, `ALTER`, `DROP`) provoca **commit implícito** no
MySQL. A transação morre no meio, e o `commit()` seguinte estoura
`"There is no active transaction"` — com a tabela **já criada**. O runner aborta
achando que falhou, quando na verdade a migração foi aplicada; rodar de novo dá
`table already exists`.

Não é detalhe de configuração: é comportamento documentado do MySQL, e torna o padrão
"migração transacional" (que funciona em PostgreSQL) inaplicável aqui.

**No projeto:** o runner não usa transação para DDL, e a aula prova o comportamento —
abre transação, faz um `CREATE TABLE`, e mostra a transação já morta.

**Onde:** [Modulo_7/02-migracoes-banco.md](Modulo_7/02-migracoes-banco.md) ·
[Modulo_7/02-migracoes-banco.php](Modulo_7/02-migracoes-banco.php)

---

### A2. Módulo 2, Aula 3 — o repositório em memória fica na camada errada

**O material** põe a implementação em memória em `App\Domain\Cliente`.

**O problema:** contradiz a regra que o próprio curso ensina no Módulo 1 e formaliza no
Módulo 4 — *o domínio não conhece implementação*. Um repositório em memória **é** uma
implementação; deixá-lo no domínio faz a camada depender de uma escolha de
persistência, ainda que trivial.

**No projeto:** `RepositorioDeClientesEmMemoria` mora em `App\Infrastructure\Cliente`.
O contrato (`RepositorioDeClientes`) continua no domínio, que é o lugar dele.

**Onde:** [Modulo_2/03-interfaces-contratos.md](Modulo_2/03-interfaces-contratos.md)

---

### A3. Módulo 2, Aula 6 — exceção declarada dentro do arquivo do caso de uso

**O material** declara `ClienteInexistente` dentro de `CadastrarContato.php` *"só para
caber no exemplo"* — e o próprio texto manda mover depois. Fica valendo o registro
porque contraria a regra de "uma classe por arquivo" que a Aula 4 estabelece, e porque
quem copia o exemplo sem ler a ressalva leva a estrutura errada adiante.

**No projeto:** virou `ClienteNaoEncontrado`, em `src/Domain/Cliente/`, reaproveitada
por `CadastrarContato`.

Junto disso: a aula instancia `ErroDeDominio` diretamente, mas ela é `abstract` — o
código como está no material não roda. No projeto usa-se a fábrica nomeada da exceção
concreta (`AtividadeInvalida::clienteInexistente()`).

**Onde:** [Modulo_2/06-refatorar-dominio-crm.md](Modulo_2/06-refatorar-dominio-crm.md)

---

## B. Bugs que só apareceram porque o código roda de verdade

Não são erros do material — são erros que o material **não teria como pegar**, porque
só aparecem contra um banco real. Ficam aqui porque mostram onde o exemplo didático
para de ser suficiente.

### B1. Placeholder repetido quebra com `EMULATE_PREPARES => false`

```sql
WHERE nome LIKE :q OR email LIKE :q
```

Com emulação ligada, o PDO monta a string e o `:q` duplicado passa. Com
`ATTR_EMULATE_PREPARES => false` — que é o certo, porque quem prepara passa a ser o
MySQL — isso estoura `Invalid parameter number`.

**Por que escapou dos testes:** o duplo em memória passava, porque o SQL nem chegava a
ser executado. Só apareceu ao rodar contra o MySQL. Hoje são `:q_nome` e `:q_email`,
com teste de integração que exercita a query de verdade.

**Onde:** [Modulo_6/06-soft-delete-busca.md](Modulo_6/06-soft-delete-busca.md)

---

### B2. A linha do cron dentro de um bloco `/** */` fecha o comentário

```php
/**
 * Rodar a cada 5 minutos:
 *   */5 * * * *  php bin/verificar-lembretes.php
 */
```

O `*/` de `*/5` **fecha o bloco de comentário**, e o arquivo deixa de compilar. Em
`bin/verificar-lembretes.php` o cron está em comentário de linha (`//`) por isso.

**Onde:** [Modulo_6/04-notificacoes-lembretes.md](Modulo_6/04-notificacoes-lembretes.md)

---

### B3. Repositório em arquivo descartava o `telefone` em silêncio

O `POST` respondia **201** e o campo sumia. O bug só apareceu quando o MySQL entrou e
a coluna passou a existir de verdade — até então nada denunciava a perda.

É o argumento concreto contra JSON como armazenamento: sem schema, o contrato mente e
ninguém percebe.

**Onde:** [crm-produto/docs/separacao-de-arquivos.md](crm-produto/docs/separacao-de-arquivos.md)

---

### B4. `LembreteService` conversava com PDO dentro de `Application/`

Escrito no Módulo 6, violava a regra que o **Módulo 1** ensina. Foi a verificação
automática da aula 1 (*"a aplicação não conhece PDO"*) que pegou, meses depois — teste
de regressão fazendo o trabalho dele.

Virou `RepositorioDeLembretes` no domínio + implementação na infraestrutura.

**Onde:** [Modulo_8/05-projeto-final-rubrica.md](Modulo_8/05-projeto-final-rubrica.md)

---

## Por que o PHPAN acha isso e o PHPIAN não achava

No PHPIAN os arquivos eram transcrição do código da aula: rodavam, mas não afirmavam
nada — não havia o que falhar. No PHPAN cada aula termina com `fecharAula()` e um
placar de asserções, e o `composer quality` roda a cada mudança. Um erro no material
vira `[FALHA]` na tela em vez de passar batido.

É a diferença entre **copiar** o código da aula e **verificar** o que ele afirma.
