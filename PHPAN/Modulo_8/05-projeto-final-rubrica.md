# Aula 05 — Projeto final: rubrica de entrega

**Código:** [05-projeto-final-rubrica.php](05-projeto-final-rubrica.php) · **Rubrica:** [rubrica-final.md](../crm-produto/docs/rubrica-final.md) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-08-final/05-projeto-final-rubrica)

## A ideia

Rubrica não é burocracia — é o que separa "projeto de curso" de produto. Ninguém
acredita em "funciona" sem evidência: comando rodado, teste passando, ou o sistema no ar
respondendo.

Cada linha é **feito** ou **não feito**. Em produção, "quase funciona" e "não funciona"
têm o mesmo efeito no cliente.

## Por que a rubrica é executável aqui

O `.php` desta aula não lê a rubrica — ele **confere contra o projeto**: os arquivos
existem? as rotas estão documentadas? o `composer quality` passa? **todas as aulas
rodam?**

45 verificações. Marcar item como feito sem evidência é exatamente o que a rubrica
existe para impedir, então ela mesma não podia ser uma lista de caixinhas confiando na
minha palavra.

## O que a última verificação pegou

A checagem "todas as aulas rodam" falhou com **11 aulas quebradas**. Motivo: o Módulo 5
introduziu autenticação, e as aulas dos Módulos 1 a 4 — escritas antes — batiam na API
sem token e no painel sem sessão.

Isso é o valor do teste de regressão aparecendo: o sistema evoluiu e o material antigo
parou de refletir a realidade. As aulas foram atualizadas para entrar autenticadas, com
o motivo escrito no código.

E mais um achado no caminho: a verificação do Módulo 1 ("aplicação não conhece PDO")
pegou o `LembreteService`, que eu havia escrito no Módulo 6 conversando com PDO direto —
violando a regra que o próprio Módulo 1 ensina. Virou `RepositorioDeLembretes` no
domínio + implementação na infraestrutura.

## Entregue

| Área | Status |
|---|---|
| Domínio, Web, API, Auth, Produto, Monetização, Documentação | ✅ |
| Backup e restauração | ✅ (RTO medido: 1s) |
| Staging, produção, deploy, HTTPS, domínio | ❌ — sem servidor |

**122 testes / 289 asserções** no projeto. **38 aulas executáveis** rodando.

## Pendências com motivo e prazo

Estão na rubrica com as três colunas que importam: o quê, por quê, e quando. Pendência
sem motivo escrito vira dívida esquecida; com motivo, vira item de roadmap.

## A demo

O roteiro de demonstração ainda não foi ensaiado — está declarado como pendência. Rodar
pela primeira vez na hora de apresentar trava em detalhe bobo (dado de teste faltando,
worker parado).
