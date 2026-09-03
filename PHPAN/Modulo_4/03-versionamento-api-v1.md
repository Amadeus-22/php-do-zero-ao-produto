# Aula 03 — Versionamento simples (`/api/v1`)

**Código executável:** [03-versionamento-api-v1.php](03-versionamento-api-v1.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-04-api/03-versionamento-api-v1)

## A ideia

Versionar **não** é copiar o projeto para uma pasta `v2`. O núcleo (domínio, service,
repositório) continua um só. O que versiona é a **casca**: rotas, controllers da API,
resources e o contrato JSON.

| Versiona | Não versiona |
|---|---|
| `routes/api.php` (grupo `/api/v2`) | `App\Application\Cliente\ClienteService` |
| `App\Http\Api\V2\ClienteApiController` | `App\Domain\*` |
| `ClienteResourceV2` (se o contrato mudar) | `App\Infrastructure\*` |

## Por que `ClienteServiceV1` seria um erro

O Service é do **produto**, não da versão da API. Colocar `V1` no nome acopla a regra de
negócio a um detalhe de transporte — e, na V2, você teria duas cópias da mesma regra
para manter em sincronia. É exatamente o bug que este módulo inteiro existe para evitar.

O `.php` desta aula verifica isso: nenhum arquivo em `Domain/`, `Application/` ou
`Infrastructure/` tem sufixo de versão no nome.

## O que exige V2 e o que não exige

| Mudança | Veredito |
|---|---|
| Remover um campo da resposta | **breaking** → V2 |
| Mudar tipo ou significado de um campo | **breaking** → V2 |
| Acrescentar campo opcional na resposta | compatível → fica na V1 |
| Adicionar filtro opcional na query | compatível → fica na V1 |

A regra por trás: quem já integrou lê os campos que conhece e ignora os novos. Tirar ou
mudar o significado do que ele lê é que quebra.

## URL e não header

Existe a alternativa `Accept: application/vnd.crm.v1+json`. A URL vence aqui por um
motivo prático: `/api/v1/clientes` é debugável com `curl` sem flag, aparece no log de
acesso e é copiável para o suporte.
