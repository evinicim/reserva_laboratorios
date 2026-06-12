<?php
namespace App\Controllers;

use App\Models\User;
use Google\Client as GoogleClient;
use Google\Service\Oauth2 as GoogleOauth2;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;

class AuthController extends BaseController {

    protected Request $request;

    /**
     * Aceita o Request via injeção de dependência (feita pelo router).
     * Fallback: captura da requisição global se não for injetado.
     */
    public function __construct(?Request $request = null) {
        $this->request = $request ?? Request::capture();
    }

    /**
     * Página de login
     */
    public function login() {
        if ($this->request->isMethod('post')) {
            return $this->processLogin();
        }

        // Lê mensagens da sessão PHP nativa (Illuminate session não está disponível
        // sem o kernel completo do Laravel — usamos $_SESSION diretamente).
        $erro = '';
        $sucesso = '';

        if (isset($_SESSION['error'])) {
            $erro = $_SESSION['error'];
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            $sucesso = $_SESSION['success'];
            unset($_SESSION['success']);
        }

        if ($this->request->query('msg') == 'cadastro_ok') {
            $sucesso = "Cadastro realizado! <strong>Verifique sua caixa de e-mail</strong> para ativar a conta.";
        } elseif ($this->request->query('msg') == 'email_confirmado') {
            $sucesso = "E-mail confirmado com sucesso! Você já pode fazer login.";
        }

        return compact('erro', 'sucesso');
    }

    /**
     * Processa login via email/senha
     */
    private function processLogin() {
        $email = trim($this->request->input('email'));
        $senha_digitada = $this->request->input('senha');

        $pdo = \App\Config\Database::getInstance()->getPDO();
        $stmt = $pdo->prepare('SELECT id, nome, email, senha, perfil, foto_perfil FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($usuario && password_verify($senha_digitada, $usuario['senha'])) {
            // [DESABILITADO] Verificação de e-mail obrigatória — não é essencial por enquanto.
            // Reativar quando o fluxo de SMTP estiver configurado em produção.
            // if ($usuario->email_verificado == 0) {
            //     $this->redirectWithError('index.php', "Seu acesso está bloqueado. Por favor, confirme seu e-mail.");
            // }

            session_regenerate_id(true);

            $_SESSION['usuario_id']  = $usuario['id'];
            $_SESSION['nome']        = $usuario['nome'];
            $_SESSION['email']       = $usuario['email'];
            $_SESSION['perfil']      = $usuario['perfil'];
            $_SESSION['foto_perfil'] = $usuario['foto_perfil'] ?? null;

            $destinos = [
                'coordenador' => 'painel_coordenador.php',
                'suporte'     => 'painel_suporte.php',
                'professor'   => 'painel_professor.php',
            ];

            $url = $destinos[$usuario['perfil']] ?? 'index.php';
            $this->redirect($url);
        } else {
            $this->redirectWithError('index.php', "E-mail ou senha incorretos!");
        }
    }

    /**
     * Página de cadastro
     */
    public function cadastro() {
        if ($this->request->isMethod('post')) {
            return $this->processCadastro();
        }

        $mensagem = '';
        return compact('mensagem');
    }

    /**
     * Processa cadastro
     */
    private function processCadastro() {
        $nome = trim($this->request->input('nome'));
        $email = trim($this->request->input('email'));
        $senha = $this->request->input('senha');
        $senha_confirm = $this->request->input('confirmar_senha');
        
        $mensagem = '';

        if (!preg_match('/@uniceplac\.edu\.br$/', $email)) {
            $mensagem = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Apenas e-mails <strong>@uniceplac.edu.br</strong>.</div>';
        } elseif ($senha !== $senha_confirm) {
            $mensagem = '<div class="alert alert-danger"><i class="bi bi-shield-x me-2"></i>As senhas não coincidem.</div>';
        } elseif (strlen($senha) < 6) {
            $mensagem = '<div class="alert alert-danger"><i class="bi bi-shield-x me-2"></i>A senha deve ter pelo menos 6 caracteres.</div>';
        } else {
            // Eloquent: verifica duplicidade
            if (User::where('email', $email)->exists()) {
                $mensagem = '<div class="alert alert-danger"><i class="bi bi-person-x-fill me-2"></i>E-mail já cadastrado. Tente recuperar a senha.</div>';
            } else {
                // Eloquent: Mass Assignment (criação direta)
                // [DESABILITADO] Geração de token de verificação — não é essencial por enquanto.
                // Reativar junto com o envio de e-mail quando o SMTP estiver configurado.
                // $token = bin2hex(random_bytes(32));

                $user = User::create([
                    'nome'             => $nome,
                    'email'            => $email,
                    'senha'            => password_hash($senha, PASSWORD_DEFAULT),
                    'perfil'           => 'professor',
                    'email_verificado' => 1, // Conta já ativa — verificação de e-mail desabilitada por enquanto
                    // 'token_verificacao' => $token, // Reativar com o fluxo de e-mail
                ]);

                if ($user) {
                    // [DESABILITADO] Envio de e-mail de verificação — não é essencial por enquanto.
                    // Reativar quando o SMTP estiver configurado em produção.
                    // $this->enviarEmailVerificacao($email, $nome, $token);
                    $this->redirect('index.php?msg=cadastro_ok');
                } else {
                    $mensagem = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill me-2"></i>Erro ao cadastrar usuário.</div>';
                }
            }
        }
        return compact('mensagem');
    }

    /**
     * Envia e-mail de verificação após cadastro
     */
    private function enviarEmailVerificacao(string $email, string $nome, string $token): void {
        $mailerPath = __DIR__ . '/../../PHPMailer/src/';
        require_once $mailerPath . 'Exception.php';
        require_once $mailerPath . 'PHPMailer.php';
        require_once $mailerPath . 'SMTP.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Configuração SMTP — ajuste as credenciais no .env ou diretamente aqui
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAIL_HOST']       ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USERNAME']   ?? '';
            $mail->Password   = $_ENV['MAIL_PASSWORD']   ?? '';
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)($_ENV['MAIL_PORT'] ?? 587);
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(
                $_ENV['MAIL_FROM_ADDRESS'] ?? $mail->Username,
                $_ENV['MAIL_FROM_NAME']    ?? 'Central de Reservas UNICEPLAC'
            );
            $mail->addAddress($email, $nome);

            $baseUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/labs', '/');
            $link    = $baseUrl . '/verificar.php?token=' . urlencode($token);

            $mail->isHTML(true);
            $mail->Subject = 'Confirme seu e-mail — Reserva de Laboratórios';
            $mail->Body    = "
                <p>Olá, <strong>" . htmlspecialchars($nome) . "</strong>!</p>
                <p>Clique no link abaixo para ativar sua conta:</p>
                <p><a href=\"{$link}\">{$link}</a></p>
                <p>Se você não criou esta conta, ignore este e-mail.</p>
            ";
            $mail->AltBody = "Acesse o link para ativar sua conta: {$link}";

            $mail->send();
        } catch (\Exception $e) {
            // Loga o erro mas não interrompe o fluxo — o usuário já foi cadastrado
            error_log('[enviarEmailVerificacao] Falha ao enviar e-mail para ' . $email . ': ' . $e->getMessage());
        }
    }

    /**
     * Login via Google OAuth2
     */
    public function loginGoogle() {
        require_once __DIR__ . '/../Config/env.php';
        app_load_env(dirname(__DIR__, 2));

        $clientId = app_env('GOOGLE_CLIENT_ID', '');
        $clientSecret = app_env('GOOGLE_CLIENT_SECRET', '');
        $redirectUri = app_env('GOOGLE_REDIRECT_URI', 'http://localhost:8080/login_google.php');

        if ($clientId === '' || $clientSecret === '') {
            $this->redirectWithError('index.php', 'Login Google não configurado. Use e-mail e senha.');
        }

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->addScope("email");
        $client->addScope("profile");

        // Bypassa validação de SSL local
        $guzzleClient = new GuzzleClient(['verify' => false]);
        $client->setHttpClient($guzzleClient);

        // Se não houver código, redireciona o usuário pro Google
        if (!$this->request->has('code')) {
            $authUrl = $client->createAuthUrl();
            header("Location: " . filter_var($authUrl, FILTER_SANITIZE_URL));
            exit;
        }

        // --- CALLBACK ---
        try {
            $token = $client->fetchAccessTokenWithAuthCode($this->request->input('code'));
            if (isset($token['error'])) {
                throw new \Exception($token['error_description'] ?? 'Erro ao obter token do Google');
            }

            $client->setAccessToken($token['access_token']);
            $google_oauth = new GoogleOauth2($client);
            $google_account_info = $google_oauth->userinfo->get();

            $email = $google_account_info->email;
            $nome = $google_account_info->name;
            $google_id = $google_account_info->id;
            $foto_perfil = $google_account_info->picture;

            // Eloquent: Verifica se o usuário existe
            $usuario = User::where('email', $email)->first();

            if ($usuario) {
                // Atualiza dados do Google se for um usuário legado que entrou com Google agora
                $usuario->update([
                    'google_id' => $google_id,
                    'foto_perfil' => $foto_perfil,
                    'email_verificado' => 1
                ]);
            } else {
                // Eloquent: Criação de novo usuário do Google
                $usuario = User::create([
                    'nome' => $nome,
                    'email' => $email,
                    'google_id' => $google_id,
                    'foto_perfil' => $foto_perfil,
                    'perfil' => 'professor',
                    'email_verificado' => 1
                ]);
            }

            session_regenerate_id(true);

            $_SESSION['usuario_id']  = $usuario->id;
            $_SESSION['nome']        = $usuario->nome;
            $_SESSION['perfil']      = $usuario->perfil;
            $_SESSION['foto_perfil'] = $usuario->foto_perfil;

            $destinos = [
                'coordenador' => 'painel_coordenador.php',
                'suporte'     => 'painel_suporte.php',
                'professor'   => 'painel_professor.php',
            ];

            $url = $destinos[$usuario->perfil] ?? 'index.php';
            $this->redirect($url);

        } catch (\Exception $e) {
            $this->redirectWithError('index.php', "Erro no login com Google: " . $e->getMessage());
        }
    }

    /**
     * Logout
     */
    public function logout() {
        session_destroy();
        $this->redirect('index.php');
    }

    /**
     * Verifica email por token
     */
    public function verificarEmail() {
        $mensagem   = "";
        $tipo_alerta = "";

        if (isset($_GET['token']) && !empty(trim($_GET['token']))) {
            $token = trim($_GET['token']);

            try {
                $usuarioSvc = new \App\Services\UsuarioService();
                $usuario    = $usuarioSvc->buscarPorTokenVerificacao($token);

                if ($usuario) {
                    $usuarioSvc->confirmarEmail((int) $usuario['id']);
                    $mensagem    = "Excelente, " . htmlspecialchars($usuario['nome']) . "! Seu e-mail foi verificado com sucesso.";
                    $tipo_alerta = "success";
                } else {
                    $mensagem    = "Link de verificação inválido ou sua conta já foi verificada anteriormente.";
                    $tipo_alerta = "danger";
                }
            } catch (\Exception $e) {
                $mensagem    = "Erro ao conectar com o banco de dados: " . $e->getMessage();
                $tipo_alerta = "danger";
            }
        } else {
            $mensagem    = "Nenhum código de verificação foi fornecido. Por favor, acesse o link enviado para o seu e-mail.";
            $tipo_alerta = "warning";
        }

        return compact('mensagem', 'tipo_alerta');
    }

    /**
     * Redefinição de senha via link enviado por e-mail
     */
    public function redefinirSenha() {
        $usuarioSvc = new \App\Services\UsuarioService();
        $token      = trim($this->request->query('token', ''));
        $erro       = '';
        $sucesso    = '';
        $tokenValido = false;
        $usuario    = null;

        if ($token !== '') {
            $usuario = $usuarioSvc->buscarPorTokenRedefinicao($token);
            $tokenValido = (bool) $usuario;
        }

        if ($this->request->isMethod('post')) {
            $tokenPost = trim($this->request->input('token', ''));
            $senha     = $this->request->input('senha', '');
            $confirma  = $this->request->input('confirmar_senha', '');

            if ($senha !== $confirma) {
                $erro = 'As senhas não coincidem.';
            } elseif (strlen($senha) < 6) {
                $erro = 'A senha deve ter pelo menos 6 caracteres.';
            } elseif (!$usuarioSvc->redefinirSenhaPorToken($tokenPost, $senha)) {
                $erro = 'Link inválido ou expirado. Solicite um novo link à coordenação.';
            } else {
                $sucesso = 'Senha alterada com sucesso! Você já pode fazer login.';
                $tokenValido = false;
            }
        }

        return compact('token', 'erro', 'sucesso', 'tokenValido', 'usuario');
    }
}
?>
