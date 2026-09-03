# Aula 04 — Views PHP: layouts e partials

**Código executável:** [04-views-layouts.php](04-views-layouts.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-03-mvc/04-views-layouts)

## A ideia

**PHP com `require` + output buffering já é uma engine de template.** Blade e Twig
resolvem problemas que este projeto não tem.

Três peças: **layout** (o molde), **partial** (pedaço reaproveitado) e **view de
página** (o conteúdo específico), encaixado no layout via `$content`.

Uma view boa recebe dado **pronto** e imprime. No máximo faz `foreach` e `if` de
apresentação.

## Por que `ob_start()` e não `include` direto

```php
ob_start();
require $caminho;
return (string) ob_get_clean();
```

`require` imprime direto na saída. O buffer **captura** esse HTML como string — o que
permite renderizar a view primeiro e só depois injetá-la no layout. Sem isso, o
conteúdo sairia antes do `<html>`.

## Por que `dirname(__DIR__, 2)` e não caminho fixo

Sobe de `src/Support/` até a raiz do projeto. Caminho absoluto (`/var/www/crm/views`)
quebra assim que o projeto muda de lugar ou roda em outro ambiente.

## `View::e()` — escape é a regra

`htmlspecialchars($valor, ENT_QUOTES, 'UTF-8')`. O `ENT_QUOTES` importa: sem ele, aspas
não são escapadas e um valor dentro de `value="..."` consegue fechar o atributo e
injetar outro.

O `.php` desta aula prova com um cliente chamado `<script>roubarSessao()</script>`: o
nome aparece como texto na tela, não como script executado.

## O que quebra sem isso

- `<?= $cliente->nome ?>` sem escape: XSS no dia em que um nome vier com `<script>`.
- Lógica de negócio na view: se a condição junta duas regras do domínio, calcule antes
  e passe `$cliente->podeSerRemovido` pronto.
- `require` de view direto no Controller: funciona, mas tranca — cache de view ou troca
  de estratégia exigiria mexer em cada Controller.
