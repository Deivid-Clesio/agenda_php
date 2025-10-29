<?php
// index.php
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Agenda - Lista de Tarefas</title>

  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="script.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Minha Agenda</a>
    <div class="d-flex">
      <input id="searchInput" class="form-control me-2" placeholder="Pesquisar tarefas..." />
      <button id="btnSearch" class="btn btn-light">Buscar</button>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Suas tarefas</h4>
    <button class="btn btn-success" id="btnNovo">+ Criar tarefa</button>
  </div>

  <div id="listaTarefas" class="row g-3">
    <!-- cards serão inseridos aqui -->
  </div>
</div>

<!-- Modal Criar / Editar Tarefa -->
<div class="modal fade" id="tarefaModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formTarefa">
        <div class="modal-header">
          <h5 class="modal-title">Criar Tarefa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">

          <input type="hidden" id="tarefaId" value="0">
          <div class="mb-3">
            <label class="form-label">Título</label>
            <input id="titulo" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Descrição</label>
            <textarea id="descricao" class="form-control"></textarea>
          </div>
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Data prevista</label>
              <input id="data_prevista" type="date" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Duração (min)</label>
              <input id="duracao_minutos" type="number" min="0" class="form-control">
            </div>
          </div>

          <hr>
          <h6>Subtarefas</h6>
          <div id="subtarefasList" class="mb-2"></div>

          <div class="input-group mb-3">
            <input id="novoSubInput" class="form-control" placeholder="Adicionar nova subtarefa (título)">
            <button id="btnAddSub" type="button" class="btn btn-outline-secondary">Adicionar</button>
          </div>

          <hr>
          <h6>Lista de compras / Itens</h6>
          <div id="itensList" class="mb-2"></div>
          <div class="input-group mb-1">
            <input id="novoItemInput" class="form-control" placeholder="Adicionar item para comprar">
            <button id="btnAddItem" type="button" class="btn btn-outline-secondary">Adicionar</button>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" id="btnDelete" class="btn btn-danger me-auto">Excluir tarefa</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Bootstrap + JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const api = (action, data = {}, method='POST') => {
  const url = 'api.php?action=' + encodeURIComponent(action);
  if (method === 'GET') {
    const params = new URLSearchParams(data);
    return fetch(url + '&' + params.toString()).then(r=>r.json());
  } else {
    const form = new FormData();
    for (const k in data) form.append(k, data[k]);
    return fetch(url, { method: 'POST', body: form }).then(r=>r.json());
  }
};

const tarefaModalEl = document.getElementById('tarefaModal');
const tarefaModal = new bootstrap.Modal(tarefaModalEl);

const listaTarefasEl = document.getElementById('listaTarefas');
const btnNovo = document.getElementById('btnNovo');
const formTarefa = document.getElementById('formTarefa');
const tituloEl = document.getElementById('titulo');
const descricaoEl = document.getElementById('descricao');
const dataPrevEl = document.getElementById('data_prevista');
const duracaoEl = document.getElementById('duracao_minutos');
const tarefaIdEl = document.getElementById('tarefaId');
const subtarefasList = document.getElementById('subtarefasList');
const itensList = document.getElementById('itensList');
const btnAddSub = document.getElementById('btnAddSub');
const novoSubInput = document.getElementById('novoSubInput');
const btnAddItem = document.getElementById('btnAddItem');
const novoItemInput = document.getElementById('novoItemInput');
const btnDelete = document.getElementById('btnDelete');
const searchInput = document.getElementById('searchInput');
const btnSearch = document.getElementById('btnSearch');

function renderCard(t) {
  const due = t.data_prevista ? new Date(t.data_prevista).toLocaleDateString() : '';
  return `
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">${escapeHtml(t.titulo)}</h5>
        <p class="card-text small text-muted">Criado: ${t.criado_em}</p>
        <p class="card-text">${escapeHtml(t.descricao || '')}</p>
        <p class="card-text"><small>Data prevista: ${due} • Duração: ${t.duracao_minutos || '-'} min</small></p>
        <div class="d-flex justify-content-between">
          <button class="btn btn-sm btn-outline-primary" onclick="openTarefa(${t.id})">Abrir</button>
          <button class="btn btn-sm btn-outline-danger" onclick="deleteTarefa(${t.id})">Excluir</button>
        </div>
      </div>
    </div>
  </div>
  `;
}

function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[m]); }

function loadAll(q = '') {
  api('search', {q}, 'GET').then(res => {
    if (!res.success) return;
    listaTarefasEl.innerHTML = res.results.map(renderCard).join('') || '<p class="text-muted">Nenhuma tarefa encontrada.</p>';
  });
}

btnNovo.addEventListener('click', () => {
  tarefaIdEl.value = '0';
  tituloEl.value = descricaoEl.value = dataPrevEl.value = duracaoEl.value = '';
  subtarefasList.innerHTML = '';
  itensList.innerHTML = '';
  btnDelete.style.display = 'none';
  tarefaModal.show();
});

formTarefa.addEventListener('submit', (e) => {
  e.preventDefault();
  const data = {
    titulo: tituloEl.value,
    descricao: descricaoEl.value,
    data_prevista: dataPrevEl.value,
    duracao_minutos: duracaoEl.value
  };
  api('create_tarefa', data).then(res => {
    if (res.success){
      tarefaIdEl.value = res.id;
      btnDelete.style.display = 'inline-block';
      loadTask(res.id);
      loadAll();
    } else alert(res.msg || 'Erro');
  });
});

function loadTask(id){
  api('get_tarefa', {id}, 'GET').then(res=>{
    if (!res.success) return;
    const t = res.tarefa;
    tarefaIdEl.value = t.id;
    tituloEl.value = t.titulo;
    descricaoEl.value = t.descricao || '';
    dataPrevEl.value = t.data_prevista || '';
    duracaoEl.value = t.duracao_minutos || '';
    subtarefasList.innerHTML = res.subtarefas.map(s => renderSub(s)).join('');
    itensList.innerHTML = res.itens.map(it => renderItem(it)).join('');
    btnDelete.style.display = 'inline-block';
    tarefaModal.show();
  });
}

function renderSub(s){
  return `<div class="form-check mb-1">
    <input class="form-check-input" type="checkbox" id="sub_${s.id}" ${s.concluida? 'checked':''} onchange="toggleSub(${s.id})">
    <label class="form-check-label" for="sub_${s.id}">${escapeHtml(s.titulo)} ${s.data_prevista ? '('+s.data_prevista+')':''}</label>
  </div>`;
}

function renderItem(it){
  return `<div class="form-check mb-1">
    <input class="form-check-input" type="checkbox" id="item_${it.id}" ${it.comprado? 'checked':''} onchange="toggleItem(${it.id})">
    <label class="form-check-label" for="item_${it.id}">${escapeHtml(it.conteudo)}</label>
  </div>`;
}

btnAddSub.addEventListener('click', () => {
  const tarefaId = tarefaIdEl.value;
  const titulo = novoSubInput.value.trim();
  if (!tarefaId || tarefaId === '0'){ alert('Salve a tarefa primeiro (clique em Salvar) antes de adicionar subtarefas.'); return; }
  if (!titulo) return;
  api('add_subtarefa', {tarefa_id: tarefaId, titulo}).then(res => {
    if (res.success) { loadTask(tarefaId); novoSubInput.value = ''; loadAll(); }
  });
});

btnAddItem.addEventListener('click', () => {
  const tarefaId = tarefaIdEl.value;
  const conteudo = novoItemInput.value.trim();
  if (!tarefaId || tarefaId === '0'){ alert('Salve a tarefa primeiro antes de adicionar itens.'); return; }
  if (!conteudo) return;
  api('add_item', {tarefa_id: tarefaId, conteudo}).then(res => {
    if (res.success) { loadTask(tarefaId); novoItemInput.value = ''; loadAll(); }
  });
});

function toggleSub(id){
  api('toggle_subtarefa', {id}).then(()=>{ /* opcional refrescar */ });
}

function toggleItem(id){
  api('toggle_item', {id}).then(()=>{ /* opcional refrescar */ });
}

function openTarefa(id){
  loadTask(id);
}

function deleteTarefa(id){
  if (!confirm('Confirma exclusão da tarefa?')) return;
  api('delete_tarefa', {id}).then(res=>{
    if (res.success) loadAll();
  });
}

btnSearch.addEventListener('click', ()=> loadAll(searchInput.value));
searchInput.addEventListener('keyup', (e)=> { if (e.key === 'Enter') loadAll(searchInput.value); });

/* inicial */
loadAll();
</script>
</body>
</html>
