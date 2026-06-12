<?php
namespace App\Models;

use PDO;

class SOS extends BaseModel {
    protected $table = 'chamados_suporte';

    /**
     * Conta chamados pendentes
     */
    public function contarPendentes() {
        $sql       = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = 'pendente'";
        $stmt      = $this->pdo->query($sql);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$resultado['total'];
    }

    /**
     * Lista chamados (todos ou filtrado por status)
     * Coluna correta: data_hora (conforme schema do banco)
     */
    public function listarTodos($status = null) {
        $sql = "SELECT * FROM {$this->table}";

        if ($status) {
            $sql .= " WHERE status = :status";
        }

        $sql .= " ORDER BY data_hora DESC";

        $stmt = $this->pdo->prepare($sql);

        if ($status) {
            $stmt->bindParam(':status', $status);
        }

        $stmt->execute();
        return $this->fetchAll($stmt);
    }

    /**
     * Lista chamados pendentes e retorna HTML + quantidade para o polling AJAX
     * Usado por check_sos_status.php
     */
    public function listarStatus() {
        $chamados     = $this->listarTodos('pendente');
        $qtd_suporte  = count($chamados);

        $html_suporte = '';
        if ($qtd_suporte > 0) {
            $html_suporte .= '<div class="alert-sos-banner alert-sos-notificacao" role="button" title="Clique para ver os chamados">';
            $html_suporte .= '<div class="alert-sos-banner-header">';
            $html_suporte .= '<i class="bi bi-headset"></i>';
            $html_suporte .= '<strong>' . $qtd_suporte . ' chamado(s) aguardando atendimento</strong>';
            $html_suporte .= '<small>(clique para abrir)</small></div>';
            $html_suporte .= '<ul class="alert-sos-banner-list">';
            foreach ($chamados as $c) {
                $html_suporte .= '<li><strong>' . htmlspecialchars($c['professor_nome'] ?? '') . '</strong> — ';
                $html_suporte .= htmlspecialchars($c['laboratorio'] ?? '') . ': ';
                $html_suporte .= htmlspecialchars($c['mensagem'] ?? '') . '</li>';
            }
            $html_suporte .= '</ul></div>';
        }

        return ['qtd_suporte' => $qtd_suporte, 'html_suporte' => $html_suporte];
    }

    /**
     * Busca chamado por ID
     */
    public function buscarPorId($id) {
        return $this->findById($id);
    }

    /**
     * Cria novo chamado SOS
     * Não passa data_hora — coluna tem DEFAULT NOW() no schema
     */
    public function criar($id_professor, $laboratorio, $mensagem, $professor_nome = '') {
        $sql  = "INSERT INTO {$this->table} (id_professor, professor_nome, laboratorio, mensagem, status)
                 VALUES (:id_professor, :professor_nome, :laboratorio, :mensagem, 'pendente')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id_professor', $id_professor);
        $stmt->bindParam(':professor_nome', $professor_nome);
        $stmt->bindParam(':laboratorio', $laboratorio);
        $stmt->bindParam(':mensagem', $mensagem);

        return $stmt->execute();
    }

    /**
     * Atualiza status do chamado
     */
    public function atualizarStatus($id, $status) {
        $sql  = "UPDATE {$this->table} SET status = :status WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>
