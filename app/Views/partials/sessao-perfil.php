<?php
$perfil_label = match ($_SESSION['perfil'] ?? '') {
    'coordenador' => 'Coordenador',
    'suporte'     => 'Suporte TI',
    default       => 'Professor',
};
$perfil_badge_class = match ($_SESSION['perfil'] ?? '') {
    'coordenador' => 'bg-uniceplac',
    'suporte'     => 'bg-uniceplac',
    default       => 'bg-uniceplac',
};
?>
<div id="sessao-perfil" class="content-section container-fluid px-4 pb-5">
    <div class="card shadow-sm border-0 mb-4" style="border-top: 4px solid var(--roxo-uniceplac);">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold d-flex align-items-center" style="color:var(--roxo-uniceplac);">
                <i class="bi bi-person-circle fs-4 me-3"></i>Meu Perfil
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <img src="<?= htmlspecialchars($foto_atual) ?>" alt="Foto de perfil"
                        style="width:120px;height:120px;object-fit:cover;border-radius:50% !important;border:3px solid var(--laranja-uniceplac,#f07f3c);"
                        class="shadow mb-3" id="fotoPerfilGrande">
                    <div class="mb-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3"
                            onclick="document.getElementById('nova_foto_input').click()">
                            <i class="bi bi-camera me-1"></i> Alterar foto
                        </button>
                    </div>
                    <p class="text-muted small mb-0">JPG, PNG ou WEBP · alteração disponível somente aqui</p>
                </div>
                <div class="col-md-8">
                    <div class="bg-light p-4 rounded-3 mb-3">
                        <h6 class="fw-bold text-secondary text-uppercase small mb-3">Informações da conta</h6>
                        <p class="mb-2"><i class="bi bi-person-fill me-2 text-primary"></i><strong>Nome:</strong> <?= htmlspecialchars($_SESSION['nome']) ?></p>
                        <p class="mb-2"><i class="bi bi-envelope-fill me-2 text-primary"></i><strong>E-mail:</strong> <?= htmlspecialchars($_SESSION['email'] ?? 'Não informado') ?></p>
                        <p class="mb-0"><i class="bi bi-shield-fill me-2 text-success"></i><strong>Perfil:</strong>
                            <span class="badge <?= $perfil_badge_class ?> text-uppercase"><?= htmlspecialchars($perfil_label) ?></span>
                        </p>
                    </div>
                    <?php if (($_SESSION['perfil'] ?? '') === 'professor'): ?>
                        <div class="alert alert-info border-0 border-start border-4 border-info rounded-0 small mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Para alterar nome, perfil ou senha, solicite à <strong>Coordenação</strong>.
                        </div>
                    <?php elseif (($_SESSION['perfil'] ?? '') === 'coordenador'): ?>
                        <div class="alert alert-info border-0 border-start border-4 border-info rounded-0 small mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Gerencie outros usuários em <a href="javascript:void(0)" onclick="showSection('sessao-usuarios')">Usuários do Sistema</a>.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info border-0 border-start border-4 border-info rounded-0 small mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Para alterar dados da conta, fale com a <strong>Coordenação</strong>.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
