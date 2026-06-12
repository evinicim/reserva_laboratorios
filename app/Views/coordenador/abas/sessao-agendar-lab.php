<div id="sessao-agendar-lab" class="content-section container-fluid px-4 pb-5">
    <div class="card shadow-sm mb-4 border-0" style="border-top: 4px solid var(--laranja-uniceplac) !important;">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold text-laranja"><span class="header-icon bg-light text-laranja"><i class="bi bi-calendar-plus fs-4"></i></span>Agendar Laboratório (Coordenador)</h5>
        </div>
        <div class="card-body bg-light p-4">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <form action="painel_coordenador.php" method="POST" class="bg-white p-4 p-md-5 border shadow-sm" style="border-radius: 20px;">
                        <input type="hidden" name="agendar_lab_coord" value="1">
                        <div class="row mb-4">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="form-label fw-bold text-secondary">Data:</label>
                                <input type="date" class="form-control form-control-lg" name="data_reserva" min="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="form-label fw-bold text-secondary">Turno:</label>
                                <select class="form-select form-select-lg" name="turno" required>
                                    <option value="">Selecione...</option>
                                    <option>Matutino</option>
                                    <option>Vespertino</option>
                                    <option>Noturno</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-secondary">Horário:</label>
                                <select class="form-select form-select-lg" name="periodo" required>
                                    <option>1º e 2º Horários</option>
                                    <option>1º Horário</option>
                                    <option>2º Horário</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-5">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="form-label fw-bold text-secondary">Laboratório:</label>
                                <select class="form-select form-select-lg" name="id_laboratorio" required data-lh-combobox data-lh-create="laboratorios">
                                    <option value="">Selecione ou busque...</option>
                                    <?php foreach ($laboratorios_cadastrados as $lab): ?>
                                        <option value="<?= $lab['id'] ?>"><?= htmlspecialchars($lab['nome']) ?> (Cap: <?= $lab['capacidade'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <label class="form-label fw-bold text-secondary">Professor:</label>
                                <select class="form-select form-select-lg" name="id_professor" required data-lh-combobox>
                                    <option value="">Selecione ou busque...</option>
                                    <?php foreach ($professores as $prof): ?>
                                        <option value="<?= $prof['id'] ?>"><?= htmlspecialchars($prof['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-secondary">Disciplina:</label>
                                <select class="form-select form-select-lg" name="id_disciplina" required data-lh-combobox data-lh-create="disciplinas">
                                    <option value="">Selecione ou busque...</option>
                                    <?php foreach ($disciplinas as $disc): ?>
                                        <option value="<?= $disc['id'] ?>"><?= htmlspecialchars($disc['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-uniceplac btn-lg px-5 w-100 w-md-auto rounded-pill"><i class="bi bi-send-check me-2"></i>Realizar Agendamento</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
