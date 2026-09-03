# Aula 06 — Soft delete e busca

**Código:** [06-soft-delete-busca.php](06-soft-delete-busca.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-06-produto/06-soft-delete-busca)

## A ideia

`DELETE FROM clientes WHERE id = ?` é irreversível. Em produto isso é inaceitável para
uma entidade central: o usuário **vai** clicar errado em algum momento, e "não tem como
desfazer" é péssima experiência — além de destruir histórico ligado (atividades, anexos,
auditoria) por cascata de FK.

**Soft delete:** `UPDATE clientes SET deletado_em = NOW()`. O registro continua; toda
consulta passa a filtrar `deletado_em IS NULL`. Como se não existisse, exceto para quem
quiser ver a lixeira.

Resolve três coisas de uma vez: recuperação de engano, preservação de histórico e
integridade referencial.

## Centralizar o filtro não é preferência

Qualquer `SELECT` novo escrito **fora** do repositório volta a mostrar registro
excluído. É por isso que o filtro estar em um lugar só importa mais aqui do que parece:
não é organização, é a única garantia de que ninguém vai esquecer.

## Restaurar é tão sensível quanto excluir

Mesma permissão (`admin`) e mesmo rastro na auditoria — restaurar pode reviver algo que
deveria continuar fora, e alguém precisa poder responder quem trouxe de volta.

## A armadilha do `UNIQUE`

O registro "excluído" continua na tabela, então o `UNIQUE KEY (email)` continua valendo:
cadastrar outro cliente com o e-mail de um excluído **falha**.

O `.php` reproduz isso. Saídas possíveis: índice único parcial (onde o banco suportar)
ou incluir `deletado_em` na chave única. **Fica como decisão pendente do projeto** —
hoje o comportamento é o conflito, e ele está documentado em vez de escondido.

## Busca

`LIKE '%termo%'` com índice resolve para o volume de um CRM (milhares a centenas de
milhares). Não é necessário FULLTEXT nem Elasticsearch nesta fase.

O limite conhecido: o `%` no início impede uso eficiente de índice B-tree. Se a base
crescer muito, ou se a busca precisar de relevância, aí sim outra solução.

## Um bug que só o banco real revelou

A query de busca usava o mesmo placeholder `:q` duas vezes (`nome LIKE :q OR email LIKE
:q`). Com `ATTR_EMULATE_PREPARES => false` — que é o certo, porque quem prepara passa a
ser o MySQL — isso estoura `Invalid parameter number`.

Os testes com o duplo em memória passavam: o SQL nem chegava a ser executado. Só
apareceu ao rodar a aula contra o MySQL. Hoje há `:q_nome` e `:q_email`, e um teste de
integração ([BuscaPdoTest](../crm-produto/tests/Infrastructure/Cliente/BuscaPdoTest.php))
que exercita a query de verdade.

## Soft delete não é backup

Protege contra engano de usuário. Não protege contra `DROP TABLE`, bug de migração ou
disco corrompido. Backup continua obrigatório — Módulo 7.
