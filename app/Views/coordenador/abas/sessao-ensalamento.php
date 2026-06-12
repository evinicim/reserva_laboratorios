<div id="sessao-ensalamento" class="content-section container-fluid px-4 pb-5">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0" style="border-top: 4px solid var(--verde-uniceplac);">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-building text-primary me-2"></i> Novo Ensalamento</h5>
                    <p class="text-muted small mb-0 mt-1">Defina sala fixa por professor, curso e turno.</p>
                </div>
                <div class="card-body bg-light">
                    <form method="POST" action="painel_coordenador.php">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Professor:</label>
                            <select name="id_professor" class="form-select" required data-lh-combobox>
                                <option value="">Selecione ou busque...</option>
                                <?php foreach ($professores as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Disciplina:</label>
                            <select name="id_disciplina" class="form-select" required data-lh-combobox data-lh-create="disciplinas">
                                <option value="">Selecione ou busque...</option>
                                <?php foreach ($disciplinas as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Curso:</label>
                            <select name="curso" class="form-select" required data-lh-combobox data-lh-create="cursos" data-lh-value="nome">
                                <option value="">Selecione ou busque...</option>
                                <?php foreach ($cursos_cadastrados as $c): ?>
                                    <option value="<?= htmlspecialchars($c['nome']) ?>"><?= htmlspecialchars($c['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <label class="form-label small fw-bold">Bloco</label>
                                <select name="bloco" class="form-select" required data-lh-combobox data-lh-create="blocos" data-lh-value="nome">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($blocos_cadastrados as $b): ?>
                                        <option value="<?= htmlspecialchars($b['nome']) ?>"><?= htmlspecialchars($b['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold">Andar</label>
                                <select name="andar" class="form-select" required data-lh-combobox data-lh-create="andares" data-lh-value="nome">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($andares_cadastrados as $a): ?>
                                        <option value="<?= htmlspecialchars($a['nome']) ?>"><?= htmlspecialchars($a['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold">Sala</label>
                                <select name="sala" class="form-select" required data-lh-combobox data-lh-create="salas" data-lh-value="nome">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($salas_cadastradas as $s): ?>
                                        <option value="<?= htmlspecialchars($s['nome']) ?>"><?= htmlspecialchars($s['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Categoria:</label>
                            <select name="categoria" class="form-select" data-lh-combobox>
                                <option value="">Selecione...</option>
                                <?php foreach (['Presencial', 'EAD Polo', 'Híbrido', 'Presencial / EAD Polo'] as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Turno:</label>
                            <select name="turno" class="form-select" required data-lh-combobox>
                                <option value="">Selecione...</option>
                                <option value="Matutino">Matutino</option>
                                <option value="Vespertino">Vespertino</option>
                                <option value="Noturno">Noturno</option>
                            </select>
                        </div>
                        <button type="submit" name="salvar_ensalamento" value="1" class="btn btn-uniceplac w-100 fw-bold">
                            <i class="bi bi-plus-circle me-1"></i> Registrar Ensalamento
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark">Grade de Ensalamento</h5>
                    <span class="badge bg-secondary"><?= count($lista_ensalamentos) ?> registro(s)</span>
                </div>
                <div class="card-body p-0">
                    <?php if (count($lista_ensalamentos) > 0): ?>
                        <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="ps-4">Professor</th>
                                        <th>Disciplina / Curso</th>
                                        <th>Local</th>
                                        <th>Turno</th>
                                        <th class="pe-4 text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lista_ensalamentos as $en): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold"><?= htmlspecialchars($en['professor']) ?></td>
                                            <td>
                                                <span class="fw-bold"><?= htmlspecialchars($en['disciplina']) ?></span><br>
                                                <small class="text-muted"><?= htmlspecialchars($en['curso']) ?></small>
                                            </td>
                                            <td>
                                                Bloco <?= htmlspecialchars($en['bloco']) ?> ·
                                                <?= htmlspecialchars($en['andar']) ?> · Sala <?= htmlspecialchars($en['sala']) ?>
                                                <?php if (!empty($en['categoria'])): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($en['categoria']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-primary"><?= htmlspecialchars($en['turno']) ?></span></td>
                                            <td class="pe-4 text-end">
                                                <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal"
                                                    data-bs-target="#editEns<?= $en['id'] ?>"><i class="bi bi-pencil"></i></button>
                                                <form method="POST" action="painel_coordenador.php" class="d-inline"
                                                    onsubmit="return confirm('Remover este ensalamento?');">
                                                    <input type="hidden" name="id_ensalamento" value="<?= $en['id'] ?>">
                                                    <button type="submit" name="excluir_ensalamento" value="1" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-building fs-1 opacity-50 d-block mb-2"></i>
                            Nenhum ensalamento cadastrado. Use o formulário ao lado.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (count($lista_ensalamentos) > 0): ?>
        <?php foreach ($lista_ensalamentos as $en): ?>
            <div class="modal fade" id="editEns<?= $en['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title fw-bold">Editar Ensalamento</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" action="painel_coordenador.php">
                                <input type="hidden" name="id_ensalamento" value="<?= $en['id'] ?>">
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">Professor</label>
                                    <select name="id_professor" class="form-select" required data-lh-combobox>
                                        <?php foreach ($professores as $p): ?>
                                            <option value="<?= $p['id'] ?>" <?= $p['id'] == $en['id_professor'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">Disciplina</label>
                                    <select name="id_disciplina" class="form-select" required data-lh-combobox data-lh-create="disciplinas">
                                        <?php foreach ($disciplinas as $d): ?>
                                            <option value="<?= $d['id'] ?>" <?= $d['id'] == $en['id_disciplina'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">Curso</label>
                                    <select name="curso" class="form-select" required data-lh-combobox data-lh-create="cursos" data-lh-value="nome">
                                        <option value="">Selecione ou busque...</option>
                                        <?php foreach ($cursos_cadastrados as $c): ?>
                                            <option value="<?= htmlspecialchars($c['nome']) ?>" <?= ($en['curso'] ?? '') === $c['nome'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
                                        <?php endforeach; ?>
                                        <?php if (!empty($en['curso']) && !in_array($en['curso'], array_column($cursos_cadastrados, 'nome'), true)): ?>
                                            <option value="<?= htmlspecialchars($en['curso']) ?>" selected><?= htmlspecialchars($en['curso']) ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-4">
                                        <label class="form-label small fw-bold">Bloco</label>
                                        <select name="bloco" class="form-select" required data-lh-combobox data-lh-create="blocos" data-lh-value="nome">
                                            <option value="">Selecione...</option>
                                            <?php foreach ($blocos_cadastrados as $b): ?>
                                                <option value="<?= htmlspecialchars($b['nome']) ?>" <?= ($en['bloco'] ?? '') === $b['nome'] ? 'selected' : '' ?>><?= htmlspecialchars($b['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small fw-bold">Andar</label>
                                        <select name="andar" class="form-select" required data-lh-combobox data-lh-create="andares" data-lh-value="nome">
                                            <option value="">Selecione...</option>
                                            <?php foreach ($andares_cadastrados as $a): ?>
                                                <option value="<?= htmlspecialchars($a['nome']) ?>" <?= ($en['andar'] ?? '') === $a['nome'] ? 'selected' : '' ?>><?= htmlspecialchars($a['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small fw-bold">Sala</label>
                                        <select name="sala" class="form-select" required data-lh-combobox data-lh-create="salas" data-lh-value="nome">
                                            <option value="">Selecione...</option>
                                            <?php foreach ($salas_cadastradas as $s): ?>
                                                <option value="<?= htmlspecialchars($s['nome']) ?>" <?= ($en['sala'] ?? '') === $s['nome'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">Categoria</label>
                                    <select name="categoria" class="form-select" data-lh-combobox>
                                        <option value="">Selecione...</option>
                                        <?php foreach (['Presencial', 'EAD Polo', 'Híbrido', 'Presencial / EAD Polo'] as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat) ?>" <?= ($en['categoria'] ?? '') === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <select name="turno" class="form-select" required data-lh-combobox>
                                        <?php foreach (['Matutino', 'Vespertino', 'Noturno'] as $t): ?>
                                            <option value="<?= $t ?>" <?= $en['turno'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" name="editar_ensalamento" value="1" class="btn btn-primary w-100">Salvar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
