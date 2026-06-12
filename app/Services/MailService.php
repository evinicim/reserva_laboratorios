<?php
namespace App\Services;

class MailService {
    private function createMailer(): \PHPMailer\PHPMailer\PHPMailer {
        $mailerPath = __DIR__ . '/../../PHPMailer/src/';
        require_once $mailerPath . 'Exception.php';
        require_once $mailerPath . 'PHPMailer.php';
        require_once $mailerPath . 'SMTP.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = app_env('MAIL_HOST', 'smtp.gmail.com');
        $mail->SMTPAuth   = true;
        $mail->Username   = app_env('MAIL_USERNAME', '');
        $mail->Password   = app_env('MAIL_PASSWORD', '');
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) app_env('MAIL_PORT', '587');
        $mail->CharSet    = 'UTF-8';

        $from = app_env('MAIL_FROM_ADDRESS', app_env('MAIL_USERNAME', 'noreply@uniceplac.edu.br'));
        $name = app_env('MAIL_FROM_NAME', 'LabHub UNICEPLAC');
        $mail->setFrom($from, $name);

        return $mail;
    }

    public function isConfigured(): bool {
        return (app_env('MAIL_USERNAME') !== null && app_env('MAIL_USERNAME') !== '')
            || (app_env('MAIL_HOST') !== null && app_env('MAIL_HOST') !== 'smtp.gmail.com');
    }

    public function baseUrl(): string {
        $url = app_env('APP_URL', '');
        if ($url === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
            return $scheme . '://' . $host;
        }
        return rtrim($url, '/');
    }

    public function enviarRedefinicaoSenha(string $email, string $nome, string $token): bool {
        try {
            $mail = $this->createMailer();
            $mail->addAddress($email, $nome);
            $link = $this->baseUrl() . '/redefinir_senha.php?token=' . urlencode($token);

            $mail->isHTML(true);
            $mail->Subject = 'Redefinição de senha — LabHub UNICEPLAC';
            $mail->Body    = '
                <div style="font-family:Segoe UI,sans-serif;max-width:520px;margin:0 auto;">
                    <h2 style="color:#00734F;">Redefinição de senha</h2>
                    <p>Olá, <strong>' . htmlspecialchars($nome) . '</strong>!</p>
                    <p>Recebemos uma solicitação para redefinir a senha da sua conta no LabHub.</p>
                    <p><a href="' . htmlspecialchars($link) . '" style="display:inline-block;background:#00734F;color:#fff;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:600;">Criar nova senha</a></p>
                    <p style="color:#666;font-size:13px;">O link expira em 24 horas. Se você não solicitou, ignore este e-mail.</p>
                    <p style="color:#999;font-size:12px;word-break:break-all;">' . htmlspecialchars($link) . '</p>
                </div>';
            $mail->AltBody = "Acesse para redefinir sua senha (válido por 24h): {$link}";

            $mail->send();
            return true;
        } catch (\Throwable $e) {
            error_log('[MailService] redefinicao: ' . $e->getMessage());
            return false;
        }
    }

    public function enviarVerificacaoEmail(string $email, string $nome, string $token): bool {
        try {
            $mail = $this->createMailer();
            $mail->addAddress($email, $nome);
            $link = $this->baseUrl() . '/verificar.php?token=' . urlencode($token);

            $mail->isHTML(true);
            $mail->Subject = 'Confirme seu e-mail — LabHub UNICEPLAC';
            $mail->Body    = '
                <div style="font-family:Segoe UI,sans-serif;max-width:520px;margin:0 auto;">
                    <h2 style="color:#00734F;">Confirme seu e-mail</h2>
                    <p>Olá, <strong>' . htmlspecialchars($nome) . '</strong>!</p>
                    <p>Clique no botão abaixo para ativar sua conta:</p>
                    <p><a href="' . htmlspecialchars($link) . '" style="display:inline-block;background:#00734F;color:#fff;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:600;">Confirmar e-mail</a></p>
                </div>';
            $mail->AltBody = "Confirme seu e-mail: {$link}";

            $mail->send();
            return true;
        } catch (\Throwable $e) {
            error_log('[MailService] verificacao: ' . $e->getMessage());
            return false;
        }
    }

    public function enviarSenhaTemporaria(string $email, string $nome, string $senhaTemp): bool {
        try {
            $mail = $this->createMailer();
            $mail->addAddress($email, $nome);
            $link = $this->baseUrl() . '/index.php';

            $mail->isHTML(true);
            $mail->Subject = 'Nova senha temporária — LabHub UNICEPLAC';
            $mail->Body    = '
                <div style="font-family:Segoe UI,sans-serif;max-width:520px;margin:0 auto;">
                    <h2 style="color:#00734F;">Senha redefinida</h2>
                    <p>Olá, <strong>' . htmlspecialchars($nome) . '</strong>!</p>
                    <p>A coordenação definiu uma senha temporária para sua conta:</p>
                    <p style="font-size:18px;font-weight:bold;background:#f4f6f8;padding:12px;border-radius:8px;">' . htmlspecialchars($senhaTemp) . '</p>
                    <p>Recomendamos alterá-la após o login em <strong>Meu Perfil</strong>.</p>
                    <p><a href="' . htmlspecialchars($link) . '">Acessar o sistema</a></p>
                </div>';
            $mail->AltBody = "Senha temporária: {$senhaTemp}. Acesse: {$link}";

            $mail->send();
            return true;
        } catch (\Throwable $e) {
            error_log('[MailService] senha_temp: ' . $e->getMessage());
            return false;
        }
    }
}
