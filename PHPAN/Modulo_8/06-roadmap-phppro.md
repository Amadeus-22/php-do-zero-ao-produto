# Aula 06 — Roadmap: o que vem no PHPPRO

**Código:** [06-roadmap-phppro.php](06-roadmap-phppro.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-08-final/06-roadmap-phppro)

## A ideia

Over-engineering é adicionar hoje a solução de um problema que você não tem hoje. O CRM
foi construído sem framework pesado, sem multi-tenant completo e sem fila distribuída —
não porque sejam ruins, mas porque entender **por que existem** exige primeiro sentir a
dor que resolvem.

| Ficou de fora | Quando passa a fazer sentido |
|---|---|
| Laravel/Symfony ponta a ponta | quando manter infra própria custar mais que entender cada camada |
| DDD / arquitetura avançada | quando regras colidirem entre módulos |
| Fila distribuída (Redis) | quando passar de dezenas para milhares de jobs/hora |
| Cache distribuído | quando query repetida em várias instâncias pesar no banco |
| Multi-tenant SaaS completo | quando contas pagantes justificarem o custo operacional |
| Escala (LB, réplicas) | quando uma instância não aguentar o tráfego |

## As quatro perguntas antes de priorizar

1. **Evidência:** onde no código ou na operação a dor aparece hoje?
2. **Custo de adiar:** o que quebra se eu não resolver em 3 meses?
3. **Custo de antecipar:** quantas horas de complexidade eu adiciono agora?
4. **Alternativa mínima:** existe solução menor que resolve 80%?

Exemplo: "preciso de fila" → evidência: job demora 40s no request → alternativa mínima:
cron + tabela de outbox, antes de Redis. Foi exatamente o caminho do Módulo 6.

## Lacunas deste projeto

| Lacuna | Dor hoje (S) / futura (F) | Tema PHPPRO |
|---|:--:|---|
| Staging e produção não existem | F | ambientes e CI/CD |
| Deploy nunca executado | F | infra e automação |
| Rate limit ausente no webhook | **S** | hardening |
| `UNIQUE` conflita com soft delete | **S** | modelagem de schema |
| Multi-tenant pela metade | F | multi-tenant |
| Sem teste de contrato da API | **S** | testes em profundidade |

## Prioridade imediata

**Rate limit no webhook + índice único parcial para soft delete.** São as duas únicas
lacunas com efeito no comportamento do sistema hoje: um endpoint público sem limite de
frequência, e a impossibilidade de recadastrar o e-mail de um cliente excluído.

## Adiamento consciente

**Fila distribuída (Redis).** Sinal para revisitar: mais de 500 jobs/hora **ou** job
aguardando mais de 5 minutos na fila por 3 dias seguidos.

Adiamento sem sinal mensurável ("quando crescer") vira nunca. Com número e prazo, vira
decisão revisável.

## O que levar daqui

Migrações, testes, runbook, checklist de deploy e a rubrica preenchida. Não se joga o
CRM fora — evolui-se sobre ele ou extraem-se módulos.

**Erros comuns ao entrar no PHPPRO:** refazer tudo do zero em Laravel (joga fora o
aprendizado operacional); escolher framework antes de listar o que você não quer mais
manter (critério invertido); recomeçar com cobertura zero de testes.

## Fechamento

O CRM está **fechado nesta fase de estudo**. Isso não impede manutenção — impede scope
creep infinito. O PHPPRO começa com clareza, não com culpa por "não ter feito tudo".

E "não usei framework" não foi perda de tempo: é exatamente o que dá critério para
escolher um depois, com entendimento do que ele está abstraindo.
