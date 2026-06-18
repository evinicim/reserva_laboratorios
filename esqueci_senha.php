<?php
$controller_data = require __DIR__ . '/app/router.php';
if (is_array($controller_data)) {
    extract($controller_data);
} else {
    $mensagem = '';
    $tipo = '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci minha senha — LabHub UNICEPLAC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --verde-uniceplac: #00734F; --laranja-uniceplac: #f07f3c; }
        body { background: #f0f2f5; margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1.5rem; font-family: 'Segoe UI', sans-serif; }
        .login-shell { width: min(100%, 28rem); }
        .card-box { border: none; border-top: 6px solid var(--laranja-uniceplac); border-radius: 12px; }
        .btn-uniceplac { background: var(--verde-uniceplac); color: #fff; font-weight: 600; border: none; }
        .btn-uniceplac:hover { background: #045238; color: #fff; }
    </style>
</head>
<body>
    <main class="login-shell">
        <div class="card card-box shadow-lg w-100">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <img src="uniceplac2.png" alt="UNICEPLAC" style="max-height: 80px;" class="mb-3">
                    <h5 class="fw-bold text-success">Esqueci minha senha</h5>
                    <p class="text-muted small mb-0">Enviaremos um link para redefinir sua senha.</p>
                </div>

                <?php if ($mensagem): ?>
                    <div class="alert alert-<?= htmlspecialchars($tipo ?: 'info') ?> py-2 small text-center"><?= $mensagem ?></div>
                <?php endif; ?>

                <form action="esqueci_senha.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">E-mail</label>
                        <input type="email" name="email" class="form-control" required
                            placeholder="seu@email.com">
                    </div>
                    <button type="submit" class="btn btn-uniceplac w-100 py-2 mb-3">Enviar link de redefinição</button>
                </form>
                <div class="text-center">
                    <a href="index.php" class="small fw-bold text-decoration-none" style="color: var(--laranja-uniceplac);">← Voltar ao login</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
