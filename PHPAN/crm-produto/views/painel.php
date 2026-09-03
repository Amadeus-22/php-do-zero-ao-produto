<?php use App\Support\View; ?>
<h1>Painel via API</h1>
<p>Esta tela não recarrega: tudo passa por <code>fetch</code> em <code>/api/v1/clientes</code>.</p>

<section data-clientes>
  <form id="form-cliente">
    <input name="nome" required placeholder="Nome">
    <input name="email" type="email" required placeholder="E-mail">
    <button type="submit">Salvar</button>
  </form>
  <p id="clientes-erro" hidden></p>
  <ul id="lista-clientes"></ul>
</section>
<script src="/assets/js/clientes-api.js" type="module"></script>
