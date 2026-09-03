# Aula 05 — Exportação CSV e PDF

**Código:** [05-exportacao-csv-pdf.php](05-exportacao-csv-pdf.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-06-produto/05-exportacao-csv-pdf)

## A ideia

O erro comum é montar um array PHP com todos os registros e só então gerar o arquivo.
Funciona com 50 linhas; com 50 mil estoura `memory_limit` e trava o request por dezenas
de segundos.

**Streaming:** escrever direto na saída conforme o banco entrega, linha a linha.

```php
while (($linha = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
    fputcsv($saida, $linha, ';', '"', '\\');
}
```

`fetch()` em loop, nunca `fetchAll()`. A diferença não aparece em desenvolvimento — só
em produção, com dado real.

## O BOM UTF-8

```php
fwrite($saida, "\xEF\xBB\xBF");
```

Sem esses três bytes, o Excel (principalmente no Windows) interpreta o arquivo como
outra codificação e "Cecília Ávila" vira lixo. Três bytes que evitam o suporte
recebendo print de planilha quebrada.

O separador `;` segue a mesma lógica: é o que o Excel em pt-BR espera.

## Quando vira job

Acima de 1000 registros a rota devolve **202** e despacha para a fila (aula 2). O
usuário não fica com a página travada esperando, e o worker gera o arquivo em background.

O número não é sagrado — o critério é "passou de poucos segundos, sai do request".

## Exportação é ação sensível

Dado exportado **sai do controle do sistema**: vira arquivo no computador de alguém,
fora de qualquer permissão ou log. Por isso `leitura` não exporta, e a rota é candidata
natural a rate limit (Módulo 5, aula 5) — é cara e visada.

## Detalhe do `Content-Disposition`

Se o nome do arquivo vier de parâmetro da requisição, ele precisa passar por
`basename()` antes de ir para o cabeçalho — senão é injeção de cabeçalho HTTP.

## PDF

`dompdf` (HTML → PDF, sem motor de browser) resolve relatório simples. Ainda não está
instalado no projeto: o CSV cobre a entrega, e o PDF entra quando houver um relatório
que justifique. A regra vale para os dois — passou de poucos segundos, vai para a fila.

## Sobre cursor não-bufferizado

Com o padrão do PDO, o driver ainda carrega o resultado inteiro na conexão antes de
iterar. Para volumes realmente grandes existe `MYSQL_ATTR_USE_BUFFERED_QUERY = false` —
mas enquanto esse cursor está aberto, a conexão não roda outra query. É uma troca, não
uma melhoria automática.
