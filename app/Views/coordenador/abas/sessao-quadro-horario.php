<div id="sessao-quadro-horario" class="content-section">
            <div class="card shadow-sm border-0 mb-4 d-print-none" style="border-top: 4px solid var(--azul-google);">
                <div
                    class="card-body bg-light d-flex flex-column flex-md-row justify-content-between align-items-md-center p-3">
                    <form method="GET" action="painel_coordenador.php" class="d-flex flex-grow-1 me-md-4 mb-3 mb-md-0"
                        id="formMudarQuadro">
                        <div class="input-group">
                            <span class="input-group-text bg-white fw-bold text-secondary"><i
                                    class="bi bi-display me-2"></i> Exibir Quadro:</span>
                            <select name="q_id" class="form-select"
                                onchange="window.location.href='?q_id=' + this.value + '#sessao-quadro-horario'">
                                <?php if (count($lista_quadros) == 0): ?>
                                    <option value="">Nenhum cenário criado...</option><?php endif; ?>
                                <?php foreach ($lista_quadros as $q): ?>
                                    <option value="<?= $q['id'] ?>" <?= $quadro_selecionado == $q['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($q['nome']) ?> (<?= htmlspecialchars($q['periodo_letivo']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary fw-bold text-nowrap" data-bs-toggle="modal"
                            data-bs-target="#modalNovoQuadro" title="Criar Novo Quadro"><i
                                class="bi bi-plus-lg me-1"></i></button>

                        <?php if ($quadro_selecionado): ?>
                            <button type="button" class="btn btn-outline-warning fw-bold text-dark" data-bs-toggle="modal"
                                data-bs-target="#modalDuplicarQuadro" title="Duplicar Cenário Atual"><i
                                    class="bi bi-copy"></i></button>

                            <form method="POST" action="painel_coordenador.php#sessao-quadro-horario"
                                onsubmit="return confirm('Excluir este Quadro inteiro e TODAS as suas aulas?');">
                                <input type="hidden" name="id_quadro" value="<?= $quadro_selecionado ?>">
                                <button type="submit" name="excluir_quadro" class="btn btn-outline-danger"
                                    title="Excluir Quadro"><i class="bi bi-trash"></i></button>
                            </form>

                            <button class="btn btn-outline-success fw-bold" onclick="window.print()"
                                title="Imprimir Grade"><i class="bi bi-printer me-1"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalNovoQuadro" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white border-0">
                            <h5 class="modal-title"><i class="bi bi-calendar-range me-2"></i>Criar Novo Cenário</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-start p-4">
                            <form method="POST" action="painel_coordenador.php#sessao-quadro-horario">
                                <div class="mb-3"><label class="form-label fw-bold small text-secondary">Nome do Cenário
                                        (Ex: Oficial 2026.1):</label><input type="text" name="nome_quadro"
                                        class="form-control" required></div>
                                <div class="mb-4"><label class="form-label fw-bold small text-secondary">Período
                                        Letivo:</label><input type="text" name="periodo_letivo" class="form-control"
                                        required placeholder="Ex: 2026.1" data-lh-pick="semestres" data-lh-create="semestres"></div>
                                <button type="submit" name="criar_quadro" class="btn btn-primary w-100 fw-bold">Salvar
                                    Quadro</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($quadro_selecionado): ?>
                <div class="modal fade" id="modalDuplicarQuadro" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content border-warning" style="border-width: 3px;">
                            <div class="modal-header bg-warning text-dark border-0">
                                <h5 class="modal-title fw-bold"><i class="bi bi-copy me-2"></i>Duplicar Cenário Atual</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-start p-4">
                                <div class="alert alert-light border-warning text-dark small mb-4">
                                    <i class="bi bi-info-circle-fill text-warning me-2"></i> Você está prestes a fazer uma
                                    cópia exata deste quadro e de todas as aulas que estão nele.
                                </div>
                                <form method="POST" action="painel_coordenador.php#sessao-quadro-horario">
                                    <input type="hidden" name="id_quadro_origem" value="<?= $quadro_selecionado ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small text-secondary">Nome da Cópia (Ex: Oficial
                                            2026.2):</label>
                                        <input type="text" name="novo_nome_quadro" class="form-control" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold small text-secondary">Novo Período Letivo:</label>
                                        <input type="text" name="novo_periodo_letivo" class="form-control" required
                                            placeholder="Ex: 2026.2" data-lh-pick="semestres" data-lh-create="semestres">
                                    </div>
                                    <button type="submit" name="duplicar_quadro"
                                        class="btn btn-warning w-100 fw-bold text-dark"><i class="bi bi-magic me-2"></i>
                                        Clonar Cenário Inteiro</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($quadro_selecionado): ?>

                <div class="card shadow-sm border-0 mb-4"></div>
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center"
                    onclick="abrirSanfona('formAlocarAulaBox', 'setaToggleForm')" style="cursor: pointer;">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-plus-circle-fill text-success me-2"
                            id="iconeToggleForm"></i>Alocar Aula no Quadro</h6>
                    <i class="bi bi-chevron-down text-muted transition-transform" id="setaToggleForm"></i>
                </div>

                <div id="formAlocarAulaBox" style="display: none;">
                    <div class="card-body bg-light p-4 border-top">
                        <form method="POST" action="painel_coordenador.php#sessao-quadro-horario">
                            <input type="hidden" name="id_quadro_ativo" value="<?= $quadro_selecionado ?>">

                            <div class="bg-white p-4 rounded-3 shadow-sm mb-4 border-start border-4 border-primary">
                                <h6 class="fw-bold text-primary mb-3 pb-2 border-bottom"><i
                                        class="bi bi-mortarboard-fill me-2"></i>1. Identificação da Turma e Matéria</h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold small text-secondary">Curso:</label>
                                        <select class="form-select bg-light" name="curso_aula" required data-lh-combobox data-lh-create="cursos" data-lh-value="nome">
                                            <option value="">Selecione...</option>
                                            <?php foreach ($cursos_cadastrados as $c): ?>
                                                <option value="<?= htmlspecialchars($c['nome']) ?>">
                                                    <?= htmlspecialchars($c['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold small text-secondary">Semestre:</label>
                                        <select class="form-select bg-light" name="semestre_aula" required data-lh-combobox data-lh-create="semestres" data-lh-value="nome">
                                            <option value="">Selecione...</option>
                                            <?php foreach ($semestres_cadastrados as $sem): ?>
                                                <option value="<?= htmlspecialchars($sem['nome']) ?>">
                                                    <?= htmlspecialchars($sem['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-secondary">Disciplina:</label>
                                        <select class="form-select bg-light" name="id_disciplina_aula" required data-lh-combobox data-lh-create="disciplinas">
                                            <option value="">Selecione a matéria...</option>
                                            <?php foreach ($disciplinas as $d): ?>
                                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold small text-secondary">Professor:</label>
                                        <select class="form-select bg-light border-primary border-opacity-50"
                                            name="id_professor_aula" required data-lh-combobox>
                                            <option value="">Selecione o docente...</option>
                                            <?php foreach ($professores as $p): ?>
                                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white p-4 rounded-3 shadow-sm mb-4 border-start border-4 border-success">
                                <h6 class="fw-bold text-success mb-3 pb-2 border-bottom"><i
                                        class="bi bi-clock-history me-2"></i>2. Horário e Formato da Aula</h6>
                                <div class="row g-3">
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold small text-secondary">Dia da Semana:</label>
                                        <select class="form-select bg-light" name="dia_semana" required>
                                            <option>Segunda</option>
                                            <option>Terça</option>
                                            <option>Quarta</option>
                                            <option>Quinta</option>
                                            <option>Sexta</option>
                                            <option>Sábado</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold small text-secondary">Turno:</label>
                                        <select class="form-select bg-light" name="turno_aula" required>
                                            <option>Matutino</option>
                                            <option>Vespertino</option>
                                            <option>Noturno</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold small text-secondary">Horário:</label>
                                        <select class="form-select bg-light" name="horario_aula" required>
                                            <option>1º e 2º Horários</option>
                                            <option>1º Horário</option>
                                            <option>2º Horário</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold small text-secondary">Modalidade:</label>
                                        <select class="form-select bg-light border-warning border-opacity-50"
                                            name="modalidade" onchange="travarProfEAD(this)" required>
                                            <option>Presencial</option>
                                            <option>EAD</option>
                                            <option>Híbrido</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold small text-secondary">Qtd. Alunos:</label>
                                        <input type="number" name="numero_alunos" class="form-control bg-light" required
                                            placeholder="Ex: 40">
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white p-4 rounded-3 shadow-sm mb-4 border-start border-4 border-warning">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom"><i
                                        class="bi bi-building-gear text-warning me-2"></i>3. Infraestrutura e Carga Horária
                                </h6>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold small text-secondary">Carga Horária (1 a
                                            8):</label>
                                        <input type="number" name="carga_horaria_total"
                                            class="form-control bg-light carga-total" min="1" max="8" value="2" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold small text-secondary">Horas Laboratório (0 a
                                            8):</label>
                                        <input type="number" name="horas_laboratorio" class="form-control bg-light" min="0"
                                            max="8" value="0" required
                                            oninput="let total = this.closest('.row').querySelector('.carga-total').value; if(parseInt(this.value) > parseInt(total)) { alert('Erro: Horas de laboratório não podem exceder a carga horária total!'); this.value = total; }">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-primary"><i
                                                class="bi bi-pc-display me-1"></i> Laboratório Especializado
                                            (Opcional):</label>
                                        <select class="form-select border-primary" name="id_laboratorio_aula"
                                            style="background-color: rgba(13, 110, 253, 0.05);" data-lh-combobox data-lh-create="laboratorios">
                                            <option value="">Nenhum laboratório...</option>
                                            <?php foreach ($laboratorios_cadastrados as $lab): ?>
                                                <option value="<?= $lab['id'] ?>"><?= htmlspecialchars($lab['nome']) ?>
                                                    (Capacidade: <?= $lab['capacidade'] ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="p-3 bg-light rounded-3 border border-success border-opacity-25">
                                    <label class="form-label fw-bold small text-success mb-3"><i
                                            class="bi bi-door-open-fill me-1"></i> Alocação em Sala de Aula Comum</label>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small text-secondary mb-1 fw-bold">Bloco:</label>
                                            <select class="form-select border-success border-opacity-50" name="bloco_aula" data-lh-combobox data-lh-create="blocos" data-lh-value="nome">
                                                <option value="">Nenhum...</option>
                                                <?php foreach ($blocos_cadastrados as $b): ?>
                                                    <option value="<?= htmlspecialchars($b['nome']) ?>">
                                                        <?= htmlspecialchars($b['nome']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-secondary mb-1 fw-bold">Andar:</label>
                                            <select class="form-select border-success border-opacity-50" name="andar_aula" data-lh-combobox data-lh-create="andares" data-lh-value="nome">
                                                <option value="">Nenhum...</option>
                                                <?php foreach ($andares_cadastrados as $a): ?>
                                                    <option value="<?= htmlspecialchars($a['nome']) ?>">
                                                        <?= htmlspecialchars($a['nome']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small text-secondary mb-1 fw-bold">Sala:</label>
                                            <select class="form-select border-success border-opacity-50" name="sala_aula" data-lh-combobox data-lh-create="salas" data-lh-value="nome">
                                                <option value="">Nenhuma...</option>
                                                <?php foreach ($salas_cadastradas as $s): ?>
                                                    <option value="<?= htmlspecialchars($s['nome']) ?>">
                                                        <?= htmlspecialchars($s['nome']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-4">
                                <button type="submit" name="salvar_aula_quadro"
                                    class="btn btn-primary btn-lg px-5 fw-bold shadow-sm"
                                    style="border-radius: 8px; transition: 0.3s;">
                                    <i class="bi bi-check2-circle me-2 fs-5 align-middle"></i> Confirmar Alocação no Quadro
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card shadow-sm border-0 mb-3 bg-light d-print-none" style="border-radius: 12px;">
                    <div class="card-body p-3 d-flex flex-column flex-md-row gap-3 align-items-center">
                        <div class="fw-bold text-secondary"><i class="bi bi-funnel-fill me-1 text-primary"></i> Filtrar
                            Grade:</div>
                        <select id="filtroTurnoGrade" class="form-select w-auto border-primary" onchange="filtrarGrade()">
                            <option value="todos">Todos os Turnos</option>
                            <option value="Matutino">Matutino</option>
                            <option value="Vespertino">Vespertino</option>
                            <option value="Noturno">Noturno</option>
                        </select>
                        <select id="filtroCursoGrade" class="form-select w-auto border-primary" onchange="filtrarGrade()">
                            <option value="todos">Todos os Cursos</option>
                            <?php foreach ($cursos_cadastrados as $c): ?>
                                <option value="<?= htmlspecialchars($c['nome']) ?>"><?= htmlspecialchars($c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select id="filtroModalidadeGrade" class="form-select w-auto border-primary"
                            onchange="filtrarGrade()">
                            <option value="todos">Todas Modalidades</option>
                            <option value="Presencial">Presencial</option>
                            <option value="EAD">EAD</option>
                            <option value="Híbrido">Híbrido</option>
                        </select>

                        <button class="btn btn-outline-secondary btn-sm fw-bold ms-auto"
                            onclick="document.getElementById('filtroTurnoGrade').value='todos'; document.getElementById('filtroCursoGrade').value='todos'; document.getElementById('filtroModalidadeGrade').value='todos'; filtrarGrade();">
                            <i class="bi bi-eraser-fill me-1"></i> Limpar Filtros
                        </button>
                    </div>
                </div>

                <?php
                $nome_cenario_impresso = "Grade de Horários";
                foreach ($lista_quadros as $q) {
                    if ($q['id'] == $quadro_selecionado) {
                        $nome_cenario_impresso = htmlspecialchars($q['nome']) . ' <span class="text-secondary fs-5 fw-normal">(' . htmlspecialchars($q['periodo_letivo']) . ')</span>';
                        break;
                    }
                }
                ?>
                <div class="print-only-header mb-4">
                    <div class="d-flex align-items-center border-bottom border-3 pb-3"
                        style="border-color: var(--verde-uniceplac) !important;">
                        <img src="uniceplac2.png" alt="UNICEPLAC" style="height: 55px; margin-right: 20px;">
                        <div class="border-start border-2 ps-4" style="border-color: #dee2e6 !important;">
                            <div class="text-muted small fw-bold text-uppercase mb-1" style="letter-spacing: 1px;">
                                Coordenação: <?= htmlspecialchars($_SESSION['nome']) ?>
                            </div>
                            <h3 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">
                                <?= $nome_cenario_impresso ?>
                            </h3>
                        </div>
                    </div>
                </div>

                <div class="bg-white border shadow-sm p-3 overflow-auto" style="border-radius: 12px;">
                    <div class="grade-wrapper">
                        <div class="grade-container">
                            <?php foreach ($dias_semana as $dia): ?>
                                <div class="grade-coluna">
                                    <div class="grade-cabecalho shadow-sm mb-2"><?= $dia ?></div>
                                    <div class="grade-corpo coluna-sortable" data-dia="<?= $dia ?>" style="min-height: 150px;">
                                        <?php
                                        if (empty($aulas_do_quadro[$dia])) {
                                            echo "<div class='grade-slot-livre text-center mt-4 text-muted small opacity-50 fw-bold'><i class='bi bi-cup-hot fs-4 d-block mb-1'></i>Livre</div>";
                                        } else {
                                            $aulas_dia = $aulas_do_quadro[$dia];
                                            foreach ($aulas_dia as $a) {
                                                $classe_turno = strtolower($a['turno']);

                                                // --- A MÁGICA DO VIDRO EAD ---
                                                $classe_ead = ($a['modalidade'] === 'EAD') ? 'aula-ead-glass' : '';
                                                ?>
                                                <div class="aula-card-google <?= $classe_turno ?> <?= $classe_ead ?> card-grade-aula"
                                                    data-id-aula="<?= (int) $a['id'] ?>"
                                                    data-turno="<?= htmlspecialchars($a['turno']) ?>"
                                                    data-curso="<?= htmlspecialchars($a['curso']) ?>"
                                                    data-modalidade="<?= htmlspecialchars($a['modalidade']) ?>">

                                                    <div class="position-absolute top-0 end-0 p-1 d-flex d-print-none">
                                                        <button type="button" class="btn btn-sm text-primary p-0 m-0 border-0 me-2"
                                                            data-bs-toggle="modal" data-bs-target="#editAulaQuadro<?= $a['id'] ?>"
                                                            title="Editar Aula"><i class="bi bi-pencil-fill"></i></button>
                                                        <form method="POST" action="painel_coordenador.php#sessao-quadro-horario"
                                                            onsubmit="return confirm('Remover esta aula do quadro?');">
                                                            <input type="hidden" name="id_aula_q" value="<?= $a['id'] ?>">
                                                            <button type="submit" name="excluir_aula_quadro"
                                                                class="btn btn-sm text-danger p-0 m-0 border-0" title="Excluir"><i
                                                                    class="bi bi-x-circle-fill"></i></button>
                                                        </form>
                                                    </div>

                                                    <div class="fw-bold text-dark lh-sm mb-1"
                                                        style="padding-right: 35px; font-size:0.85rem;">
                                                        <?= htmlspecialchars($a['curso']) ?> <br>
                                                        <span class="text-secondary"
                                                            style="font-size:0.75rem;"><?= htmlspecialchars($a['semestre']) ?></span>
                                                    </div>

                                                    <div
                                                        class="text-secondary small fw-bold mb-2 border-bottom pb-2 border-secondary border-opacity-25">
                                                        <?= htmlspecialchars($a['disc_nome']) ?>
                                                    </div>

                                                    <div>
                                                        <?php if ($a['modalidade'] === 'EAD'): ?>
                                                            <span class="badge bg-warning text-dark border border-warning shadow-sm mb-1"><i
                                                                    class="bi bi-laptop me-1"></i> EAD Online</span>
                                                        <?php else: ?>
                                                            <span class="badge selo-<?= $classe_turno ?> shadow-sm mb-1"><i
                                                                    class="bi bi-building-check me-1"></i> <?= $a['modalidade'] ?></span>
                                                            <div class="small lh-sm mt-1 text-dark fw-bold prof-nome"><i
                                                                    class="bi bi-person me-1"></i><?= htmlspecialchars($a['prof_nome'] ?? 'Sem Professor') ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if ($a['id_laboratorio']):
                                                        $lab_det = [];
                                                        if (!empty($a['lab_local']))
                                                            $lab_det[] = htmlspecialchars($a['lab_local']);
                                                        if (!empty($a['lab_andar']))
                                                            $lab_det[] = 'And ' . htmlspecialchars($a['lab_andar']);
                                                        $lab_str = !empty($lab_det) ? ' <span class="text-muted" style="font-size:0.7rem;">(' . implode(' - ', $lab_det) . ')</span>' : '';
                                                        ?>
                                                        <div class="small mt-2"><i class="bi bi-pc-display me-1 text-primary"></i><span
                                                                class="fw-bold text-primary"><?= htmlspecialchars($a['lab_nome']) ?></span><?= $lab_str ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($a['sala']) || !empty($a['bloco'])): ?>
                                                        <div class="small <?= $a['id_laboratorio'] ? 'mt-1' : 'mt-2' ?>"><i
                                                                class="bi bi-door-open me-1 text-success"></i><span
                                                                class="fw-bold text-success">Sala
                                                                <?= htmlspecialchars($a['sala'] ?? '-') ?></span><span class="text-muted"
                                                                style="font-size:0.7rem;">(Bl
                                                                <?= htmlspecialchars($a['bloco'] ?? '-') ?>)</span></div>
                                                    <?php endif; ?>

                                                    <div class="small lh-sm mt-2 fw-bold text-dark"><i
                                                            class="bi bi-clock me-1"></i><?= $a['turno'] ?> (<?= $a['horario'] ?>)</div>

                                                    <div class="mt-2 d-flex justify-content-between align-items-center">
                                                        <span class="small fw-bold text-muted"><i
                                                                class="bi bi-people-fill me-1"></i><?= $a['numero_alunos'] ?></span>
                                                        <span class="small text-muted"
                                                            style="font-size: 0.7rem;">CH:<?= $a['carga_horaria_total'] ?? 2 ?>h |
                                                            L:<?= $a['horas_laboratorio'] ?? 0 ?>h</span>
                                                    </div>
                                                </div>

                                                <div class="modal fade d-print-none" id="editAulaQuadro<?= $a['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content border-primary" style="border-width:3px;">
                                                            <div class="modal-header bg-primary text-white border-0">
                                                                <h5 class="modal-title">Editar Aula do Quadro</h5>
                                                                <button type="button" class="btn-close btn-close-white"
                                                                    data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body text-start">
                                                                <form method="POST"
                                                                    action="painel_coordenador.php#sessao-quadro-horario">
                                                                    <input type="hidden" name="editar_aula_quadro" value="1">
                                                                    <input type="hidden" name="id_aula_q" value="<?= $a['id'] ?>">
                                                                    <input type="hidden" name="id_quadro_ativo"
                                                                        value="<?= $quadro_selecionado ?>">
                                                                    <div class="row g-3 mb-3">
                                                                        <div class="col-md-2"><label
                                                                                class="form-label fw-bold small text-secondary">Dia:</label><select
                                                                                class="form-select" name="dia_semana" required>
                                                                                <option <?= $a['dia_semana'] == 'Segunda' ? 'selected' : '' ?>>Segunda</option>
                                                                                <option <?= $a['dia_semana'] == 'Terça' ? 'selected' : '' ?>>Terça</option>
                                                                                <option <?= $a['dia_semana'] == 'Quarta' ? 'selected' : '' ?>>Quarta</option>
                                                                                <option <?= $a['dia_semana'] == 'Quinta' ? 'selected' : '' ?>>Quinta</option>
                                                                                <option <?= $a['dia_semana'] == 'Sexta' ? 'selected' : '' ?>>Sexta</option>
                                                                                <option <?= $a['dia_semana'] == 'Sábado' ? 'selected' : '' ?>>Sábado</option>
                                                                            </select></div>
                                                                        <div class="col-md-2"><label
                                                                                class="form-label fw-bold small text-secondary">Turno:</label><select
                                                                                class="form-select" name="turno_aula" required>
                                                                                <option <?= $a['turno'] == 'Matutino' ? 'selected' : '' ?>>
                                                                                    Matutino</option>
                                                                                <option <?= $a['turno'] == 'Vespertino' ? 'selected' : '' ?>>Vespertino</option>
                                                                                <option <?= $a['turno'] == 'Noturno' ? 'selected' : '' ?>>
                                                                                    Noturno</option>
                                                                            </select></div>
                                                                        <div class="col-md-2"><label
                                                                                class="form-label fw-bold small text-secondary">Horário:</label><select
                                                                                class="form-select" name="horario_aula" required>
                                                                                <option <?= $a['horario'] == '1º e 2º Horários' ? 'selected' : '' ?>>1º e 2º Horários</option>
                                                                                <option <?= $a['horario'] == '1º Horário' ? 'selected' : '' ?>>1º Horário</option>
                                                                                <option <?= $a['horario'] == '2º Horário' ? 'selected' : '' ?>>2º Horário</option>
                                                                            </select></div>
                                                                        <div class="col-md-3"><label
                                                                                class="form-label fw-bold small text-secondary">Curso:</label><select
                                                                                class="form-select" name="curso_aula"
                                                                                required data-lh-combobox data-lh-create="cursos" data-lh-value="nome"><?php foreach ($cursos_cadastrados as $c): ?>
                                                                                    <option value="<?= htmlspecialchars($c['nome']) ?>"
                                                                                        <?= $c['nome'] == $a['curso'] ? 'selected' : '' ?>>
                                                                                        <?= htmlspecialchars($c['nome']) ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select></div>
                                                                        <div class="col-md-3"><label
                                                                                class="form-label fw-bold small text-secondary">Semestre:</label><select
                                                                                class="form-select" name="semestre_aula" required data-lh-combobox data-lh-create="semestres" data-lh-value="nome">
                                                                                <option value="">Selecione...</option>
                                                                                <?php foreach ($semestres_cadastrados as $sem): ?>
                                                                                    <option value="<?= htmlspecialchars($sem['nome']) ?>"
                                                                                        <?= $sem['nome'] == $a['semestre'] ? 'selected' : '' ?>><?= htmlspecialchars($sem['nome']) ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select></div>
                                                                    </div>
                                                                    <div class="row g-3 mb-3">
                                                                        <div class="col-md-4"><label
                                                                                class="form-label fw-bold small text-secondary">Disciplina:</label><select
                                                                                class="form-select" name="id_disciplina_aula"
                                                                                required data-lh-combobox data-lh-create="disciplinas"><?php foreach ($disciplinas as $d): ?>
                                                                                    <option value="<?= $d['id'] ?>"
                                                                                        <?= $d['id'] == $a['id_disciplina'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nome']) ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select></div>
                                                                        <div class="col-md-4"><label
                                                                                class="form-label fw-bold small text-secondary">Professor:</label><select
                                                                                class="form-select" name="id_professor_aula"
                                                                                required data-lh-combobox><?php foreach ($professores as $p): ?>
                                                                                    <option value="<?= $p['id'] ?>"
                                                                                        <?= $p['id'] == $a['id_professor'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nome']) ?>
                                                                                    </option><?php endforeach; ?>
                                                                            </select></div>
                                                                        <div class="col-md-2"><label
                                                                                class="form-label fw-bold small text-secondary">Modalidade:</label><select
                                                                                class="form-select" name="modalidade"
                                                                                onchange="travarProfEAD(this)" required>
                                                                                <option <?= $a['modalidade'] == 'Presencial' ? 'selected' : '' ?>>
                                                                                    Presencial</option>
                                                                                <option <?= $a['modalidade'] == 'EAD' ? 'selected' : '' ?>>
                                                                                    EAD</option>
                                                                                <option <?= $a['modalidade'] == 'Híbrido' ? 'selected' : '' ?>>
                                                                                    Híbrido</option>
                                                                            </select></div>
                                                                        <div class="col-md-2"><label
                                                                                class="form-label fw-bold small text-secondary">Qtd.
                                                                                Alunos:</label><input type="number" name="numero_alunos"
                                                                                class="form-control" value="<?= $a['numero_alunos'] ?>"
                                                                                required></div>
                                                                    </div>
                                                                    <div class="row g-3 mb-3">
                                                                        <div class="col-md-3"><label
                                                                                class="form-label fw-bold small text-secondary">Carga
                                                                                Horária (1 a 8):</label><input type="number"
                                                                                name="carga_horaria_total"
                                                                                class="form-control carga-total" min="1" max="8"
                                                                                value="<?= $a['carga_horaria_total'] ?? 2 ?>" required>
                                                                        </div>
                                                                        <div class="col-md-3"><label
                                                                                class="form-label fw-bold small text-secondary">Horas
                                                                                Lab (0 a 8):</label><input type="number"
                                                                                name="horas_laboratorio" class="form-control" min="0"
                                                                                max="8" value="<?= $a['horas_laboratorio'] ?? 0 ?>"
                                                                                required
                                                                                oninput="let total = this.closest('.row').querySelector('.carga-total').value; if(parseInt(this.value) > parseInt(total)) { alert('Erro: Horas de laboratório não podem exceder a carga horária total!'); this.value = total; }">
                                                                        </div>
                                                                    </div>
                                                                    <hr class="my-3 opacity-25">
                                                                    <div class="row g-3 mb-4">
                                                                        <div class="col-md-4">
                                                                            <label class="form-label fw-bold small text-primary"><i
                                                                                    class="bi bi-pc-display me-1"></i>
                                                                                Laboratório:</label>
                                                                            <select class="form-select border-primary"
                                                                                name="id_laboratorio_aula"
                                                                                style="background-color: rgba(13, 110, 253, 0.05);" data-lh-combobox data-lh-create="laboratorios">
                                                                                <option value="">Nenhum...</option>
                                                                                <?php foreach ($laboratorios_cadastrados as $lab): ?>
                                                                                    <option value="<?= $lab['id'] ?>"
                                                                                        <?= $lab['id'] == $a['id_laboratorio'] ? 'selected' : '' ?>>
                                                                                        <?= htmlspecialchars($lab['nome']) ?> (Cap:
                                                                                        <?= $lab['capacidade'] ?>)
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div
                                                                            class="col-md-8 border-start border-success border-opacity-25 ps-4">
                                                                            <label
                                                                                class="form-label fw-bold small text-success w-100 mb-2"><i
                                                                                    class="bi bi-door-open-fill fs-6 me-1"></i> Sala de
                                                                                Aula Comum</label>
                                                                            <div class="row g-2">
                                                                                <div class="col-md-4"><label
                                                                                        class="form-label small text-secondary mb-1">Bloco:</label><select
                                                                                        class="form-select border-success"
                                                                                        name="bloco_aula" data-lh-combobox data-lh-create="blocos" data-lh-value="nome">
                                                                                        <option value="">Nenhum...</option>
                                                                                        <?php foreach ($blocos_cadastrados as $b): ?>
                                                                                            <option
                                                                                                value="<?= htmlspecialchars($b['nome']) ?>"
                                                                                                <?= $b['nome'] == $a['bloco'] ? 'selected' : '' ?>><?= htmlspecialchars($b['nome']) ?>
                                                                                            </option><?php endforeach; ?>
                                                                                    </select></div>
                                                                                <div class="col-md-4"><label
                                                                                        class="form-label small text-secondary mb-1">Andar:</label><select
                                                                                        class="form-select border-success"
                                                                                        name="andar_aula" data-lh-combobox data-lh-create="andares" data-lh-value="nome">
                                                                                        <option value="">Nenhum...</option>
                                                                                        <?php foreach ($andares_cadastrados as $a_db): ?>
                                                                                            <option
                                                                                                value="<?= htmlspecialchars($a_db['nome']) ?>"
                                                                                                <?= $a_db['nome'] == $a['andar'] ? 'selected' : '' ?>><?= htmlspecialchars($a_db['nome']) ?>
                                                                                            </option><?php endforeach; ?>
                                                                                    </select></div>
                                                                                <div class="col-md-4"><label
                                                                                        class="form-label small text-secondary mb-1">Sala:</label><select
                                                                                        class="form-select border-success"
                                                                                        name="sala_aula" data-lh-combobox data-lh-create="salas" data-lh-value="nome">
                                                                                        <option value="">Nenhum...</option>
                                                                                        <?php foreach ($salas_cadastradas as $s): ?>
                                                                                            <option
                                                                                                value="<?= htmlspecialchars($s['nome']) ?>"
                                                                                                <?= $s['nome'] == $a['sala'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nome']) ?>
                                                                                            </option><?php endforeach; ?>
                                                                                    </select></div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="text-end"><button type="submit"
                                                                            name="editar_aula_quadro"
                                                                            class="btn btn-primary px-5 fw-bold"><i
                                                                                class="bi bi-pencil-fill me-2"></i> Salvar
                                                                            Edição</button></div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>