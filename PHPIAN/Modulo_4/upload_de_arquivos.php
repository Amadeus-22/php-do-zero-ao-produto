<form method="post" enctype="multipart/form-data">
  <input type="file" name="avatar" accept="image/*">
  <button>Enviar</button>
</form>
<?php

// PHPIAN · Módulo 4 · Aula 4 — Upload de arquivos
// metadados em aulas.json (4-4)

$arquivo = $_FILES['avatar'] ?? null;
if ($arquivo && $arquivo['error'] === UPLOAD_ERR_OK) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($arquivo['tmp_name']);
    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($permitidos[$mime])) {
        exit('Tipo inválido');
    }
    $nome = bin2hex(random_bytes(8)) . '.' . $permitidos[$mime];
    move_uploaded_file($arquivo['tmp_name'], __DIR__ . '/uploads/' . $nome);
}