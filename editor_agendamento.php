<?php
// ============================================================
// ARQUITETURA MVC - Arquivo refatorado para usar Controllers
// ============================================================

// Inclui o router que mapeia para o Controller apropriado
$controller_data = require __DIR__ . '/app/router.php';

// Extrai dados retornados pelo controller
if (is_array($controller_data)) {
    extract($controller_data);
} else {
    header("Location: painel_coordenador.php");
    exit;
}

// Define mensagem padrão se não for retornada
$mensagem = $mensagem ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Reserva - UNICEPLAC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --azul-uniceplac: #0A2444; --amarelo-uniceplac: #F1A000; }
        body { background-color: #f0f2f5; }
        .bg-uniceplac { background-color: var(--azul-uniceplac) !important; }
        .text-uniceplac { color: var(--azul-uniceplac) !important; }
        .btn-uniceplac { background-color: var(--azul-uniceplac); color: white; border: none; }
        .btn-uniceplac:hover { background-color: #051326; color: var(--amarelo-uniceplac); }
        .card-uniceplac { border: none; border-top: 4px solid var(--amarelo-uniceplac); }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                
                <?= $mensagem ?>

                <div class="card card-uniceplac shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-uniceplac">✏️ Editar Agendamento #<?= $reserva_atual['id'] ?></h5>
                        <a href="painel_coordenador.php" class="btn btn-sm btn-outline-secondary">Cancelar e Voltar</a>
                    </div>
                    <div class="card-body">
                        <form action="editor_agendamento.php?id=<?= $id_editar ?>" method="POST">
                            <input type="hidden" name="id_agendamento" value="<?= $reserva_atual['id'] ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Data da Reserva:</label>
                                    <input type="date" class="form-control" name="data_reserva" value="<?= $reserva_atual['data_reserva'] ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Turno:</label>
                                    <select class="form-select" name="turno" required data-lh-combobox>
                                        <option value="Matutino" <?= $reserva_atual['turno'] == 'Matutino' ? 'selected' : '' ?>>Matutino</option>
                                        <option value="Vespertino" <?= $reserva_atual['turno'] == 'Vespertino' ? 'selected' : '' ?>>Vespertino</option>
                                        <option value="Noturno" <?= $reserva_atual['turno'] == 'Noturno' ? 'selected' : '' ?>>Noturno</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Horário:</label>
                                    <select class="form-select" name="periodo" required>
                                        <option value="1º e 2º Horários" <?= $reserva_atual['periodo'] == '1º e 2º Horários' ? 'selected' : '' ?>>1º e 2º Horários</option>
                                        <option value="1º Horário" <?= $reserva_atual['periodo'] == '1º Horário' ? 'selected' : '' ?>>Apenas 1º Horário</option>
                                        <option value="2º Horário" <?= $reserva_atual['periodo'] == '2º Horário' ? 'selected' : '' ?>>Apenas 2º Horário</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Laboratório:</label>
                                <select class="form-select" name="id_laboratorio" required data-lh-combobox data-lh-create="laboratorios">
                                    <?php foreach($laboratorios as $lab): ?>
                                        <option value="<?= $lab['id'] ?>" <?= $reserva_atual['id_laboratorio'] == $lab['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($lab['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Professor:</label>
                                    <select class="form-select" name="id_professor" required data-lh-combobox>
                                        <?php foreach($professores as $prof): ?>
                                            <option value="<?= $prof['id'] ?>" <?= $reserva_atual['id_professor'] == $prof['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($prof['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Disciplina:</label>
                                    <select class="form-select" name="id_disciplina" required data-lh-combobox data-lh-create="disciplinas">
                                        <?php foreach($disciplinas as $disc): ?>
                                            <option value="<?= $disc['id'] ?>" <?= $reserva_atual['id_disciplina'] == $disc['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($disc['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-uniceplac w-100 py-2">Salvar Alterações</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    $labhub_catalog = [
        'disciplinas'  => array_map(static fn($d) => ['id' => $d['id'], 'nome' => $d['nome']], $disciplinas ?? []),
        'laboratorios' => array_map(static fn($l) => ['id' => $l['id'], 'nome' => $l['nome']], $laboratorios ?? []),
        'professores'  => array_map(static fn($p) => ['id' => $p['id'], 'nome' => $p['nome']], $professores ?? []),
    ];
    $labhub_can_create = (($_SESSION['perfil'] ?? '') === 'coordenador');
    require __DIR__ . '/app/Views/partials/labhub-combobox-setup.php';
    ?>
</body>
</html>