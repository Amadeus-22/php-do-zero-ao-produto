# Aula 01 — Planos, limites e "access granted"

**Código:** [01-planos-limites.php](01-planos-limites.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-08-final/01-planos-limites)

## A ideia

Assinatura reduzida ao essencial: *"este cliente pagante tem direito a X até quando?"*

| Peça | O que é |
|---|---|
| **Plano** | conjunto nomeado de limites (`free`, `pro`) |
| **Assinatura** | conta + plano + status + validade |
| **Access granted** | a checagem em runtime — é ela que trava ou libera |

O plano em si não trava nada. Quem trava é a checagem, e ela precisa rodar em **todo
ponto de entrada** que cria o recurso limitado.

## O limite tem que valer na API também

Validar só no formulário web e esquecer a API significa que o limite **não existe** —
existe só na experiência que você lembrou de proteger. Quem descobrir o endpoint o
contorna inteiro.

Por isso a regra vive numa classe só (`PlanLimiter`) e os dois controllers chamam. O
`.php` verifica que nenhum controller hardcoda o número.

## Sem assinatura = zero, não infinito

```php
return $valor === false ? 0 : (int) $valor;
```

*Fail closed*: sem assinatura ativa, o limite é **zero**. O contrário ("sem plano, sem
limite") seria acesso ilimitado de graça — e é o tipo de bug que só se descobre pela
fatura.

## Grace period

Assinatura atrasada não trava na hora. Sete dias de tolerância, e nesse período a
**leitura continua liberada** — o cliente vê os dados dele, só não escreve.

Bloqueio abrupto no dia do atraso gera cancelamento por atrito em vez de renovação. É
decisão de produto tanto quanto técnica.

## O único lugar com interpolação em SQL

Nome de coluna **não pode** ser parâmetro preparado. Por isso `limiteDe()` tem
whitelist:

```php
if (!in_array($coluna, ['max_clientes', 'max_usuarios'], true)) {
    throw new \InvalidArgumentException(...);
}
```

Sem a whitelist, isso seria injeção direta. Com ela, qualquer valor fora da lista morre
antes de chegar na query.

## Papel × plano

Conceitos diferentes que se confundem:

- **Papel** (Módulo 5): o que o **usuário** pode fazer dentro da conta.
- **Plano** (aqui): quanto a **conta** pode ter ou usar.

Um vendedor — papel que pode criar — numa conta free que atingiu o limite continua
barrado. Pelo plano, não pelo papel. As duas checagens coexistem e não se substituem.

## O que ficou pela metade

`conta_id` existe em `clientes`, mas `usuarios` ainda é conta única. Multi-tenant de
verdade está fora do escopo do PHPAN — e está declarado assim na rubrica, não escondido.
