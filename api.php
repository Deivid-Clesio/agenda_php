<?php
// api.php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'create_tarefa') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $data_prevista = $_POST['data_prevista'] ?: null;
    $duracao_minutos = $_POST['duracao_minutos'] ?: null;

    if ($titulo === '') {
        echo json_encode(['success' => false, 'msg' => 'Título obrigatório']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO tarefas (titulo, descricao, data_prevista, duracao_minutos) VALUES (?, ?, ?, ?)");
    $stmt->execute([$titulo, $descricao, $data_prevista, $duracao_minutos]);
    $id = $pdo->lastInsertId();
    echo json_encode(['success' => true, 'id' => $id]);
    exit;
}

if ($action === 'get_tarefa') {
    $id = intval($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM tarefas WHERE id = ?");
    $stmt->execute([$id]);
    $tarefa = $stmt->fetch();
    if (!$tarefa) { echo json_encode(['success'=>false]); exit; }

    $stmt = $pdo->prepare("SELECT * FROM subtarefas WHERE tarefa_id = ? ORDER BY id");
    $stmt->execute([$id]);
    $subtarefas = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM itens_compra WHERE tarefa_id = ? ORDER BY id");
    $stmt->execute([$id]);
    $itens = $stmt->fetchAll();

    echo json_encode(['success' => true, 'tarefa' => $tarefa, 'subtarefas' => $subtarefas, 'itens' => $itens]);
    exit;
}

if ($action === 'add_subtarefa') {
    $tarefa_id = intval($_POST['tarefa_id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $data_prevista = $_POST['data_prevista'] ?: null;
    $duracao_minutos = $_POST['duracao_minutos'] ?: null;
    if (!$tarefa_id || $titulo === '') { echo json_encode(['success'=>false]); exit; }
    $stmt = $pdo->prepare("INSERT INTO subtarefas (tarefa_id, titulo, data_prevista, duracao_minutos) VALUES (?, ?, ?, ?)");
    $stmt->execute([$tarefa_id, $titulo, $data_prevista, $duracao_minutos]);
    echo json_encode(['success'=>true, 'id'=>$pdo->lastInsertId()]);
    exit;
}

if ($action === 'toggle_subtarefa') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE subtarefas SET concluida = 1 - concluida WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success'=>true]);
    exit;
}

if ($action === 'add_item') {
    $tarefa_id = intval($_POST['tarefa_id'] ?? 0);
    $conteudo = trim($_POST['conteudo'] ?? '');
    if (!$tarefa_id || $conteudo === '') { echo json_encode(['success'=>false]); exit; }
    $stmt = $pdo->prepare("INSERT INTO itens_compra (tarefa_id, conteudo) VALUES (?, ?)");
    $stmt->execute([$tarefa_id, $conteudo]);
    echo json_encode(['success'=>true, 'id'=>$pdo->lastInsertId()]);
    exit;
}

if ($action === 'toggle_item') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE itens_compra SET comprado = 1 - comprado WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success'=>true]);
    exit;
}

if ($action === 'search') {
    $q = '%' . ($_GET['q'] ?? '') . '%';
    $stmt = $pdo->prepare("SELECT * FROM tarefas WHERE titulo LIKE ? OR descricao LIKE ? ORDER BY criado_em DESC LIMIT 200");
    $stmt->execute([$q, $q]);
    $rows = $stmt->fetchAll();
    echo json_encode(['success'=>true, 'results'=>$rows]);
    exit;
}

if ($action === 'delete_tarefa') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false]); exit; }
    $stmt = $pdo->prepare("DELETE FROM tarefas WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success'=>true]);
    exit;
}

echo json_encode(['success'=>false, 'msg'=>'Ação inválida']);
