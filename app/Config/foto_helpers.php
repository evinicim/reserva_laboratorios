<?php

/**
 * Resolve URL exibível da foto de perfil (upload local, URL Google ou avatar padrão).
 */
function app_foto_perfil_url(?string $fotoPerfil, ?string $nome = null): string
{
    $foto = trim((string) $fotoPerfil);

    if ($foto !== '' && preg_match('#^https?://#i', $foto)) {
        return $foto;
    }

    if ($foto !== '') {
        $candidatos = [
            $foto,
            ltrim(str_replace('\\', '/', $foto), '/'),
        ];
        $root = dirname(__DIR__, 2);
        foreach ($candidatos as $path) {
            if ($path !== '' && file_exists($path)) {
                return $path;
            }
            $abs = $root . '/' . ltrim($path, '/');
            if (file_exists($abs)) {
                return ltrim($path, '/');
            }
        }
    }

    if ($nome !== null && trim($nome) !== '') {
        return app_foto_iniciais_data_uri($nome);
    }

    return 'img/avatar-padrao.svg';
}

/**
 * Avatar SVG inline com iniciais do nome (fallback quando não há arquivo).
 */
function app_foto_iniciais_data_uri(string $nome): string
{
    $partes = preg_split('/\s+/u', trim($nome), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $iniciais = '';
    foreach (array_slice($partes, 0, 2) as $p) {
        $iniciais .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    if ($iniciais === '') {
        $iniciais = '?';
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120">'
        . '<circle cx="60" cy="60" r="60" fill="#00734F"/>'
        . '<text x="60" y="60" dominant-baseline="central" text-anchor="middle" '
        . 'fill="#ffffff" font-family="Segoe UI,sans-serif" font-size="42" font-weight="600">'
        . htmlspecialchars($iniciais, ENT_XML1 | ENT_QUOTES, 'UTF-8')
        . '</text></svg>';

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * Sincroniza sessão com o banco e retorna URL da foto para exibição.
 */
function app_foto_perfil_usuario(PDO $pdo, int $userId): string
{
    if (!isset($_SESSION['foto_perfil']) || !isset($_SESSION['email'])) {
        $stmt = $pdo->prepare('SELECT nome, email, foto_perfil FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $_SESSION['nome']         = $_SESSION['nome'] ?? $row['nome'];
            $_SESSION['email']        = $row['email'] ?? null;
            $_SESSION['foto_perfil']  = $row['foto_perfil'] ?? null;
        }
    }

    return app_foto_perfil_url(
        $_SESSION['foto_perfil'] ?? null,
        $_SESSION['nome'] ?? null
    );
}
