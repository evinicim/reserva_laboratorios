<?php
$controller_data = require __DIR__ . '/app/router.php';
if (is_array($controller_data)) {
    extract($controller_data);
} else {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir senha — LabHub UNICEPLAC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --verde-uniceplac: #00734F; --laranja-uniceplac: #f07f3c; }
        body { background: #f0f2f5; margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1.5rem; font-family: 'Segoe UI', sans-serif; box-sizing: border-box; }
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
                    <h5 class="fw-bold text-success">Redefinir senha</h5>
                </div>

                <?php if ($sucesso): ?>
                    <div class="alert alert-success text-center"><?= htmlspecialchars($sucesso) ?></div>
                    <a href="index.php" class="btn btn-uniceplac w-100 py-2">Ir para o login</a>
                <?php elseif (!$tokenValido && !$erro): ?>
                    <div class="alert alert-warning text-center">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Link inválido ou expirado. Solicite um novo link à coordenação.
                    </div>
                    <a href="index.php" class="btn btn-outline-secondary w-100">Voltar ao login</a>
                <?php else: ?>
                    <?php if ($erro): ?>
                        <div class="alert alert-danger py-2 small"><?= htmlspecialchars($erro) ?></div>
                    <?php endif; ?>
                    <?php if ($usuario): ?>
                        <p class="text-muted small text-center mb-3">Conta: <strong><?= htmlspecialchars($usuario['nome']) ?></strong></p>
                    <?php endif; ?>
                    <form method="POST" action="redefinir_senha.php?token=<?= urlencode($token) ?>">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-success">Nova senha</label>
                            <input type="password" name="senha" class="form-control" minlength="6" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-success">Confirmar senha</label>
                            <input type="password" name="confirmar_senha" class="form-control" minlength="6" required>
                        </div>
                        <button type="submit" class="btn btn-uniceplac w-100 py-2">Salvar nova senha</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
