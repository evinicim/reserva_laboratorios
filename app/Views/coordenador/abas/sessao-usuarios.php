<style>
    #sessao-usuarios .usuarios-card-header {
        background: var(--bs-body-bg);
        border-bottom: 1px solid var(--bs-border-color);
    }
    #sessao-usuarios .usuarios-info-bar {
        background: rgba(0, 115, 79, 0.06);
        border-bottom: 1px solid var(--bs-border-color);
        color: var(--bs-secondary-color);
    }
    #sessao-usuarios .usuarios-title {
        color: var(--verde-uniceplac);
    }
    [data-bs-theme="dark"] #sessao-usuarios .usuarios-info-bar {
        background: rgba(0, 115, 79, 0.15);
        color: #cbd5e1;
    }
    [data-bs-theme="dark"] #sessao-usuarios .usuarios-title {
        color: #6ee7b7;
    }
    [data-bs-theme="dark"] #sessao-usuarios .usuarios-nome {
        color: #f1f5f9 !important;
    }
    [data-bs-theme="dark"] #sessao-usuarios .table thead th {
        background: #1e293b !important;
        color: #e2e8f0;
        border-color: #334155;
    }
    [data-bs-theme="dark"] #sessao-usuarios .btn-outline-secondary {
        color: #94a3b8;
        border-color: #475569;
    }
    [data-bs-theme="dark"] #sessao-usuarios .badge.bg-light {
        background: #334155 !important;
        color: #e2e8f0 !important;
        border-color: #475569 !important;
    }
    #sessao-usuarios .btn-reenviar-email {
        --bs-btn-color: var(--verde-uniceplac);
        --bs-btn-border-color: var(--verde-uniceplac);
    }
    #sessao-usuarios .btn-reenviar-email:hover {
        background: var(--verde-uniceplac);
        color: #fff;
    }
</style>

<div id="sessao-usuarios" class="content-section container-fluid px-4 pb-5">
    <div class="card shadow-sm border-0 mb-4" style="border-top: 4px solid var(--roxo-uniceplac);">
        <div class="card-header usuarios-card-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0 fw-bold usuarios-title">
                <i class="bi bi-people-fill me-3 fs-4"></i>Usuários do Sistema
            </h5>
            <div class="d-flex align-items-center gap-2">
                <span class="badge" style="background:var(--roxo-uniceplac);"><?= count($lista_usuarios) ?> cadastrados</span>
                <button type="button" class="btn btn-sm btn-uniceplac fw-bold" data-bs-toggle="modal" data-bs-target="#modalNovoUsuario">
                    <i class="bi bi-person-plus me-1"></i> Novo usuário
                </button>
            </div>
        </div>
        <div class="card-body usuarios-info-bar py-3">
            <p class="small mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Cadastre, edite dados, redefina senhas ou envie e-mail.
                Usuários <span class="badge bg-warning text-dark">Pendente</span> — use o botão verde
                <i class="bi bi-send-fill"></i> na linha para <strong>reenviar confirmação</strong>.
                <?php if (empty($mail_configurado)): ?>
                    <span class="text-warning fw-semibold d-block mt-1">E-mail não configurado — defina RESEND_API_KEY no servidor.</span>
                <?php else: ?>
                    <span class="text-success fw-semibold d-block mt-1">Provedor: <?= htmlspecialchars($mail_provedor ?? 'Brevo') ?> — confira Spam/Promoções se não chegar em 2 min.</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height:620px;overflow-y:auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="ps-4 py-3">Nome</th>
                            <th>E-mail</th>
                            <th>Perfil</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista_usuarios as $u):
                            $isSelf = (int) $u['id'] === (int) ($_SESSION['usuario_id'] ?? 0);
                            $temGoogle = !empty($u['google_id']);
                            $pendente = empty($u['email_verificado']);
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold usuarios-nome"><?= htmlspecialchars($u['nome']) ?></span>
                                    <?php if ($isSelf): ?>
                                        <span class="badge bg-secondary ms-1">Você</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small"><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <?php if ($u['perfil'] === 'coordenador'): ?>
                                        <span class="badge" style="background:var(--verde-uniceplac);">Coordenador</span>
                                    <?php elseif ($u['perfil'] === 'professor'): ?>
                                        <span class="badge" style="background:var(--roxo-uniceplac);">Professor</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark">Suporte TI</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= !$pendente
                                        ? '<span class="badge bg-success rounded-pill"><i class="bi bi-check-circle me-1"></i>Verificado</span>'
                                        : '<span class="badge bg-warning text-dark rounded-pill"><i class="bi bi-hourglass-split me-1"></i>Pendente</span>' ?>
                                    <?php if ($temGoogle): ?>
                                        <span class="badge bg-light text-dark border ms-1" title="Login Google"><i class="bi bi-google"></i></span>
                                    <?php endif; ?>
                                    <?php if (empty($u['tem_senha']) && !$temGoogle): ?>
                                        <span class="badge bg-secondary ms-1">Sem senha</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($pendente && !empty($mail_configurado)): ?>
                                            <form method="POST" action="painel_coordenador.php" class="d-inline"
                                                onsubmit="return confirm('Reenviar e-mail de confirmação para <?= htmlspecialchars(addslashes($u['email'])) ?>?');">
                                                <input type="hidden" name="admin_enviar_verificacao" value="1">
                                                <input type="hidden" name="id_usuario" value="<?= (int) $u['id'] ?>">
                                                <button type="submit" class="btn btn-reenviar-email" title="Reenviar confirmação">
                                                    <i class="bi bi-send-fill"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                                            data-bs-target="#modalEditUser<?= (int) $u['id'] ?>" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal"
                                            data-bs-target="#modalSenhaUser<?= (int) $u['id'] ?>" title="Senha">
                                            <i class="bi bi-key"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal"
                                            data-bs-target="#modalEmailUser<?= (int) $u['id'] ?>" title="E-mails">
                                            <i class="bi bi-envelope"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php foreach ($lista_usuarios as $u):
        $uid = (int) $u['id'];
        $isSelf = $uid === (int) ($_SESSION['usuario_id'] ?? 0);
        ?>
        <!-- Editar -->
        <div class="modal fade" id="modalEditUser<?= $uid ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold"><i class="bi bi-pencil me-2"></i>Editar usuário</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="painel_coordenador.php" id="editUserForm<?= $uid ?>">
                        <input type="hidden" name="admin_editar_usuario" value="1">
                        <input type="hidden" name="id_usuario" value="<?= $uid ?>">
                        <div class="modal-body text-start">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Nome</label>
                                <input type="text" name="nome_usuario" class="form-control" required
                                    value="<?= htmlspecialchars($u['nome']) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">E-mail</label>
                                <input type="email" name="email_usuario" class="form-control" required
                                    value="<?= htmlspecialchars($u['email']) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Perfil</label>
                                <select name="perfil_usuario" class="form-select" required <?= $isSelf ? 'disabled' : '' ?>>
                                    <option value="coordenador" <?= $u['perfil'] === 'coordenador' ? 'selected' : '' ?>>Coordenador</option>
                                    <option value="professor" <?= $u['perfil'] === 'professor' ? 'selected' : '' ?>>Professor</option>
                                    <option value="suporte" <?= $u['perfil'] === 'suporte' ? 'selected' : '' ?>>Suporte TI</option>
                                </select>
                                <?php if ($isSelf): ?>
                                    <input type="hidden" name="perfil_usuario" value="<?= htmlspecialchars($u['perfil']) ?>">
                                    <small class="text-muted">Você não pode alterar seu próprio perfil.</small>
                                <?php endif; ?>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="email_verificado" value="1" id="verif<?= $uid ?>"
                                    <?= !empty($u['email_verificado']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="verif<?= $uid ?>">E-mail verificado</label>
                            </div>
                        </div>
                    </form>
                    <div class="modal-footer flex-wrap gap-2">
                        <?php if (!$isSelf): ?>
                            <form method="POST" action="painel_coordenador.php" class="me-auto"
                                onsubmit="return confirm('Excluir <?= htmlspecialchars(addslashes($u['nome'])) ?>? Esta ação não pode ser desfeita.');">
                                <input type="hidden" name="admin_excluir_usuario" value="1">
                                <input type="hidden" name="id_usuario" value="<?= $uid ?>">
                                <button type="submit" class="btn btn-outline-danger">
                                    <i class="bi bi-trash"></i> Excluir
                                </button>
                            </form>
                        <?php endif; ?>
                        <?php if (!empty($mail_configurado)): ?>
                            <form method="POST" action="painel_coordenador.php">
                                <input type="hidden" name="admin_enviar_verificacao" value="1">
                                <input type="hidden" name="id_usuario" value="<?= $uid ?>">
                                <button type="submit" class="btn btn-outline-success">
                                    <i class="bi bi-send-fill me-1"></i> Reenviar confirmação
                                </button>
                            </form>
                        <?php endif; ?>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" form="editUserForm<?= $uid ?>" class="btn btn-primary">Salvar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Senha -->
        <div class="modal fade" id="modalSenhaUser<?= $uid ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold"><i class="bi bi-key me-2"></i>Senha — <?= htmlspecialchars($u['nome']) ?></h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-start">
                        <form method="POST" action="painel_coordenador.php" class="mb-4 pb-3 border-bottom">
                            <input type="hidden" name="admin_redefinir_senha" value="1">
                            <input type="hidden" name="id_usuario" value="<?= $uid ?>">
                            <label class="form-label small fw-bold">Nova senha (manual)</label>
                            <input type="password" name="nova_senha" class="form-control mb-2" minlength="6" required placeholder="Mínimo 6 caracteres">
                            <input type="password" name="confirmar_senha" class="form-control mb-3" minlength="6" required placeholder="Confirmar senha">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="enviar_senha_email" value="1" id="sendPwd<?= $uid ?>">
                                <label class="form-check-label" for="sendPwd<?= $uid ?>">Enviar senha por e-mail</label>
                            </div>
                            <button type="submit" class="btn btn-warning w-100 fw-bold">Definir senha agora</button>
                        </form>
                        <form method="POST" action="painel_coordenador.php">
                            <input type="hidden" name="admin_enviar_reset" value="1">
                            <input type="hidden" name="id_usuario" value="<?= $uid ?>">
                            <p class="small text-muted">Ou envie um link seguro para o usuário criar a própria senha (válido 24h).</p>
                            <button type="submit" class="btn btn-outline-primary w-100" <?= empty($mail_configurado) ? 'disabled title="Configure e-mail no servidor"' : '' ?>>
                                <i class="bi bi-envelope-arrow-up me-1"></i> Enviar link de redefinição
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- E-mails -->
        <div class="modal fade" id="modalEmailUser<?= $uid ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold"><i class="bi bi-envelope me-2"></i>E-mails — <?= htmlspecialchars($u['nome']) ?></h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-start">
                        <form method="POST" action="painel_coordenador.php" class="mb-3">
                            <input type="hidden" name="admin_enviar_verificacao" value="1">
                            <input type="hidden" name="id_usuario" value="<?= $uid ?>">
                            <p class="small text-muted">Reenvia o link para confirmar o e-mail (marca como Pendente até clicar).</p>
                            <button type="submit" class="btn btn-success w-100 fw-bold" <?= empty($mail_configurado) ? 'disabled' : '' ?>>
                                <i class="bi bi-send-fill me-1"></i> Reenviar confirmação de e-mail
                            </button>
                        </form>
                        <form method="POST" action="painel_coordenador.php">
                            <input type="hidden" name="admin_enviar_reset" value="1">
                            <input type="hidden" name="id_usuario" value="<?= $uid ?>">
                            <p class="small text-muted">Link para o usuário redefinir ou criar a senha.</p>
                            <button type="submit" class="btn btn-outline-primary w-100" <?= empty($mail_configurado) ? 'disabled' : '' ?>>
                                <i class="bi bi-key me-1"></i> Enviar redefinição de senha
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="modal fade" id="modalNovoUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold"><i class="bi bi-person-plus me-2"></i>Novo usuário</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="painel_coordenador.php" id="formNovoUsuario">
                    <input type="hidden" name="admin_criar_usuario" value="1">
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nome completo</label>
                            <input type="text" name="nome_usuario" class="form-control" required placeholder="Ex: Maria Souza">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">E-mail</label>
                            <input type="email" name="email_usuario" class="form-control" required placeholder="seu@email.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Perfil</label>
                            <select name="perfil_usuario" class="form-select" required>
                                <option value="professor">Professor</option>
                                <option value="coordenador">Coordenador</option>
                                <option value="suporte">Suporte TI</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Senha inicial <span class="text-muted fw-normal">(opcional)</span></label>
                            <input type="password" name="nova_senha" id="novoUserSenha" class="form-control mb-2" minlength="6" placeholder="Mínimo 6 caracteres">
                            <input type="password" name="confirmar_senha" class="form-control" minlength="6" placeholder="Confirmar senha">
                        </div>
                        <div class="border rounded p-3 mb-0 bg-body-tertiary">
                            <p class="small fw-bold mb-2"><i class="bi bi-envelope me-1"></i> E-mail ao criar</p>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="enviar_confirmacao_email" value="1"
                                    id="novoUserSendConfirm" <?= !empty($mail_configurado) ? 'checked' : 'disabled' ?>>
                                <label class="form-check-label" for="novoUserSendConfirm">
                                    Enviar e-mail de confirmação agora
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="enviar_link_senha" value="1"
                                    id="novoUserSendLink" <?= !empty($mail_configurado) ? 'checked' : 'disabled' ?>>
                                <label class="form-check-label" for="novoUserSendLink">
                                    Enviar link para criar senha (se senha em branco)
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="enviar_senha_email" value="1" id="novoUserSendPwd"
                                    <?= empty($mail_configurado) ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="novoUserSendPwd">Enviar senha por e-mail (se preenchida acima)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="email_verificado" value="1" id="novoUserVerif">
                                <label class="form-check-label" for="novoUserVerif">Marcar como verificado (pula confirmação por e-mail)</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold">Criar e enviar e-mails</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const verif = document.getElementById('novoUserVerif');
    const sendConfirm = document.getElementById('novoUserSendConfirm');
    const sendLink = document.getElementById('novoUserSendLink');
    const senha = document.getElementById('novoUserSenha');
    if (!verif || !sendConfirm) return;

    function syncNovoUsuarioChecks() {
        if (verif.checked) {
            sendConfirm.checked = false;
            sendConfirm.disabled = true;
        } else if (<?= !empty($mail_configurado) ? 'true' : 'false' ?>) {
            sendConfirm.disabled = false;
        }
        if (senha && senha.value.trim() !== '') {
            if (sendLink) sendLink.checked = false;
        }
    }
    verif.addEventListener('change', syncNovoUsuarioChecks);
    if (senha) senha.addEventListener('input', syncNovoUsuarioChecks);
    syncNovoUsuarioChecks();
})();
</script>
