<?php
namespace App\Controllers;

use App\Models\Agendamento;
use App\Models\SOS as SOSModel;

class NotificacaoController extends BaseController {
    public function __construct() {
        $this->requireAuth();
    }

    public function listar() {

        $perfil = $_SESSION['perfil'] ?? '';
        $items  = [];

        try {
            if ($perfil === 'coordenador') {
                $items = $this->itemsReservasPendentes();
            } elseif ($perfil === 'professor') {
                $items = $this->itemsReservasProfessor((int) $_SESSION['usuario_id']);
            } elseif ($perfil === 'suporte') {
                $items = $this->itemsSosPendentes();
            }
        } catch (\Exception $e) {
            $this->json(['qtd' => 0, 'items' => []]);
        }

        $this->json([
            'qtd'   => count($items),
            'items' => $items,
        ]);
    }

    private function itemsReservasPendentes(): array {
        $agendamento = new Agendamento();
        $pendentes   = $agendamento->listarSolicitacoesPendentes();
        $items       = [];

        foreach ($pendentes as $p) {
            $items[] = [
                'id'        => (int) $p['id'],
                'tipo'      => 'reserva',
                'titulo'    => ($p['professor'] ?? 'Professor') . ' — ' . ($p['laboratorio'] ?? 'Lab'),
                'subtitulo' => ($p['disciplina'] ?? '') . ' · ' . ($p['turno'] ?? '') . ' ' . ($p['periodo'] ?? ''),
                'data'      => !empty($p['data_reserva']) ? date('d/m/Y', strtotime($p['data_reserva'])) : '',
                'icon'      => 'bi-hourglass-split',
                'color'     => 'warning',
            ];
        }

        return $items;
    }

    private function itemsReservasProfessor(int $idProfessor): array {
        $agendamento = new Agendamento();
        $pendentes   = $agendamento->listarPendentesProfessor($idProfessor);
        $items       = [];

        foreach ($pendentes as $p) {
            $items[] = [
                'id'        => (int) $p['id'],
                'tipo'      => 'reserva',
                'titulo'    => ($p['laboratorio'] ?? 'Laboratório') . ' — aguardando aprovação',
                'subtitulo' => ($p['disciplina'] ?? '') . ' · ' . ($p['turno'] ?? '') . ' ' . ($p['periodo'] ?? ''),
                'data'      => !empty($p['data_reserva']) ? date('d/m/Y', strtotime($p['data_reserva'])) : '',
                'icon'      => 'bi-hourglass-split',
                'color'     => 'warning',
            ];
        }

        return $items;
    }

    private function itemsSosPendentes(): array {
        $sosModel = new SOSModel();
        $chamados = $sosModel->listarTodos('pendente');
        $items    = [];

        foreach ($chamados as $c) {
            $hora = !empty($c['data_hora']) ? date('d/m H:i', strtotime($c['data_hora'])) : '';
            $items[] = [
                'id'        => (int) $c['id'],
                'tipo'      => 'sos',
                'titulo'    => 'Chamado: ' . ($c['professor_nome'] ?? 'Professor'),
                'subtitulo' => ($c['laboratorio'] ?? '') . ' — ' . ($c['mensagem'] ?? ''),
                'data'      => $hora,
                'icon'      => 'bi-headset',
                'color'     => 'attention',
            ];
        }

        return $items;
    }
}
?>
