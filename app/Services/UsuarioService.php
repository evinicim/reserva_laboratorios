<?php
namespace App\Services;

use App\Config\Database;
use PDO;

class UsuarioService {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getPDO();
    }

    public function listar(): array {
        $sql = "SELECT id, nome, email, perfil, email_verificado, google_id,
                       CASE WHEN senha IS NULL OR senha = '' THEN 0 ELSE 1 END AS tem_senha
                FROM usuarios ORDER BY perfil ASC, nome ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function buscarPorEmail(string $email): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT id, nome, email, perfil, email_verificado FROM usuarios WHERE LOWER(email) = LOWER(?) LIMIT 1'
        );
        $stmt->execute([trim($email)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function criar(string $nome, string $email, string $perfil, string $senha = '', int $emailVerificado = 1): int {
        if (!in_array($perfil, ['coordenador', 'professor', 'suporte'], true)) {
            throw new \InvalidArgumentException('Perfil inválido.');
        }
        if (!app_email_institucional_valido($email)) {
            throw new \InvalidArgumentException('Use um e-mail institucional (@uniceplac.edu.br ou subdomínio, ex.: @esoftware.uniceplac.edu.br).');
        }

        $dup = $this->pdo->prepare('SELECT id, nome FROM usuarios WHERE LOWER(email) = LOWER(?) LIMIT 1');
        $dup->execute([trim($email)]);
        $existente = $dup->fetch(PDO::FETCH_ASSOC);
        if ($existente) {
            throw new \InvalidArgumentException(
                'Este e-mail já pertence a «' . $existente['nome'] . '». Veja na lista abaixo ou use outro e-mail.'
            );
        }

        if ($senha !== '' && strlen($senha) < 6) {
            throw new \InvalidArgumentException('A senha deve ter pelo menos 6 caracteres.');
        }

        $hash = $senha !== '' ? password_hash($senha, PASSWORD_DEFAULT) : null;
        $stmt = $this->pdo->prepare(
            'INSERT INTO usuarios (nome, email, senha, perfil, email_verificado) VALUES (?, ?, ?, ?, ?) RETURNING id'
        );
        $stmt->execute([trim($nome), trim($email), $hash, $perfil, $emailVerificado ? 1 : 0]);
        return (int) $stmt->fetchColumn();
    }

    public function atualizar(int $id, string $nome, string $email, string $perfil, int $emailVerificado): void {
        if (!in_array($perfil, ['coordenador', 'professor', 'suporte'], true)) {
            throw new \InvalidArgumentException('Perfil inválido.');
        }
        if (!app_email_institucional_valido($email)) {
            throw new \InvalidArgumentException('Use um e-mail institucional (@uniceplac.edu.br ou subdomínio, ex.: @esoftware.uniceplac.edu.br).');
        }

        $dup = $this->pdo->prepare('SELECT id FROM usuarios WHERE LOWER(email) = LOWER(?) AND id != ? LIMIT 1');
        $dup->execute([$email, $id]);
        if ($dup->fetchColumn()) {
            throw new \InvalidArgumentException('Este e-mail já está em uso por outro usuário.');
        }

        $stmt = $this->pdo->prepare(
            'UPDATE usuarios SET nome = ?, email = ?, perfil = ?, email_verificado = ? WHERE id = ?'
        );
        $stmt->execute([trim($nome), trim($email), $perfil, $emailVerificado ? 1 : 0, $id]);
    }

    public function definirSenha(int $id, string $senha): void {
        if (strlen($senha) < 6) {
            throw new \InvalidArgumentException('A senha deve ter pelo menos 6 caracteres.');
        }
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $this->pdo->prepare(
            'UPDATE usuarios SET senha = ?, token_verificacao = NULL, token_expira_em = NULL WHERE id = ?'
        )->execute([$hash, $id]);
    }

    public function gerarTokenRedefinicao(int $id): string {
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', time() + 86400);
        $this->pdo->prepare(
            'UPDATE usuarios SET token_verificacao = ?, token_expira_em = ? WHERE id = ?'
        )->execute([$token, $expira, $id]);
        return $token;
    }

    public function gerarTokenVerificacao(int $id): string {
        $token = bin2hex(random_bytes(32));
        $this->pdo->prepare(
            'UPDATE usuarios SET token_verificacao = ?, token_expira_em = NULL, email_verificado = 0 WHERE id = ?'
        )->execute([$token, $id]);
        return $token;
    }

    public function buscarPorTokenVerificacao(string $token): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM usuarios WHERE token_verificacao = ? AND token_expira_em IS NULL LIMIT 1'
        );
        $stmt->execute([trim($token)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function confirmarEmail(int $id): void {
        $this->pdo->prepare(
            'UPDATE usuarios SET email_verificado = 1, token_verificacao = NULL, token_expira_em = NULL WHERE id = ?'
        )->execute([$id]);
    }

    public function buscarPorTokenRedefinicao(string $token): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM usuarios WHERE token_verificacao = ? AND (
                token_expira_em IS NULL OR token_expira_em >= NOW()
            ) LIMIT 1"
        );
        $stmt->execute([trim($token)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function redefinirSenhaPorToken(string $token, string $senha): bool {
        $user = $this->buscarPorTokenRedefinicao($token);
        if (!$user) {
            return false;
        }
        $this->definirSenha((int) $user['id'], $senha);
        return true;
    }

    public function excluir(int $id, int $idAdminLogado): void {
        if ($id === $idAdminLogado) {
            throw new \InvalidArgumentException('Você não pode excluir sua própria conta.');
        }

        $user = $this->buscarPorId($id);
        if (!$user) {
            throw new \InvalidArgumentException('Usuário não encontrado.');
        }

        if ($user['perfil'] === 'coordenador') {
            $qtd = (int) $this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE perfil = 'coordenador'")->fetchColumn();
            if ($qtd <= 1) {
                throw new \InvalidArgumentException('Não é possível excluir o único coordenador do sistema.');
            }
        }

        $this->pdo->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
    }

    public function senhaTemporaria(): string {
        return substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789'), 0, 10);
    }
}
