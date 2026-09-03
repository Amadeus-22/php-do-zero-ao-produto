const API = '/api/v1/clientes';

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function api(path, options = {}) {
  const headers = {
    Accept: 'application/json',
    ...(options.body ? { 'Content-Type': 'application/json' } : {}),
    'X-CSRF-TOKEN': csrfToken(),
    ...options.headers,
  };

  const res = await fetch(path, { ...options, headers, credentials: 'same-origin' });
  // 500 pode devolver HTML de erro do PHP: json() quebraria sem este catch
  const payload = await res.json().catch(() => ({}));

  if (!res.ok) {
    const msg = payload?.error?.message ?? `HTTP ${res.status}`;
    throw Object.assign(new Error(msg), { status: res.status, payload });
  }
  return payload;
}

function escapeHtml(s) {
  return String(s)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;');
}

function renderLista(clientes) {
  document.getElementById('lista-clientes').innerHTML = clientes
    .map((c) => `<li data-id="${c.id}"><strong>${escapeHtml(c.nome)}</strong> — ${escapeHtml(c.email)}</li>`)
    .join('');
}

async function carregar() {
  const { data } = await api(`${API}?per_page=50`);
  renderLista(data);
}

document.getElementById('form-cliente').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const erro = document.getElementById('clientes-erro');
  try {
    erro.hidden = true;
    await api(API, { method: 'POST', body: JSON.stringify({ nome: fd.get('nome'), email: fd.get('email') }) });
    e.target.reset();
    await carregar();
  } catch (err) {
    erro.hidden = false;
    erro.textContent = err.message;
  }
});

carregar().catch((err) => {
  const erro = document.getElementById('clientes-erro');
  erro.hidden = false;
  erro.textContent = err.message;
});
