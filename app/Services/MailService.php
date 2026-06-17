<?php
namespace App\Services;

class MailService {
    private function brevoApiKey(): ?string {
        $key = app_env('MAIL_PASSWORD', '');
        if ($key !== '' && str_starts_with($key, 'xkeysib-')) {
            return $key;
        }
        $apiKey = app_env('BREVO_API_KEY', '');
        return ($apiKey !== '' && str_starts_with($apiKey, 'xkeysib-')) ? $apiKey : null;
    }

    private function sender(): array {
        $from = app_env('MAIL_FROM_ADDRESS', app_env('MAIL_USERNAME', 'noreply@uniceplac.edu.br'));
        $name = app_env('MAIL_FROM_NAME', 'LabHub UNICEPLAC');
        return ['email' => $from, 'name' => $name];
    }

    private function sendViaBrevoApi(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody): bool {
        $apiKey = $this->brevoApiKey();
        if ($apiKey === null) {
            return false;
        }

        $payload = json_encode([
            'sender'      => $this->sender(),
            'to'          => [['email' => $toEmail, 'name' => $toName]],
            'subject'     => $subject,
            'htmlContent' => $htmlBody,
            'textContent' => $textBody,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . $apiKey,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            $hint = str_contains((string) $response, 'unrecognised IP') || str_contains((string) $response, 'Unauthorized IP')
                ? ' Autorize o IP do servidor em https://app.brevo.com/security/authorised_ips'
                : '';
            error_log('[MailService] brevo_api: HTTP ' . $httpCode . ' ' . ($error ?: (string) $response) . $hint);
            return false;
        }

        return true;
    }

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

    private function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody): bool {
        if ($this->brevoApiKey() !== null) {
            return $this->sendViaBrevoApi($toEmail, $toName, $subject, $htmlBody, $textBody);
        }

        try {
            $mail = $this->createMailer();
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody;
            $mail->send();
            return true;
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Unauthorized IP') || str_contains($msg, '525')) {
                $msg .= ' — autorize o IP em https://app.brevo.com/security/authorised_ips';
            }
            error_log('[MailService] smtp: ' . $msg);
            return false;
        }
    }

    public function isConfigured(): bool {
        if ($this->brevoApiKey() !== null) {
            return app_env('MAIL_FROM_ADDRESS') !== null && app_env('MAIL_FROM_ADDRESS') !== '';
        }
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
        $link = $this->baseUrl() . '/redefinir_senha.php?token=' . urlencode($token);
        $html = '
            <div style="font-family:Segoe UI,sans-serif;max-width:520px;margin:0 auto;">
                <h2 style="color:#00734F;">Redefinição de senha</h2>
                <p>Olá, <strong>' . htmlspecialchars($nome) . '</strong>!</p>
                <p>Recebemos uma solicitação para redefinir a senha da sua conta no LabHub.</p>
                <p><a href="' . htmlspecialchars($link) . '" style="display:inline-block;background:#00734F;color:#fff;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:600;">Criar nova senha</a></p>
                <p style="color:#666;font-size:13px;">O link expira em 24 horas. Se você não solicitou, ignore este e-mail.</p>
                <p style="color:#999;font-size:12px;word-break:break-all;">' . htmlspecialchars($link) . '</p>
            </div>';
        $text = "Acesse para redefinir sua senha (válido por 24h): {$link}";

        return $this->sendMail($email, $nome, 'Redefinição de senha — LabHub UNICEPLAC', $html, $text);
    }

    public function enviarVerificacaoEmail(string $email, string $nome, string $token): bool {
        $link = $this->baseUrl() . '/verificar.php?token=' . urlencode($token);
        $html = '
            <div style="font-family:Segoe UI,sans-serif;max-width:520px;margin:0 auto;">
                <h2 style="color:#00734F;">Confirme seu e-mail</h2>
                <p>Olá, <strong>' . htmlspecialchars($nome) . '</strong>!</p>
                <p>Clique no botão abaixo para ativar sua conta:</p>
                <p><a href="' . htmlspecialchars($link) . '" style="display:inline-block;background:#00734F;color:#fff;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:600;">Confirmar e-mail</a></p>
            </div>';
        $text = "Confirme seu e-mail: {$link}";

        return $this->sendMail($email, $nome, 'Confirme seu e-mail — LabHub UNICEPLAC', $html, $text);
    }

    public function enviarSenhaTemporaria(string $email, string $nome, string $senhaTemp): bool {
        $link = $this->baseUrl() . '/index.php';
        $html = '
            <div style="font-family:Segoe UI,sans-serif;max-width:520px;margin:0 auto;">
                <h2 style="color:#00734F;">Senha redefinida</h2>
                <p>Olá, <strong>' . htmlspecialchars($nome) . '</strong>!</p>
                <p>A coordenação definiu uma senha temporária para sua conta:</p>
                <p style="font-size:18px;font-weight:bold;background:#f4f6f8;padding:12px;border-radius:8px;">' . htmlspecialchars($senhaTemp) . '</p>
                <p>Recomendamos alterá-la após o login em <strong>Meu Perfil</strong>.</p>
                <p><a href="' . htmlspecialchars($link) . '">Acessar o sistema</a></p>
            </div>';
        $text = "Senha temporária: {$senhaTemp}. Acesse: {$link}";

        return $this->sendMail($email, $nome, 'Nova senha temporária — LabHub UNICEPLAC', $html, $text);
    }
}
