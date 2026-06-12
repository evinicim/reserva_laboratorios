<?php
use Illuminate\Http\Request;
/**
 * Router Central — Mapeia arquivos PHP legados para Controllers MVC
 *
 * Ponto único de bootstrap: inicializa sessão, configura autoload PSR-4
 * e despacha para o Controller correto baseado no arquivo chamado.
 */

// ── Sessão (uma única vez por request) ──────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Autoload Composer (Google API, PHPMailer via Composer, etc.) ─────────────
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// ── Autoload PSR-4 para namespace App\ ──────────────────────────────────────
spl_autoload_register(function ($class) {
    $prefix   = 'App\\';
    $base_dir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file           = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// ── Bootstrap Eloquent (lazy) — só conecta ao banco quando o controller precisa ──

// ── Tabela de rotas: arquivo → [Controller, método] ─────────────────────────
function getControllerAndAction() {
    $page = $_GET['page'] ?? basename($_SERVER['PHP_SELF'], '.php');

    $routes = [
        // Autenticação
        'index'          => ['AuthController', 'login'],
        'login'          => ['AuthController', 'login'],
        'cadastro'       => ['AuthController', 'cadastro'],
        'login_google'   => ['AuthController', 'loginGoogle'],
        'logout'         => ['AuthController', 'logout'],
        'verificar'      => ['AuthController', 'verificarEmail'],
        'redefinir_senha'=> ['AuthController', 'redefinirSenha'],

        // Agendamentos
        'Agendamento'          => ['AgendamentoController', 'criar'],
        'editor_agendamento'   => ['AgendamentoController', 'editar'],

        // Painéis (delegação — os arquivos legados ainda processam o HTML)
        'painel_professor'  => ['PainelController', 'professor'],
        'painel_coordenador'=> ['CoordenadorController', 'index'],
        'painel_suporte'    => ['PainelController', 'suporte'],

        // AJAX — SOS
        'check_sos'        => ['SOSController', 'contarPendentes'],
        'check_sos_status' => ['SOSController', 'listarStatus'],
        'check_notificacoes' => ['NotificacaoController', 'listar'],
        'api_cadastros'      => ['CadastroApiController', 'handle'],
    ];

    return $routes[$page] ?? null;
}

// ── Despachador ──────────────────────────────────────────────────────────────
function executeRouter() {
    $route = getControllerAndAction();

    if (!$route) {
        return null;
    }

    list($controllerName, $action) = $route;

    $controllerClass = "App\\Controllers\\{$controllerName}";

    if (!class_exists($controllerClass)) {
        return null;
    }

    // 1. Captura a requisição atual usando o componente do Laravel
    $request = Request::capture();

    require_once __DIR__ . '/Config/env.php';
    if ($action !== 'login' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        app_boot_database();
    }

    $controller = new $controllerClass($request);

    if (!method_exists($controller, $action)) {
        return null;
    }

    // (Opcional) Se os seus métodos também precisarem do request, 
    // você poderia passá-lo aqui: $controller->$action($request);
    return $controller->$action();
}

// Executa e devolve dados para a view legada (index.php, cadastro.php, etc.)
return executeRouter();
?>
