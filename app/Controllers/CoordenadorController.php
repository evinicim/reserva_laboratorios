<?php
namespace App\Controllers;

use App\Models\Agendamento as AgendamentoModel;
use App\Models\User;
use App\Services\MailService;
use App\Services\UsuarioService;
use PDO;
use PDOException;

class CoordenadorController extends BaseController {
    public function index() {
        // Pegar a instância PDO global (usada no código legado)
        $pdo = \App\Config\Database::getInstance()->getPDO();
        $this->requirePerfil('coordenador');

        // Variáveis de sessão mapeadas para o código legado
        $id_usuario_logado = $_SESSION['usuario_id'];
        $mensagem = ''; // Inicialização segura — evita Warning na view em requests GET
        if (!empty($_SESSION['coordenador_flash'])) {
            $mensagem = $_SESSION['coordenador_flash'];
            unset($_SESSION['coordenador_flash']);
        }
        $aba_redirect = '';
        if (!empty($_SESSION['coordenador_aba'])) {
            $aba_redirect = $_SESSION['coordenador_aba'];
            unset($_SESSION['coordenador_aba']);
        }

        // === LÓGICA LEGADA EXTRAÍDA ===
if (!isset($_SESSION['usuario_id']) || $_SESSION['perfil'] !== 'coordenador') {
    header("Location: index.php");
    exit;
}
// =================================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'mover_aula') {
    header('Content-Type: application/json');
    $id_aula = $_POST['id_aula'];
    $novo_dia = $_POST['novo_dia'];

    try {
        // Atualiza a aula para o novo dia que o Coordenador arrastou
        $stmt = $pdo->prepare("UPDATE quadro_aulas SET dia_semana = ? WHERE id = ?");
        $stmt->execute([$novo_dia, $id_aula]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit; // Para a execução do PHP aqui para não imprimir o HTML inteiro no fundo
}

// =================================================================================
// FUNÇÃO: O "DUPLO CHECK" DE CHOQUES (Avulsos vs Grade Fixa)
// =================================================================================
function verificaChoqueHorario($pdo, $id_lab, $data_reserva, $turno, $periodo, $id_ignorar = null, $ignorar_grade_fixa = false)
{
    $sql_ag = "SELECT a.periodo, u.nome AS professor FROM agendamentos a JOIN usuarios u ON a.id_professor = u.id WHERE a.id_laboratorio = ? AND a.data_reserva = ? AND a.turno = ? AND a.status = 'aprovado'";
    $params_ag = [$id_lab, $data_reserva, $turno];

    if ($id_ignorar) {
        $sql_ag .= " AND a.id != ?";
        $params_ag[] = $id_ignorar;
    }
    $stmt_ag = $pdo->prepare($sql_ag);
    $stmt_ag->execute($params_ag);
    $avulsos = $stmt_ag->fetchAll(PDO::FETCH_ASSOC);

    foreach ($avulsos as $row) {
        $p_banco = $row['periodo'];
        if ($periodo === '1º e 2º Horários' || $p_banco === '1º e 2º Horários' || $periodo === $p_banco) {
            return "Já existe reserva aprovada do(a) Prof. " . $row['professor'] . " neste laboratório, dia e horário.";
        }
    }

    if ($ignorar_grade_fixa) {
        return false;
    }

    $dias_map = [0 => 'Domingo', 1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta', 6 => 'Sábado'];
    $dia_semana = $dias_map[date('w', strtotime($data_reserva))];

    $id_quadro_ativo = false;
    try {
        $id_quadro_ativo = $pdo->query("SELECT id FROM quadros_horarios ORDER BY id DESC LIMIT 1")->fetchColumn();
    } catch (Exception $e) {
    }

    if ($id_quadro_ativo) {
        $stmt_qa = $pdo->prepare("SELECT qa.horario, u.nome AS professor FROM quadro_aulas qa LEFT JOIN usuarios u ON qa.id_professor = u.id WHERE qa.id_quadro = ? AND qa.id_laboratorio = ? AND qa.dia_semana = ? AND qa.turno = ?");
        $stmt_qa->execute([$id_quadro_ativo, $id_lab, $dia_semana, $turno]);
        $fixos = $stmt_qa->fetchAll(PDO::FETCH_ASSOC);

        foreach ($fixos as $fixo) {
            $h_fixo = $fixo['horario'];
            if ($periodo === '1º e 2º Horários' || $h_fixo === '1º e 2º Horários' || $periodo === $h_fixo) {
                $prof_grade = $fixo['professor'] ? 'Prof. ' . $fixo['professor'] : 'aula da grade';
                return "A grade fixa já reserva este lab em {$dia_semana} ({$turno}) — {$prof_grade}, horário {$h_fixo}.";
            }
        }
    }
    return false;
}

// --- UPLOAD DE FOTO ---
if (isset($_FILES['nova_foto']) && $_FILES['nova_foto']['error'] === UPLOAD_ERR_OK) {
    $extensao = strtolower(pathinfo($_FILES['nova_foto']['name'], PATHINFO_EXTENSION));
    if (in_array($extensao, ['jpg', 'jpeg', 'png', 'webp'])) {
        $diretorio = 'uploads/';
        if (!is_dir($diretorio))
            mkdir($diretorio, 0777, true);
        $destino = $diretorio . 'user_' . $id_usuario_logado . '_' . time() . '.' . $extensao;
        if (move_uploaded_file($_FILES['nova_foto']['tmp_name'], $destino)) {
            if (!empty($_SESSION['foto_perfil']) && file_exists($_SESSION['foto_perfil']) && strpos($_SESSION['foto_perfil'], 'padrao') === false) {
                unlink($_SESSION['foto_perfil']);
            }
            $pdo->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?")->execute([$destino, $id_usuario_logado]);
            $_SESSION['foto_perfil'] = $destino;
            $_SESSION['coordenador_flash'] = '<div class="alert alert-success alert-autohide rounded-0 border-start border-4 border-success mb-4"><i class="bi bi-check-circle me-2"></i>Foto atualizada com sucesso!</div>';
            $_SESSION['coordenador_aba'] = 'sessao-perfil';
            header('Location: painel_coordenador.php?aba=sessao-perfil');
            exit;
        }
    }
}

// --- GESTÃO DE USUÁRIOS (ADMIN) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioSvc = new UsuarioService();
    $mailSvc    = new MailService();
    $flashUsuarios = null;

    try {
        if (isset($_POST['admin_criar_usuario'])) {
            $senha = $_POST['nova_senha'] ?? '';
            $conf  = $_POST['confirmar_senha'] ?? '';
            if ($senha !== '' && $senha !== $conf) {
                throw new \InvalidArgumentException('As senhas não coincidem.');
            }
            $emailVerificado = isset($_POST['email_verificado']) ? 1 : 0;
            $enviarConfirmacao = isset($_POST['enviar_confirmacao_email']);
            $enviarLinkSenha = isset($_POST['enviar_link_senha']);
            $id = $usuarioSvc->criar(
                trim($_POST['nome_usuario'] ?? ''),
                trim($_POST['email_usuario'] ?? ''),
                $_POST['perfil_usuario'] ?? 'professor',
                $senha,
                $emailVerificado
            );
            $user = $usuarioSvc->buscarPorId($id);
            if (!$user) {
                throw new \RuntimeException('Usuário criado, mas não apareceu na lista. Atualize a página.');
            }

            $avisosEmail = [];
            $deveEnviarConfirmacao = $enviarConfirmacao || (!$emailVerificado && $mailSvc->isConfigured());
            if ($deveEnviarConfirmacao && $mailSvc->isConfigured()) {
                $token = $usuarioSvc->gerarTokenVerificacao($id);
                if ($mailSvc->enviarVerificacaoEmail($user['email'], $user['nome'], $token)) {
                    $avisosEmail[] = 'E-mail de confirmação enviado para <strong>' . htmlspecialchars($user['email']) . '</strong>.';
                } else {
                    $detail = $mailSvc->lastError() ?: 'erro desconhecido';
                    $avisosEmail[] = 'Usuário criado, mas o e-mail <strong>não</strong> foi enviado: ' . htmlspecialchars($detail) . '. Use «Reenviar» na lista.';
                }
            } elseif ($deveEnviarConfirmacao && !$mailSvc->isConfigured()) {
                $avisosEmail[] = 'Usuário criado como <strong>Pendente</strong>. Configure RESEND_API_KEY ou BREVO para enviar confirmação.';
            }

            if ($senha !== '' && isset($_POST['enviar_senha_email']) && $mailSvc->isConfigured()) {
                if ($mailSvc->enviarSenhaTemporaria($user['email'], $user['nome'], $senha)) {
                    $avisosEmail[] = 'Senha enviada por e-mail.';
                } else {
                    $avisosEmail[] = 'Senha <strong>não</strong> enviada por e-mail.';
                }
            } elseif ($senha === '' && $enviarLinkSenha && $mailSvc->isConfigured()) {
                $token = $usuarioSvc->gerarTokenRedefinicao($id);
                if ($mailSvc->enviarRedefinicaoSenha($user['email'], $user['nome'], $token)) {
                    $avisosEmail[] = 'Link para criar senha enviado por e-mail.';
                } else {
                    $detail = $mailSvc->lastError() ?: 'erro desconhecido';
                    $avisosEmail[] = 'Link de senha <strong>não</strong> enviado: ' . htmlspecialchars($detail) . '.';
                }
            }

            $classe = str_contains(implode(' ', $avisosEmail), 'não') ? 'alert-warning' : 'alert-success';
            $extra  = $avisosEmail !== [] ? ' ' . implode(' ', $avisosEmail) : '';
            $flashUsuarios = '<div class="alert ' . $classe . ' alert-autohide mb-4"><i class="bi bi-person-plus me-2"></i>Usuário <strong>' . htmlspecialchars($user['nome']) . '</strong> criado.' . $extra . '</div>';
        } elseif (isset($_POST['admin_editar_usuario'])) {
            $id = (int) ($_POST['id_usuario'] ?? 0);
            $usuarioSvc->atualizar(
                $id,
                trim($_POST['nome_usuario'] ?? ''),
                trim($_POST['email_usuario'] ?? ''),
                $_POST['perfil_usuario'] ?? 'professor',
                isset($_POST['email_verificado']) ? 1 : 0
            );
            if ($id === (int) $id_usuario_logado) {
                $_SESSION['nome']  = trim($_POST['nome_usuario']);
                $_SESSION['email'] = trim($_POST['email_usuario']);
            }
            $flashUsuarios = '<div class="alert alert-success alert-autohide mb-4"><i class="bi bi-check-circle me-2"></i>Usuário atualizado.</div>';
        } elseif (isset($_POST['admin_excluir_usuario'])) {
            $usuarioSvc->excluir((int) ($_POST['id_usuario'] ?? 0), (int) $id_usuario_logado);
            $flashUsuarios = '<div class="alert alert-info alert-autohide mb-4"><i class="bi bi-trash me-2"></i>Usuário removido.</div>';
        } elseif (isset($_POST['admin_redefinir_senha'])) {
            $id = (int) ($_POST['id_usuario'] ?? 0);
            $nova = $_POST['nova_senha'] ?? '';
            $conf = $_POST['confirmar_senha'] ?? '';
            if ($nova !== $conf) {
                throw new \InvalidArgumentException('As senhas não coincidem.');
            }
            $usuarioSvc->definirSenha($id, $nova);
            $user = $usuarioSvc->buscarPorId($id);
            if ($user && isset($_POST['enviar_senha_email']) && $mailSvc->isConfigured()) {
                $mailSvc->enviarSenhaTemporaria($user['email'], $user['nome'], $nova);
                $flashUsuarios = '<div class="alert alert-success alert-autohide mb-4"><i class="bi bi-envelope-check me-2"></i>Senha definida e enviada por e-mail.</div>';
            } else {
                $flashUsuarios = '<div class="alert alert-success alert-autohide mb-4"><i class="bi bi-key me-2"></i>Senha redefinida.</div>';
            }
        } elseif (isset($_POST['admin_enviar_reset'])) {
            $id = (int) ($_POST['id_usuario'] ?? 0);
            $user = $usuarioSvc->buscarPorId($id);
            if (!$user) {
                throw new \InvalidArgumentException('Usuário não encontrado.');
            }
            if (!$mailSvc->isConfigured()) {
                throw new \InvalidArgumentException('SMTP não configurado. Defina MAIL_HOST, MAIL_USERNAME e MAIL_PASSWORD no .env.');
            }
            $token = $usuarioSvc->gerarTokenRedefinicao($id);
            if (!$mailSvc->enviarRedefinicaoSenha($user['email'], $user['nome'], $token)) {
                $detail = $mailSvc->lastError() ?: 'Erro desconhecido';
                throw new \RuntimeException('Falha ao enviar e-mail. ' . $detail);
            }
            $flashUsuarios = '<div class="alert alert-primary alert-autohide mb-4"><i class="bi bi-envelope me-2"></i>Link de redefinição enviado para ' . htmlspecialchars($user['email']) . '. Confira também Spam/Promoções.</div>';
        } elseif (isset($_POST['admin_enviar_verificacao'])) {
            $id = (int) ($_POST['id_usuario'] ?? 0);
            $user = $usuarioSvc->buscarPorId($id);
            if (!$user) {
                throw new \InvalidArgumentException('Usuário não encontrado.');
            }
            if (!$mailSvc->isConfigured()) {
                throw new \InvalidArgumentException('SMTP não configurado.');
            }
            $token = $usuarioSvc->gerarTokenVerificacao($id);
            if (!$mailSvc->enviarVerificacaoEmail($user['email'], $user['nome'], $token)) {
                $detail = $mailSvc->lastError() ?: 'Erro desconhecido';
                throw new \RuntimeException('Falha ao enviar e-mail de verificação. ' . $detail);
            }
            $flashUsuarios = '<div class="alert alert-success alert-autohide mb-4"><i class="bi bi-envelope-check me-2"></i>E-mail de confirmação enviado. Peça ao usuário verificar <strong>Spam/Promoções</strong> se não aparecer em 2 min.</div>';
        }
    } catch (\Throwable $e) {
        $flashUsuarios = '<div class="alert alert-danger alert-autohide mb-4"><i class="bi bi-exclamation-triangle me-2"></i>' . htmlspecialchars($e->getMessage()) . '</div>';
    }

    if ($flashUsuarios !== null) {
        $_SESSION['coordenador_flash'] = $flashUsuarios;
        $_SESSION['coordenador_aba'] = 'sessao-usuarios';
        header('Location: painel_coordenador.php?aba=sessao-usuarios');
        exit;
    }
}

// --- QUADRO DE HORÁRIOS (CRIAR/EXCLUIR) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar_quadro'])) {
    try {
        $pdo->prepare("INSERT INTO quadros_horarios (nome, periodo_letivo) VALUES (?, ?)")->execute([trim($_POST['nome_quadro']), trim($_POST['periodo_letivo'])]);
        $mensagem = '<div class="alert alert-success alert-autohide mb-4">Cenário de Quadro Horário criado!</div>';
    } catch (PDOException $e) {
        $mensagem = '<div class="alert alert-danger mb-4"><strong>Erro no Banco de Dados:</strong> ' . $e->getMessage() . '</div>';
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['excluir_quadro'])) {
    $pdo->prepare("DELETE FROM quadros_horarios WHERE id = ?")->execute([$_POST['id_quadro']]);
    $mensagem = '<div class="alert alert-info alert-autohide mb-4">Quadro Horário excluído com todas as suas aulas.</div>';
}
// -- Bloco Novo : Duplicar Quadro Inteiro --
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['duplicar_quadro'])) {
    $id_origem = $_POST['id_quadro_origem'];
    $novo_nome = trim($_POST['novo_nome_quadro']);
    $novo_periodo = trim($_POST['novo_periodo_letivo']);

    try {
        $pdo->beginTransaction(); // Inicia a transação segura

        // 1. Cria o Novo Quadro Vazio
        $stmt = $pdo->prepare("INSERT INTO quadros_horarios (nome, periodo_letivo) VALUES (?, ?)");
        $stmt->execute([$novo_nome, $novo_periodo]);
        $novo_id = $pdo->lastInsertId(); // Pega o ID do quadro que acabou de nascer

        // 2. Busca todas as aulas do quadro antigo
        $stmt_aulas = $pdo->prepare("SELECT * FROM quadro_aulas WHERE id_quadro = ?");
        $stmt_aulas->execute([$id_origem]);
        $aulas_antigas = $stmt_aulas->fetchAll(PDO::FETCH_ASSOC);

        // 3. Copia aula por aula para o novo quadro
        if (count($aulas_antigas) > 0) {
            $sql_insert = "INSERT INTO quadro_aulas (id_quadro, turno, dia_semana, curso, semestre, id_disciplina, modalidade, numero_alunos, id_professor, id_laboratorio, horario, bloco, andar, sala, carga_horaria_total, horas_laboratorio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);

            foreach ($aulas_antigas as $aula) {
                $stmt_insert->execute([
                    $novo_id,
                    $aula['turno'],
                    $aula['dia_semana'],
                    $aula['curso'],
                    $aula['semestre'],
                    $aula['id_disciplina'],
                    $aula['modalidade'],
                    $aula['numero_alunos'],
                    $aula['id_professor'],
                    $aula['id_laboratorio'],
                    $aula['horario'],
                    $aula['bloco'],
                    $aula['andar'],
                    $aula['sala'],
                    $aula['carga_horaria_total'],
                    $aula['horas_laboratorio']
                ]);
            }
        }

        $pdo->commit(); // Confirma a clonagem em massa
        $mensagem = '<div class="alert alert-success alert-autohide mb-4">Cenário duplicado com sucesso! Aulas copiadas perfeitamente.</div>';
    } catch (PDOException $e) {
        $pdo->rollBack(); // Desfaz se der erro
        $mensagem = '<div class="alert alert-danger mb-4"><strong>Erro ao duplicar:</strong> ' . $e->getMessage() . '</div>';
    }
}

// --- QUADRO DE HORÁRIOS (ADICIONAR/EDITAR AULA) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['salvar_aula_quadro']) || isset($_POST['editar_aula_quadro']))) {
    if (empty($_POST['id_quadro_ativo'])) {
        $mensagem = '<div class="alert alert-danger alert-autohide"><strong>Erro:</strong> Selecione um Cenário antes de alocar aulas.</div>';
    } else {
        $id_q = $_POST['id_quadro_ativo'];
        $turno = $_POST['turno_aula'];
        $dia = $_POST['dia_semana'];
        $curso = $_POST['curso_aula'];
        $semestre = $_POST['semestre_aula'];
        $disc = $_POST['id_disciplina_aula'];
        $modalidade = $_POST['modalidade'];
        $num_alunos = (int) $_POST['numero_alunos'];

        // --- A BALA DE PRATA DO EAD ---
        if ($modalidade === 'EAD') {
            $prof = null;
            $lab = null;
            $sala = null;
            $bloco = null;
            $andar = null;
        } else {
            $prof = empty($_POST['id_professor_aula']) ? null : $_POST['id_professor_aula'];
            $lab = empty($_POST['id_laboratorio_aula']) ? null : $_POST['id_laboratorio_aula'];
            $bloco = empty($_POST['bloco_aula']) ? null : $_POST['bloco_aula'];
            $andar = empty($_POST['andar_aula']) ? null : $_POST['andar_aula'];
            $sala = empty($_POST['sala_aula']) ? null : $_POST['sala_aula'];
        }

        $horario = $_POST['horario_aula'];

        $carga_horaria_total = isset($_POST['carga_horaria_total']) ? (int) $_POST['carga_horaria_total'] : 2;
        $horas_laboratorio = isset($_POST['horas_laboratorio']) ? (int) $_POST['horas_laboratorio'] : 0;

        if ($lab) {
            if ($horas_laboratorio <= 0) {
                $horas_laboratorio = empty($sala) ? $carga_horaria_total : min($carga_horaria_total, 2);
            }
            if ($horas_laboratorio > $carga_horaria_total) {
                $horas_laboratorio = $carga_horaria_total;
            }
        } elseif ($horas_laboratorio > 0) {
            $horas_laboratorio = 0;
        }

        $editando = isset($_POST['editar_aula_quadro']);
        $id_aula_q = $editando ? $_POST['id_aula_q'] : null;
        $erro_conflito = false;
        $msg_erro = "";

        if ($lab) {
            $cap_lab = $pdo->prepare("SELECT capacidade FROM laboratorios WHERE id = ?");
            $cap_lab->execute([$lab]);
            $limite = $cap_lab->fetchColumn();
            if ($num_alunos > $limite) {
                $erro_conflito = true;
                $msg_erro = "Alunos ($num_alunos) excede capacidade do lab ($limite).";
            }
        }

        if (!$erro_conflito) {
            $sql_check = "SELECT id_professor, id_laboratorio, bloco, andar, sala, horario, modalidade FROM quadro_aulas WHERE id_quadro = ? AND dia_semana = ? AND turno = ?";
            $params_check = [$id_q, $dia, $turno];
            if ($editando) {
                $sql_check .= " AND id != ?";
                $params_check[] = $id_aula_q;
            }
            $check = $pdo->prepare($sql_check);
            $check->execute($params_check);
            $aulas_existentes = $check->fetchAll(PDO::FETCH_ASSOC);

            foreach ($aulas_existentes as $ae) {
                if ($modalidade === 'EAD') {
                    continue;
                }

                if ($horario === '1º e 2º Horários' || $ae['horario'] === '1º e 2º Horários' || $horario === $ae['horario']) {
                    if ($prof !== null && $ae['id_professor'] == $prof) {
                        $erro_conflito = true;
                        $msg_erro = "Este professor já tem aula neste dia, turno e horário na grade.";
                        break;
                    }
                    if ($lab && $ae['id_laboratorio'] == $lab) {
                        $erro_conflito = true;
                        $msg_erro = "Choque de Laboratório.";
                        break;
                    }
                    if ($sala && $ae['sala'] == $sala && $ae['bloco'] == $bloco && $ae['andar'] == $andar) {
                        $erro_conflito = true;
                        $msg_erro = "Choque de Sala.";
                        break;
                    }
                }
            }
        }

        if ($erro_conflito) {
            $mensagem = "<div class='alert alert-danger alert-autohide mb-4'><strong>Bloqueado:</strong> $msg_erro</div>";
        } else {
            try {
                if ($editando) {
                    $pdo->prepare("UPDATE quadro_aulas SET turno=?, dia_semana=?, curso=?, semestre=?, id_disciplina=?, modalidade=?, numero_alunos=?, id_professor=?, id_laboratorio=?, horario=?, bloco=?, andar=?, sala=?, carga_horaria_total=?, horas_laboratorio=? WHERE id=?")
                        ->execute([$turno, $dia, $curso, $semestre, $disc, $modalidade, $num_alunos, $prof, $lab, $horario, $bloco, $andar, $sala, $carga_horaria_total, $horas_laboratorio, $id_aula_q]);
                } else {
                    $pdo->prepare("INSERT INTO quadro_aulas (id_quadro, turno, dia_semana, curso, semestre, id_disciplina, modalidade, numero_alunos, id_professor, id_laboratorio, horario, bloco, andar, sala, carga_horaria_total, horas_laboratorio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                        ->execute([$id_q, $turno, $dia, $curso, $semestre, $disc, $modalidade, $num_alunos, $prof, $lab, $horario, $bloco, $andar, $sala, $carga_horaria_total, $horas_laboratorio]);
                }
                $mensagem = '<div class="alert alert-success alert-autohide mb-4">Grade atualizada com sucesso!</div>';
            } catch (PDOException $e) {
                if ($editando) {
                    $pdo->prepare("UPDATE quadro_aulas SET turno=?, dia_semana=?, curso=?, semestre=?, id_disciplina=?, modalidade=?, numero_alunos=?, id_professor=?, id_laboratorio=?, horario=?, bloco=?, andar=?, sala=? WHERE id=?")
                        ->execute([$turno, $dia, $curso, $semestre, $disc, $modalidade, $num_alunos, $prof, $lab, $horario, $bloco, $andar, $sala, $id_aula_q]);
                } else {
                    $pdo->prepare("INSERT INTO quadro_aulas (id_quadro, turno, dia_semana, curso, semestre, id_disciplina, modalidade, numero_alunos, id_professor, id_laboratorio, horario, bloco, andar, sala) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                        ->execute([$id_q, $turno, $dia, $curso, $semestre, $disc, $modalidade, $num_alunos, $prof, $lab, $horario, $bloco, $andar, $sala]);
                }
                $mensagem = '<div class="alert alert-warning alert-autohide mb-4">Aula salva, mas os campos de carga horária foram ignorados (banco desatualizado).</div>';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['excluir_aula_quadro'])) {
    $pdo->prepare("DELETE FROM quadro_aulas WHERE id = ?")->execute([$_POST['id_aula_q']]);
    $mensagem = '<div class="alert alert-info alert-autohide mb-4">Aula removida do quadro.</div>';
}

// --- APROVAR/REJEITAR RESERVAS PENDENTES ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao_reserva'])) {
    $id_agendamento = (int) ($_POST['id_agendamento'] ?? 0);
    $flashMsg = '';
    $ajaxStatus = null;
    $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';

    if ($id_agendamento <= 0) {
        $flashMsg = "<div class='alert alert-danger alert-autohide mb-4'>Solicitação inválida.</div>";
    } elseif ($_POST['acao_reserva'] == 'aprovar') {
        $stmt_ag = $pdo->prepare("SELECT id_laboratorio, data_reserva, turno, periodo FROM agendamentos WHERE id = ?");
        $stmt_ag->execute([$id_agendamento]);
        $ag = $stmt_ag->fetch(PDO::FETCH_ASSOC);
        if (!$ag) {
            $flashMsg = "<div class='alert alert-danger alert-autohide mb-4'>Solicitação não encontrada.</div>";
        } else {
            // Coordenador pode aprovar mesmo com grade fixa; bloqueia só reserva avulsa de outro professor.
            $conflito = verificaChoqueHorario($pdo, $ag['id_laboratorio'], $ag['data_reserva'], $ag['turno'], $ag['periodo'], $id_agendamento, true);
            if ($conflito) {
                $flashMsg = "<div class='alert alert-warning alert-autohide mb-4'><strong>Aprovação bloqueada:</strong> $conflito</div>";
            } else {
                $pdo->prepare("UPDATE agendamentos SET status = 'aprovado' WHERE id = ?")->execute([$id_agendamento]);
                $flashMsg = "<div class='alert alert-success alert-autohide mb-4'>Reserva aprovada com sucesso!</div>";
                $ajaxStatus = 'aprovado';
            }
        }
    } else {
        $pdo->prepare("UPDATE agendamentos SET status = 'rejeitado' WHERE id = ?")->execute([$id_agendamento]);
        $flashMsg = "<div class='alert alert-danger alert-autohide mb-4'>Reserva rejeitada.</div>";
        $ajaxStatus = 'rejeitado';
    }

    $qtd_pendentes_agora = (int) $pdo->query("SELECT COUNT(*) FROM agendamentos WHERE status = 'pendente'")->fetchColumn();

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $ajaxStatus !== null,
            'status' => $ajaxStatus,
            'id' => $id_agendamento,
            'qtd_pendentes' => $qtd_pendentes_agora,
            'message_html' => $flashMsg,
            'message' => strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $flashMsg)),
        ]);
        exit;
    }

    $_SESSION['coordenador_flash'] = $flashMsg;
    header('Location: painel_coordenador.php#sessao-historico-geral');
    exit;
}

// --- AGENDAR LAB AVULSO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['agendar_lab_coord'])) {
        $id_lab = $_POST['id_laboratorio'];
        $data_res = $_POST['data_reserva'];
        $turno_req = $_POST['turno'];
        $periodo_req = $_POST['periodo'];
        $conflito = verificaChoqueHorario($pdo, $id_lab, $data_res, $turno_req, $periodo_req);
        if ($conflito) {
            $mensagem = '<div class="alert alert-warning alert-autohide mb-4"><strong>Bloqueado:</strong> ' . $conflito . '</div>';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO agendamentos (id_laboratorio, id_professor, id_disciplina, data_reserva, turno, periodo, status) VALUES (?, ?, ?, ?, ?, ?, 'aprovado')");
                $stmt->execute([$id_lab, $_POST['id_professor'], $_POST['id_disciplina'], $data_res, $turno_req, $periodo_req]);
                $mensagem = '<div class="alert alert-success alert-autohide mb-4">Agendamento criado!</div>';
            } catch (PDOException $e) {
                $mensagem = '<div class="alert alert-danger alert-autohide mb-4">Erro ao agendar.</div>';
            }
        }
    } elseif (isset($_POST['editar_agendamento_coord'])) {
        $id_ag = $_POST['id_agendamento'];
        $id_lab = $_POST['id_laboratorio'];
        $data_res = $_POST['data_reserva'];
        $turno_req = $_POST['turno'];
        $periodo_req = $_POST['periodo'];
        $conflito = verificaChoqueHorario($pdo, $id_lab, $data_res, $turno_req, $periodo_req, $id_ag);
        if ($conflito) {
            $mensagem = '<div class="alert alert-warning alert-autohide mb-4"><strong>Bloqueado:</strong> ' . $conflito . '</div>';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE agendamentos SET id_laboratorio=?, id_professor=?, id_disciplina=?, data_reserva=?, turno=?, periodo=? WHERE id=?");
                $stmt->execute([$id_lab, $_POST['id_professor'], $_POST['id_disciplina'], $data_res, $turno_req, $periodo_req, $id_ag]);
                $mensagem = '<div class="alert alert-primary alert-autohide mb-4">Agendamento atualizado!</div>';
            } catch (PDOException $e) {
                $mensagem = '<div class="alert alert-danger alert-autohide mb-4">Erro ao atualizar.</div>';
            }
        }
    } elseif (isset($_POST['cancelar_agendamento'])) {
        try {
            $pdo->prepare("DELETE FROM agendamentos WHERE id = ?")->execute([$_POST['id_agendamento']]);
            $mensagem = '<div class="alert alert-warning alert-autohide mb-4">Agendamento cancelado.</div>';
        } catch (PDOException $e) {
            $mensagem = '<div class="alert alert-danger alert-autohide mb-4">Erro ao cancelar.</div>';
        }
    }
}

// --- CADASTROS BASE E INFRA ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['salvar_lab'])) {
        $pdo->prepare("INSERT INTO laboratorios (nome, capacidade, localizacao, andar) VALUES (?, ?, ?, ?)")->execute([trim($_POST['nome_lab']), (int) $_POST['capacidade_lab'], trim($_POST['localizacao_lab']), trim($_POST['andar_lab'])]);
    } elseif (isset($_POST['editar_lab'])) {
        $pdo->prepare("UPDATE laboratorios SET nome = ?, capacidade = ?, localizacao = ?, andar = ? WHERE id = ?")->execute([trim($_POST['nome_lab']), (int) $_POST['capacidade_lab'], trim($_POST['localizacao_lab']), trim($_POST['andar_lab']), $_POST['id_lab']]);
    } elseif (isset($_POST['excluir_lab'])) {
        $pdo->prepare("DELETE FROM laboratorios WHERE id = ?")->execute([$_POST['id_lab']]);
    }

    if (isset($_POST['salvar_disciplina'])) {
        $pdo->prepare("INSERT INTO disciplinas (nome) VALUES (?)")->execute([trim($_POST['nome_disciplina'])]);
    } elseif (isset($_POST['editar_disciplina'])) {
        $pdo->prepare("UPDATE disciplinas SET nome = ? WHERE id = ?")->execute([trim($_POST['nome_disciplina']), $_POST['id_disciplina']]);
    } elseif (isset($_POST['excluir_disciplina'])) {
        $pdo->prepare("DELETE FROM disciplinas WHERE id = ?")->execute([$_POST['id_disciplina']]);
    }

    if (isset($_POST['salvar_curso'])) {
        $pdo->prepare("INSERT INTO cursos (nome) VALUES (?)")->execute([trim($_POST['nome_curso'])]);
    } elseif (isset($_POST['editar_curso'])) {
        $pdo->prepare("UPDATE cursos SET nome = ? WHERE id = ?")->execute([trim($_POST['nome_curso']), $_POST['id_curso']]);
    } elseif (isset($_POST['excluir_curso'])) {
        $pdo->prepare("DELETE FROM cursos WHERE id = ?")->execute([$_POST['id_curso']]);
    }

    if (isset($_POST['salvar_semestre'])) {
        $pdo->prepare("INSERT INTO semestres (nome) VALUES (?)")->execute([trim($_POST['nome_semestre'])]);
    } elseif (isset($_POST['editar_semestre'])) {
        $pdo->prepare("UPDATE semestres SET nome = ? WHERE id = ?")->execute([trim($_POST['nome_semestre']), $_POST['id_semestre']]);
    } elseif (isset($_POST['excluir_semestre'])) {
        $pdo->prepare("DELETE FROM semestres WHERE id = ?")->execute([$_POST['id_semestre']]);
    }

    if (isset($_POST['salvar_bloco'])) {
        $pdo->prepare("INSERT INTO blocos (nome) VALUES (?)")->execute([trim($_POST['nome_bloco'])]);
    }
    if (isset($_POST['editar_bloco'])) {
        $pdo->prepare("UPDATE blocos SET nome = ? WHERE id = ?")->execute([trim($_POST['nome_bloco']), $_POST['id_bloco']]);
    }
    if (isset($_POST['excluir_bloco'])) {
        $pdo->prepare("DELETE FROM blocos WHERE id = ?")->execute([$_POST['id_bloco']]);
    }

    if (isset($_POST['salvar_andar'])) {
        $pdo->prepare("INSERT INTO andares (nome) VALUES (?)")->execute([trim($_POST['nome_andar'])]);
    }
    if (isset($_POST['editar_andar'])) {
        $pdo->prepare("UPDATE andares SET nome = ? WHERE id = ?")->execute([trim($_POST['nome_andar']), $_POST['id_andar']]);
    }
    if (isset($_POST['excluir_andar'])) {
        $pdo->prepare("DELETE FROM andares WHERE id = ?")->execute([$_POST['id_andar']]);
    }

    if (isset($_POST['salvar_sala'])) {
        $pdo->prepare("INSERT INTO salas (nome) VALUES (?)")->execute([trim($_POST['nome_sala'])]);
    }
    if (isset($_POST['editar_sala'])) {
        $pdo->prepare("UPDATE salas SET nome = ? WHERE id = ?")->execute([trim($_POST['nome_sala']), $_POST['id_sala']]);
    }
    if (isset($_POST['excluir_sala'])) {
        $pdo->prepare("DELETE FROM salas WHERE id = ?")->execute([$_POST['id_sala']]);
    }
}

// --- ENSALAMENTO NORMAL ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $flashEnsalamento = null;
    try {
        if (isset($_POST['salvar_ensalamento'])) {
            $bloco = trim($_POST['bloco'] ?? '');
            $andar = trim($_POST['andar'] ?? '');
            $sala = trim($_POST['sala'] ?? '');
            $turno = trim($_POST['turno'] ?? '');
            $categoria = trim($_POST['categoria'] ?? '');

            $stmt_check = $pdo->prepare("SELECT u.nome as prof_existente FROM ensalamento e JOIN usuarios u ON e.id_professor = u.id WHERE e.bloco = ? AND e.andar = ? AND e.sala = ? AND e.turno = ?");
            $stmt_check->execute([$bloco, $andar, $sala, $turno]);
            if ($stmt_check->rowCount() > 0) {
                $conflito = $stmt_check->fetch(PDO::FETCH_ASSOC);
                $flashEnsalamento = '<div class="alert alert-warning alert-autohide mb-4"><strong>Choque de Sala!</strong> Local em uso no turno ' . htmlspecialchars($turno) . ' por Prof. ' . htmlspecialchars($conflito['prof_existente']) . '.</div>';
            } else {
                $pdo->prepare("INSERT INTO ensalamento (id_professor, id_disciplina, curso, bloco, andar, sala, categoria, turno) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute([
                        (int) $_POST['id_professor'],
                        (int) $_POST['id_disciplina'],
                        trim($_POST['curso'] ?? ''),
                        $bloco,
                        $andar,
                        $sala,
                        $categoria !== '' ? $categoria : null,
                        $turno,
                    ]);
                $flashEnsalamento = '<div class="alert alert-success alert-autohide mb-4"><i class="bi bi-check-circle-fill me-2"></i><strong>Sucesso!</strong> Ensalamento registrado.</div>';
            }
        } elseif (isset($_POST['editar_ensalamento'])) {
            $idEns = (int) ($_POST['id_ensalamento'] ?? 0);
            $bloco = trim($_POST['bloco'] ?? '');
            $andar = trim($_POST['andar'] ?? '');
            $sala = trim($_POST['sala'] ?? '');
            $turno = trim($_POST['turno'] ?? '');
            $categoria = trim($_POST['categoria'] ?? '');

            $stmt_check = $pdo->prepare("SELECT u.nome as prof_existente FROM ensalamento e JOIN usuarios u ON e.id_professor = u.id WHERE e.bloco = ? AND e.andar = ? AND e.sala = ? AND e.turno = ? AND e.id != ?");
            $stmt_check->execute([$bloco, $andar, $sala, $turno, $idEns]);
            if ($stmt_check->rowCount() > 0) {
                $flashEnsalamento = '<div class="alert alert-warning alert-autohide mb-4"><strong>Choque na Edição!</strong> Sala já ocupada neste turno.</div>';
            } else {
                $pdo->prepare("UPDATE ensalamento SET id_professor=?, id_disciplina=?, curso=?, bloco=?, andar=?, sala=?, categoria=?, turno=? WHERE id=?")
                    ->execute([
                        (int) $_POST['id_professor'],
                        (int) $_POST['id_disciplina'],
                        trim($_POST['curso'] ?? ''),
                        $bloco,
                        $andar,
                        $sala,
                        $categoria !== '' ? $categoria : null,
                        $turno,
                        $idEns,
                    ]);
                $flashEnsalamento = '<div class="alert alert-primary alert-autohide mb-4">Ensalamento atualizado!</div>';
            }
        } elseif (isset($_POST['excluir_ensalamento'])) {
            $pdo->prepare("DELETE FROM ensalamento WHERE id = ?")->execute([(int) ($_POST['id_ensalamento'] ?? 0)]);
            $flashEnsalamento = '<div class="alert alert-info alert-autohide mb-4">Ensalamento removido.</div>';
        }
    } catch (PDOException $e) {
        $flashEnsalamento = '<div class="alert alert-danger alert-autohide mb-4"><strong>Erro ao salvar ensalamento:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
    }

    if ($flashEnsalamento !== null) {
        $_SESSION['coordenador_flash'] = $flashEnsalamento;
        $_SESSION['coordenador_aba'] = 'sessao-ensalamento';
        header('Location: painel_coordenador.php?aba=sessao-ensalamento');
        exit;
    }
}

$foto_atual = app_foto_perfil_usuario($pdo, (int) $id_usuario_logado);

$aba_ativa = $aba_redirect !== '' ? $aba_redirect : trim((string) ($_GET['aba'] ?? 'sessao-calendario-geral'));
$painel_rapido = app_is_fast_panel();
$carregar_relatorios = !$painel_rapido || $aba_ativa === 'sessao-relatorios';
$carregar_historico  = !$painel_rapido || $aba_ativa === 'sessao-historico-geral';

// --- BUSCAS DE DADOS GERAIS ---
$reservas_pendentes = $pdo->query("SELECT a.*, l.nome as laboratorio, u.nome as professor, d.nome as disciplina FROM agendamentos a JOIN laboratorios l ON a.id_laboratorio = l.id JOIN usuarios u ON a.id_professor = u.id JOIN disciplinas d ON a.id_disciplina = d.id WHERE a.status = 'pendente' ORDER BY a.data_reserva ASC")->fetchAll(PDO::FETCH_ASSOC);
$qtd_pendentes = count($reservas_pendentes);

$where_aprovados = "a.status = 'aprovado' AND " . app_sql_date_between('a.data_reserva', 90, 365);
$agendamentos_aprovados = $pdo->query(
    "SELECT a.*, l.nome as laboratorio, u.nome as professor, d.nome as disciplina, a.id_professor, a.id_laboratorio, a.id_disciplina
     FROM agendamentos a
     JOIN laboratorios l ON a.id_laboratorio = l.id
     JOIN usuarios u ON a.id_professor = u.id
     JOIN disciplinas d ON a.id_disciplina = d.id
     WHERE {$where_aprovados}
     ORDER BY a.data_reserva DESC"
)->fetchAll(PDO::FETCH_ASSOC);

if ($carregar_historico) {
    $historico_completo = $pdo->query(
        "SELECT a.*, l.nome as laboratorio, u.nome as professor, d.nome as disciplina
         FROM agendamentos a
         JOIN laboratorios l ON a.id_laboratorio = l.id
         JOIN usuarios u ON a.id_professor = u.id
         JOIN disciplinas d ON a.id_disciplina = d.id
         ORDER BY a.data_reserva DESC, a.id DESC
         LIMIT 300"
    )->fetchAll(PDO::FETCH_ASSOC);
} else {
    $historico_completo = [];
}

$professores = $pdo->query("SELECT id, nome FROM usuarios WHERE perfil = 'professor' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$laboratorios_cadastrados = $pdo->query("SELECT * FROM laboratorios ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$disciplinas = $pdo->query("SELECT * FROM disciplinas ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

$cursos_cadastrados = [];
$semestres_cadastrados = [];
$blocos_cadastrados = [];
$andares_cadastrados = [];
$salas_cadastradas = [];
try {
    $cursos_cadastrados = $pdo->query("SELECT * FROM cursos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    $semestres_cadastrados = $pdo->query("SELECT * FROM semestres ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    $blocos_cadastrados = $pdo->query("SELECT * FROM blocos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    $andares_cadastrados = $pdo->query("SELECT * FROM andares ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    $salas_cadastradas = $pdo->query("SELECT * FROM salas ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

$lista_ensalamentos = [];
try {
    $lista_ensalamentos = $pdo->query("SELECT e.*, u.nome as professor, d.nome as disciplina FROM ensalamento e JOIN usuarios u ON e.id_professor = u.id JOIN disciplinas d ON e.id_disciplina = d.id ORDER BY e.curso ASC, e.turno ASC, e.bloco ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}

$lista_quadros = [];
try {
    $lista_quadros = $pdo->query("SELECT * FROM quadros_horarios ORDER BY data_criacao DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}
$quadro_selecionado = isset($_GET['q_id']) ? $_GET['q_id'] : (count($lista_quadros) > 0 ? $lista_quadros[0]['id'] : null);

$aulas_do_quadro = [];
$dias_semana = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
$todas_aulas = [];

if ($quadro_selecionado) {
    try {
        $stmt_qa = $pdo->prepare("SELECT qa.*, p.nome as prof_nome, d.nome as disc_nome, l.nome as lab_nome, l.localizacao as lab_local, l.andar as lab_andar FROM quadro_aulas qa LEFT JOIN usuarios p ON qa.id_professor = p.id JOIN disciplinas d ON qa.id_disciplina = d.id LEFT JOIN laboratorios l ON qa.id_laboratorio = l.id WHERE qa.id_quadro = ? ORDER BY " . app_sql_order_turno('qa.turno') . ", qa.horario ASC, qa.curso ASC, qa.semestre ASC");
        $stmt_qa->execute([$quadro_selecionado]);
        $todas_aulas = $stmt_qa->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
    }
    foreach ($dias_semana as $dia) {
        $aulas_do_quadro[$dia] = [];
    }
    foreach ($todas_aulas as $a) {
        $aulas_do_quadro[$a['dia_semana']][] = $a;
    }
}

// =================================================================================
// LÓGICA MASTER DOS RELATÓRIOS BI E GRÁFICOS
// =================================================================================
$relatorio_professores = [];
$relatorio_labs = [];
$erro_banco_relatorio = false;
$grafico_prof_nomes = [];
$grafico_prof_horas = [];
$grafico_prof_lab = [];
$grafico_prof_sala = [];
$grafico_lab_nomes = [];
$grafico_lab_uso = [];
$grafico_lab_ocioso = [];
$uso_global = 0;
$lab_mais_usado = ['nome' => '-', 'horas' => 0];
$lab_mais_ocioso = ['nome' => '-', 'horas' => 0];
$taxa_ocupacao_global = 0;
$taxa_ociosidade_global = 0;
$relatorio_cursos = [];
$grafico_curso_nomes = [];
$grafico_curso_horas = [];
$capacidade_max_semanal = 60;
$total_aulas_grade = count($todas_aulas);
$total_reservas_avulsas = 0;
$total_reservas_avulsas_horas = 0;
$nome_quadro_selecionado = '';
if ($quadro_selecionado) {
    foreach ($lista_quadros as $q) {
        if ((int) $q['id'] === (int) $quadro_selecionado) {
            $nome_quadro_selecionado = $q['nome'];
            break;
        }
    }
}

if ($quadro_selecionado && $carregar_relatorios) {
    try {
        $sql_prof = "SELECT p.nome as professor, SUM(CASE WHEN qa.dia_semana = 'Segunda' THEN qa.carga_horaria_total ELSE 0 END) as seg_t, SUM(CASE WHEN qa.dia_semana = 'Segunda' THEN qa.horas_laboratorio ELSE 0 END) as seg_l, SUM(CASE WHEN qa.dia_semana = 'Terça' THEN qa.carga_horaria_total ELSE 0 END) as ter_t, SUM(CASE WHEN qa.dia_semana = 'Terça' THEN qa.horas_laboratorio ELSE 0 END) as ter_l, SUM(CASE WHEN qa.dia_semana = 'Quarta' THEN qa.carga_horaria_total ELSE 0 END) as qua_t, SUM(CASE WHEN qa.dia_semana = 'Quarta' THEN qa.horas_laboratorio ELSE 0 END) as qua_l, SUM(CASE WHEN qa.dia_semana = 'Quinta' THEN qa.carga_horaria_total ELSE 0 END) as qui_t, SUM(CASE WHEN qa.dia_semana = 'Quinta' THEN qa.horas_laboratorio ELSE 0 END) as qui_l, SUM(CASE WHEN qa.dia_semana = 'Sexta' THEN qa.carga_horaria_total ELSE 0 END) as sex_t, SUM(CASE WHEN qa.dia_semana = 'Sexta' THEN qa.horas_laboratorio ELSE 0 END) as sex_l, SUM(CASE WHEN qa.dia_semana = 'Sábado' THEN qa.carga_horaria_total ELSE 0 END) as sab_t, SUM(CASE WHEN qa.dia_semana = 'Sábado' THEN qa.horas_laboratorio ELSE 0 END) as sab_l, SUM(qa.carga_horaria_total) as total, SUM(qa.horas_laboratorio) as total_l FROM quadro_aulas qa JOIN usuarios p ON qa.id_professor = p.id WHERE qa.id_quadro = ? GROUP BY p.id, p.nome ORDER BY total DESC, p.nome ASC";
        $stmt_prof = $pdo->prepare($sql_prof);
        $stmt_prof->execute([$quadro_selecionado]);
        $relatorio_professores = $stmt_prof->fetchAll(PDO::FETCH_ASSOC);

        $sql_lab = "SELECT l.nome as laboratorio, COALESCE(SUM(CASE WHEN qa.dia_semana = 'Segunda' THEN qa.horas_laboratorio ELSE 0 END), 0) as seg, COALESCE(SUM(CASE WHEN qa.dia_semana = 'Terça' THEN qa.horas_laboratorio ELSE 0 END), 0) as ter, COALESCE(SUM(CASE WHEN qa.dia_semana = 'Quarta' THEN qa.horas_laboratorio ELSE 0 END), 0) as qua, COALESCE(SUM(CASE WHEN qa.dia_semana = 'Quinta' THEN qa.horas_laboratorio ELSE 0 END), 0) as qui, COALESCE(SUM(CASE WHEN qa.dia_semana = 'Sexta' THEN qa.horas_laboratorio ELSE 0 END), 0) as sex, COALESCE(SUM(CASE WHEN qa.dia_semana = 'Sábado' THEN qa.horas_laboratorio ELSE 0 END), 0) as sab, COALESCE(SUM(qa.horas_laboratorio), 0) as total FROM laboratorios l LEFT JOIN quadro_aulas qa ON l.id = qa.id_laboratorio AND qa.id_quadro = ? GROUP BY l.id, l.nome ORDER BY total DESC, l.nome ASC";
        $stmt_lab = $pdo->prepare($sql_lab);
        $stmt_lab->execute([$quadro_selecionado]);
        $relatorio_labs = $stmt_lab->fetchAll(PDO::FETCH_ASSOC);

        $sql_curso = "SELECT curso, SUM(carga_horaria_total) as total FROM quadro_aulas WHERE id_quadro = ? GROUP BY curso ORDER BY total DESC";
        $stmt_curso = $pdo->prepare($sql_curso);
        $stmt_curso->execute([$quadro_selecionado]);
        $relatorio_cursos = $stmt_curso->fetchAll(PDO::FETCH_ASSOC);

        $grafico_curso_nomes = [];
        $grafico_curso_horas = [];
        foreach ($relatorio_cursos as $rc) {
            $grafico_curso_nomes[] = $rc['curso'];
            $grafico_curso_horas[] = $rc['total'];
        }

        // Reservas avulsas aprovadas (últimas 4 semanas + próximas 4) somam nas métricas de lab/professor
        $where_avulsas = "a.status = 'aprovado' AND " . app_sql_date_between('a.data_reserva', 28, 28);
        $stmt_avulsas = $pdo->query(
            "SELECT a.data_reserva, a.periodo, l.nome AS laboratorio, u.nome AS professor
             FROM agendamentos a
             INNER JOIN laboratorios l ON a.id_laboratorio = l.id
             INNER JOIN usuarios u ON a.id_professor = u.id
             WHERE {$where_avulsas}"
        );
        $reservas_avulsas = $stmt_avulsas->fetchAll(PDO::FETCH_ASSOC);
        $total_reservas_avulsas = count($reservas_avulsas);

        $labIndex = [];
        foreach ($relatorio_labs as $i => $rl) {
            $labIndex[$rl['laboratorio']] = $i;
        }
        $profIndex = [];
        foreach ($relatorio_professores as $i => $rp) {
            $profIndex[$rp['professor']] = $i;
        }

        $mapDiaCol = [
            'Segunda' => 'seg', 'Terça' => 'ter', 'Quarta' => 'qua',
            'Quinta' => 'qui', 'Sexta' => 'sex', 'Sábado' => 'sab',
        ];
        $diasPt = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];

        foreach ($reservas_avulsas as $av) {
            $periodoAv = trim((string) $av['periodo']);
            $horasAv = ($periodoAv === '1º e 2º Horários' || str_contains($periodoAv, '1º e 2º')) ? 4 : 2;
            $total_reservas_avulsas_horas += $horasAv;
            $diaPt = $diasPt[(int) date('w', strtotime($av['data_reserva']))];
            $col = $mapDiaCol[$diaPt] ?? null;

            if (isset($labIndex[$av['laboratorio']])) {
                $idx = $labIndex[$av['laboratorio']];
                if ($col) {
                    $relatorio_labs[$idx][$col] += $horasAv;
                }
                $relatorio_labs[$idx]['total'] += $horasAv;
            }

            $profNome = $av['professor'];
            if (isset($profIndex[$profNome])) {
                $pidx = $profIndex[$profNome];
                if ($col) {
                    $relatorio_professores[$pidx][$col . '_t'] += $horasAv;
                    $relatorio_professores[$pidx][$col . '_l'] += $horasAv;
                }
                $relatorio_professores[$pidx]['total'] += $horasAv;
                $relatorio_professores[$pidx]['total_l'] += $horasAv;
            } else {
                $novo = [
                    'professor' => $profNome,
                    'seg_t' => 0, 'seg_l' => 0, 'ter_t' => 0, 'ter_l' => 0,
                    'qua_t' => 0, 'qua_l' => 0, 'qui_t' => 0, 'qui_l' => 0,
                    'sex_t' => 0, 'sex_l' => 0, 'sab_t' => 0, 'sab_l' => 0,
                    'total' => $horasAv, 'total_l' => $horasAv,
                ];
                if ($col) {
                    $novo[$col . '_t'] = $horasAv;
                    $novo[$col . '_l'] = $horasAv;
                }
                $relatorio_professores[] = $novo;
                $profIndex[$profNome] = count($relatorio_professores) - 1;
            }
        }

        // Recalcula KPIs e gráficos após incluir reservas avulsas
        $grafico_prof_nomes = [];
        $grafico_prof_horas = [];
        $grafico_prof_lab = [];
        $grafico_prof_sala = [];
        $grafico_lab_nomes = [];
        $grafico_lab_uso = [];
        $grafico_lab_ocioso = [];
        $uso_global = 0;
        $min_uso = 9999;
        $max_uso = -1;
        $lab_mais_usado = ['nome' => '-', 'horas' => 0];
        $lab_mais_ocioso = ['nome' => '-', 'horas' => 0];

        usort($relatorio_professores, function ($a, $b) {
            return ($b['total'] ?? 0) <=> ($a['total'] ?? 0);
        });
        $count = 0;
        foreach ($relatorio_professores as $rp) {
            if ($count < 10) {
                $grafico_prof_nomes[] = $rp['professor'];
                $grafico_prof_horas[] = $rp['total'];
                $grafico_prof_lab[] = $rp['total_l'];
                $grafico_prof_sala[] = $rp['total'] - $rp['total_l'];
                $count++;
            }
        }

        foreach ($relatorio_labs as $rl) {
            $uso_global += $rl['total'];
            $ocioso = $capacidade_max_semanal - $rl['total'];
            $grafico_lab_nomes[] = $rl['laboratorio'];
            $grafico_lab_uso[] = $rl['total'];
            $grafico_lab_ocioso[] = $ocioso;
            if ($rl['total'] > $max_uso) {
                $max_uso = $rl['total'];
                $lab_mais_usado = ['nome' => $rl['laboratorio'], 'horas' => $rl['total']];
            }
            if ($rl['total'] < $min_uso) {
                $min_uso = $rl['total'];
                $lab_mais_ocioso = ['nome' => $rl['laboratorio'], 'horas' => $ocioso];
            }
        }

        $total_labs_count = count($laboratorios_cadastrados);
        $capacidade_global = $total_labs_count * $capacidade_max_semanal;
        $taxa_ocupacao_global = $capacidade_global > 0 ? round(($uso_global / $capacidade_global) * 100) : 0;
        $taxa_ociosidade_global = 100 - $taxa_ocupacao_global;

    } catch (PDOException $e) {
        $erro_banco_relatorio = true;
    }
}

// --- CALENDÁRIO ---
$eventos_calendario = [];
function converterHorario($turno, $periodo)
{
    if ($turno == 'Matutino') {
        return ($periodo == '1º Horário') ? ['08:20:00', '10:00:00'] : (($periodo == '2º Horário') ? ['10:15:00', '11:55:00'] : ['08:20:00', '11:55:00']);
    }
    if ($turno == 'Vespertino') {
        return ($periodo == '1º Horário') ? ['14:20:00', '16:00:00'] : (($periodo == '2º Horário') ? ['16:20:00', '18:00:00'] : ['14:20:00', '18:00:00']);
    }
    return ($periodo == '1º Horário') ? ['19:20:00', '21:00:00'] : (($periodo == '2º Horário') ? ['21:10:00', '22:50:00'] : ['19:20:00', '22:50:00']);
}

foreach ($agendamentos_aprovados as $av) {
    list($start, $end) = converterHorario($av['turno'], $av['periodo']);
    $eventos_calendario[] = ['title' => $av['disciplina'] . ' (' . $av['professor'] . ')', 'start' => $av['data_reserva'] . 'T' . $start, 'end' => $av['data_reserva'] . 'T' . $end, 'className' => 'apple-event-avulsa', 'extendedProps' => ['local' => '<i class="bi bi-pc-display me-1"></i> Lab: ' . htmlspecialchars($av['laboratorio'])]];
}

if ($quadro_selecionado && count($todas_aulas) > 0) {
    $dias_map_num = ['Domingo' => 0, 'Segunda' => 1, 'Terça' => 2, 'Quarta' => 3, 'Quinta' => 4, 'Sexta' => 5, 'Sábado' => 6];
    $ano_atual = date('Y');
    $mes_atual = (int) date('m');
    if ($mes_atual <= 6) {
        $start_recur = $ano_atual . '-01-01';
        $end_recur = $ano_atual . '-07-31';
    } else {
        $start_recur = $ano_atual . '-07-01';
        $end_recur = $ano_atual . '-12-31';
    }
    foreach ($todas_aulas as $f) {
        list($start, $end) = converterHorario($f['turno'], $f['horario']);
        $dia_num = $dias_map_num[$f['dia_semana']] ?? 1;
        $loc = $f['id_laboratorio'] ? '<i class="bi bi-pc-display me-1"></i> Lab: ' . htmlspecialchars($f['lab_nome']) : '<i class="bi bi-door-open me-1"></i> Sala: ' . htmlspecialchars($f['sala'] ?? '-');
        $eventos_calendario[] = ['title' => $f['disc_nome'] . ' (' . ($f['prof_nome'] ?? 'EAD') . ')', 'daysOfWeek' => [$dia_num], 'startTime' => $start, 'endTime' => $end, 'startRecur' => $start_recur, 'endRecur' => $end_recur, 'className' => 'apple-event-fixa', 'extendedProps' => ['local' => $loc]];
    }
}

$feriados_2026 = ['2026-01-01' => 'Ano Novo', '2026-02-16' => 'Recesso de Carnaval', '2026-02-17' => 'Carnaval', '2026-04-03' => 'Paixão de Cristo', '2026-04-21' => 'Tiradentes', '2026-05-01' => 'Dia do Trabalho', '2026-06-04' => 'Corpus Christi', '2026-09-07' => 'Independência', '2026-10-12' => 'Nossa Sra. Aparecida', '2026-11-02' => 'Finados', '2026-11-15' => 'Proclamação da República', '2026-12-25' => 'Natal'];
foreach ($feriados_2026 as $data => $nome_feriado) {
    $eventos_calendario[] = ['title' => 'Feriado: ' . $nome_feriado, 'start' => $data, 'allDay' => true, 'className' => 'apple-event-feriado', 'extendedProps' => ['local' => '<i class="bi bi-calendar-x me-1"></i> Instituição Fechada']];
}
$eventos_json = json_encode($eventos_calendario);

        // Retorna todas as variáveis geradas para a view
        $vars = get_defined_vars();
        $usuarioSvc = new UsuarioService();
        $mailSvc    = new MailService();
        $vars['lista_usuarios']   = $usuarioSvc->listar();
        $vars['mail_configurado'] = $mailSvc->isConfigured();
        $vars['mail_provedor']    = $mailSvc->provedorAtivo();
        $vars['painel_rapido']    = $painel_rapido;
        $vars['secoes_pesadas']   = [
            'sessao-relatorios'       => $carregar_relatorios,
            'sessao-historico-geral'  => $carregar_historico,
        ];
        unset($vars['pdo'], $vars['this']);
        return $this->render('coordenador/painel', $vars);
    }
}
?>