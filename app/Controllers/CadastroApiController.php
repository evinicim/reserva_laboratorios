<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;

class CadastroApiController extends BaseController {
    private PDO $pdo;

    private array $tipos = [
        'disciplinas'  => ['table' => 'disciplinas',  'column' => 'nome', 'id' => true],
        'cursos'       => ['table' => 'cursos',       'column' => 'nome', 'id' => true],
        'semestres'    => ['table' => 'semestres',    'column' => 'nome', 'id' => true],
        'blocos'       => ['table' => 'blocos',       'column' => 'nome', 'id' => true],
        'andares'      => ['table' => 'andares',      'column' => 'nome', 'id' => true],
        'salas'        => ['table' => 'salas',        'column' => 'nome', 'id' => true],
        'laboratorios' => ['table' => 'laboratorios', 'column' => 'nome', 'id' => true],
        'professores'  => ['table' => 'usuarios',     'column' => 'nome', 'id' => true, 'where' => "perfil = 'professor'"],
    ];

    public function __construct() {
        $this->requireAuth();
        $this->pdo = Database::getInstance()->getPDO();
    }

    public function handle() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->listar();
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->criar();
        }
        $this->json(['ok' => false, 'error' => 'Método não permitido'], 405);
    }

    private function listar(): void {
        $tipo = trim((string) ($_GET['tipo'] ?? ''));
        $q    = trim((string) ($_GET['q'] ?? ''));

        if (!isset($this->tipos[$tipo])) {
            $this->json(['ok' => false, 'error' => 'Tipo inválido'], 400);
        }

        $cfg    = $this->tipos[$tipo];
        $col    = $cfg['column'];
        $table  = $cfg['table'];
        $where  = $cfg['where'] ?? '1=1';
        $params = [];

        $sql = "SELECT id, {$col} AS nome FROM {$table} WHERE {$where}";
        if ($q !== '') {
            $sql .= " AND LOWER({$col}) LIKE :q";
            $params[':q'] = '%' . mb_strtolower($q) . '%';
        }
        $sql .= " ORDER BY {$col} ASC LIMIT 50";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['ok' => true, 'items' => $items]);
    }

    private function criar(): void {
        if (($_SESSION['perfil'] ?? '') !== 'coordenador') {
            $this->json(['ok' => false, 'error' => 'Sem permissão'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        $tipo = trim((string) ($input['tipo'] ?? ''));
        $nome = trim((string) ($input['nome'] ?? ''));

        if (!isset($this->tipos[$tipo]) || $nome === '') {
            $this->json(['ok' => false, 'error' => 'Dados inválidos'], 400);
        }

        $cfg   = $this->tipos[$tipo];
        $col   = $cfg['column'];
        $table = $cfg['table'];

        $stmt = $this->pdo->prepare("SELECT id, {$col} AS nome FROM {$table} WHERE LOWER({$col}) = LOWER(?) LIMIT 1");
        $stmt->execute([$nome]);
        $existente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existente) {
            $this->json(['ok' => true, 'item' => $existente, 'created' => false]);
        }

        if ($tipo === 'laboratorios') {
            $cap = (int) ($input['capacidade'] ?? 30);
            $this->pdo->prepare("INSERT INTO laboratorios (nome, capacidade, localizacao, andar) VALUES (?, ?, ?, ?)")
                ->execute([$nome, $cap, trim((string) ($input['localizacao'] ?? '')), trim((string) ($input['andar'] ?? ''))]);
        } else {
            $this->pdo->prepare("INSERT INTO {$table} ({$col}) VALUES (?)")->execute([$nome]);
        }

        $id = (int) $this->pdo->lastInsertId();
        $this->json([
            'ok'      => true,
            'created' => true,
            'item'    => ['id' => $id, 'nome' => $nome],
        ]);
    }
}
