<div id="sessao-usuarios" class="content-section container-fluid px-4 pb-5">
    <div class="card shadow-sm border-0 mb-4" style="border-top: 4px solid var(--roxo-uniceplac);">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0 fw-bold" style="color:var(--roxo-uniceplac);">
                <i class="bi bi-people-fill me-3 fs-4"></i>Usuários do Sistema
            </h5>
            <div class="d-flex align-items-center gap-2">
                <span class="badge" style="background:var(--roxo-uniceplac);"><?= count($lista_usuarios) ?> cadastrados</span>
                <button type="button" class="btn btn-sm btn-uniceplac fw-bold" data-bs-toggle="modal" data-bs-target="#modalNovoUsuario">
                    <i class="bi bi-person-plus me-1"></i> Novo usuário
                </button>
            </div>
        </div>
        <div class="card-body border-bottom bg-light py-3">
            <p class="text-muted small mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Cadastre, edite dados, redefina senhas ou envie e-mail de recuperação.
                Professores também podem se cadastrar em <a href="cadastro.php" target="_blank" rel="noopener">Solicitar Cadastro</a> (perfil Professor).
                <?php if (empty($mail_configurado)): ?>
                    <span class="text-warning fw-semibold">SMTP não configurado — envios por e-mail ficarão indisponíveis até definir MAIL_* no .env.</span>
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
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-dark"><?= htmlspecialchars($u['nome']) ?></span>
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
                                    <?= !empty($u['email_verificado'])
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
                    <form method="POST" action="painel_coordenador.php">
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
                        <div class="modal-footer">
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
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
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
                            <button type="submit" class="btn btn-outline-primary w-100" <?= empty($mail_configurado) ? 'disabled title="Configure SMTP no .env"' : '' ?>>
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
                            <p class="small text-muted">Reenvia o link para confirmar o e-mail institucional.</p>
                            <button type="submit" class="btn btn-outline-success w-100" <?= empty($mail_configurado) ? 'disabled' : '' ?>>
                                <i class="bi bi-patch-check me-1"></i> Enviar confirmação de e-mail
                            </button>
                        </form>
                        <form method="POST" action="painel_coordenador.php">
                            <input type="hidden" name="admin_enviar_reset" value="1">
                            <input type="hidden" name="id_usuario" value="<?= $uid ?>">
                            <p class="small text-muted">Link para o usuário redefinir a senha.</p>
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
                <form method="POST" action="painel_coordenador.php">
                    <input type="hidden" name="admin_criar_usuario" value="1">
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nome completo</label>
                            <input type="text" name="nome_usuario" class="form-control" required placeholder="Ex: Maria Souza">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">E-mail institucional</label>
                            <input type="email" name="email_usuario" class="form-control" required placeholder="nome@uniceplac.edu.br">
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
                            <input type="password" name="nova_senha" class="form-control mb-2" minlength="6" placeholder="Mínimo 6 caracteres">
                            <input type="password" name="confirmar_senha" class="form-control" minlength="6" placeholder="Confirmar senha">
                            <small class="text-muted">Deixe em branco e use o botão de e-mail depois para enviar link de redefinição.</small>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="email_verificado" value="1" id="novoUserVerif" checked>
                            <label class="form-check-label" for="novoUserVerif">E-mail já verificado (pode acessar imediatamente)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="enviar_senha_email" value="1" id="novoUserSendPwd"
                                <?= empty($mail_configurado) ? 'disabled' : '' ?>>
                            <label class="form-check-label" for="novoUserSendPwd">Enviar senha por e-mail (se preenchida acima)</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-bold">Criar usuário</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
