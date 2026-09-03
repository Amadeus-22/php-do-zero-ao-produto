# Aula 06 — Documentar endpoints (mínimo legível para humano)

**Código executável:** [06-documentar-endpoints.php](06-documentar-endpoints.php) · **Documento:** [crm-produto/docs/api.md](../crm-produto/docs/api.md) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-04-api/06-documentar-endpoints)

## A ideia

"O código é a documentação" funciona para quem escreveu o código. Não funciona para
quem só quer saber: qual URL chamar, o que mandar, o que volta, o que pode dar errado.

Documentação de API não precisa ser sofisticada — precisa ser **verdadeira e fácil de
achar**. Um Markdown por recurso resolve nesta fase.

## O que cada endpoint precisa responder

Método + caminho · autenticação · parâmetros (path, query, corpo) · exemplo de sucesso ·
**erros possíveis**.

O último item é o mais pulado e o mais caro: quem lê só o `201` descobre o `422`
quebrando em produção.

## O problema real: doc que envelhece

Doc desatualizada é **pior** que doc nenhuma — quem lê confia e é enganado. A regra é
tratar `docs/api.md` como parte do contrato: se o commit muda entrada ou saída de um
endpoint, o mesmo commit atualiza a seção.

Mas regra escrita depende de disciplina. Por isso o projeto tem
[DocumentacaoApiTest](../crm-produto/tests/DocumentacaoApiTest.php): ele lê
`routes/api.php`, extrai método e caminho de cada rota registrada, e **falha se alguma
não aparecer em `docs/api.md`**.

Adicione uma rota nova sem documentar e `composer test` fica vermelho. A doc deixa de
depender de boa vontade.

## Dado fictício, sempre

Exemplo copiado de um teste manual costuma trazer e-mail real de cliente ou chave de
API válida — que fica versionada para sempre no histórico do Git. O `.php` desta aula
verifica que a doc só usa `@exemplo.com`.

## Próximo passo (fora do escopo)

OpenAPI/Swagger gera documentação interativa a partir de anotações no código. O objetivo
desta fase é o **hábito** de documentar, não a ferramenta.
