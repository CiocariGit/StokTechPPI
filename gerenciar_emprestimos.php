<?php
require_once 'auth.php';
require_once 'conexao.php';
verificar_admin();

header('Content-Type: application/json; charset=utf-8');

function calcular_status_emprestimo($emp) {
    if (!empty($emp['STATUS'])) {
        return strtoupper($emp['STATUS']);
    }

    if (!empty($emp['DATA_DEVOLUCAO'])) {
        return 'DEVOLVIDO';
    }

    if (!empty($emp['DATA_RETIRADA'])) {
        return 'RETIRADO';
    }

    if (!empty($emp['IDADM'])) {
        return 'RECUSADO';
    }

    return 'PENDENTE';
}

function responder(bool $sucesso, string $mensagem = '', array $extra = []): void {
    echo json_encode(array_merge([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function cancelar_emprestimos_vencidos($pdo) {
    $stmt = $pdo->query("
        SELECT IDEMPRESTIMO
        FROM EMPRESTIMO
        WHERE STATUS = 'APROVADO'
        AND DATA_RETIRADA IS NULL
        AND DATA_LIMITE_RETIRADA < CURDATE()
    ");

    $emprestimos = $stmt->fetchAll();

    foreach ($emprestimos as $emp) {
        $idEmprestimo = $emp['IDEMPRESTIMO'];

        $stmtItens = $pdo->prepare("
            SELECT IDCOMPONENTE, QUANTIDADE
            FROM CARRINHO
            WHERE IDEMPRESTIMO = ?
        ");
        $stmtItens->execute([$idEmprestimo]);
        $itens = $stmtItens->fetchAll();

        $stmtDevolver = $pdo->prepare("
            UPDATE COMPONENTE
            SET QUANTIDADE = QUANTIDADE + ?
            WHERE IDCOMPONENTE = ?
        ");

        foreach ($itens as $item) {
            $stmtDevolver->execute([
                (int)$item['QUANTIDADE'],
                (int)$item['IDCOMPONENTE']
            ]);
        }

        $stmtCancelar = $pdo->prepare("
            UPDATE EMPRESTIMO
            SET STATUS = 'CANCELADO'
            WHERE IDEMPRESTIMO = ?
        ");

        $stmtCancelar->execute([$idEmprestimo]);
    }
}

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

try {
    cancelar_emprestimos_vencidos($pdo);

    if ($acao === 'listar') {
        $stmt = $pdo->query("
        SELECT 
            e.IDEMPRESTIMO,
            e.IDALUNO,
            e.IDADM,
            e.STATUS,
            e.DATA_EMPRESTIMO,
            e.DATA_APROVACAO,
            e.DATA_LIMITE_RETIRADA,
            e.DATA_RETIRADA,
            e.PRAZO_DEVOLUCAO,
            e.DATA_DEVOLUCAO,
            aluno.NOME AS ALUNO_NOME,
            aluno.EMAIL AS ALUNO_EMAIL,
            adm.NOME AS ADM_NOME
        FROM EMPRESTIMO e
        LEFT JOIN USUARIO aluno ON aluno.IDUSUARIO = e.IDALUNO
        LEFT JOIN USUARIO adm ON adm.IDUSUARIO = e.IDADM
        ORDER BY e.IDEMPRESTIMO DESC
        ");
        $emprestimos = $stmt->fetchAll();

        $stmtItens = $pdo->prepare("
            SELECT car.IDEMPRESTIMO, car.IDCOMPONENTE, car.QUANTIDADE,
                   comp.NOME AS COMPONENTE_NOME
            FROM CARRINHO car
            INNER JOIN COMPONENTE comp ON comp.IDCOMPONENTE = car.IDCOMPONENTE
            WHERE car.IDEMPRESTIMO = ?
            ORDER BY comp.NOME
        ");

        $stmtMensagens = $pdo->prepare("
        SELECT 
            m.IDMENSAGEM,
            m.IDEMPRESTIMO,
            m.IDUSUARIO,
            m.MENSAGEM,
            m.DATA_ENVIO,
            u.NOME AS USUARIO_NOME,
            u.TIPO AS USUARIO_TIPO
        FROM MENSAGEM_EMPRESTIMO m
        INNER JOIN USUARIO u ON u.IDUSUARIO = m.IDUSUARIO
        WHERE m.IDEMPRESTIMO = ?
        ORDER BY m.DATA_ENVIO ASC, m.IDMENSAGEM ASC
    ");

    $stmtFeedback = $pdo->prepare("
    SELECT 
        f.IDFEEDBACK,
        f.IDEMPRESTIMO,
        f.IDADM,
        f.HOUVE_DANO,
        f.TIPO_DANO,
        f.GRAVIDADE,
        f.LAUDO,
        f.MULTA,
        f.DATA_REGISTRO,
        adm.NOME AS ADM_NOME
    FROM DEVOLUCAO_FEEDBACK f
    LEFT JOIN USUARIO adm ON adm.IDUSUARIO = f.IDADM
    WHERE f.IDEMPRESTIMO = ?
    ORDER BY f.DATA_REGISTRO DESC
    LIMIT 1
    ");

        foreach ($emprestimos as &$emp) {
            $emp['STATUS'] = calcular_status_emprestimo($emp);

            $stmtItens->execute([$emp['IDEMPRESTIMO']]);
            $emp['ITENS'] = $stmtItens->fetchAll();

            $stmtMensagens->execute([$emp['IDEMPRESTIMO']]);
            $emp['MENSAGENS'] = $stmtMensagens->fetchAll();

            $stmtFeedback->execute([$emp['IDEMPRESTIMO']]);
            $emp['FEEDBACK_DEVOLUCAO'] = $stmtFeedback->fetch();
        }
        unset($emp);

        responder(true, '', ['emprestimos' => $emprestimos]);
    }

if ($acao === 'atualizar_status') {
    $idEmprestimo = isset($_POST['idemprestimo']) ? (int)$_POST['idemprestimo'] : -1;
    $novoStatus = strtoupper(trim($_POST['status'] ?? ''));

    $prazoDevolucao = trim($_POST['prazo_devolucao'] ?? '');

    $houveDano = isset($_POST['houve_dano']) ? (int)$_POST['houve_dano'] : 0;
    $tipoDano = trim($_POST['tipo_dano'] ?? '');
    $gravidade = trim($_POST['gravidade'] ?? '');
    $laudo = trim($_POST['laudo'] ?? '');
    $multa = str_replace(',', '.', trim($_POST['multa'] ?? '0'));

    if ($multa === '') {
        $multa = '0';
    }

    $permitidos = ['PENDENTE', 'APROVADO', 'RECUSADO', 'DEVOLVIDO', 'RETIRADO', 'CANCELADO'];

    if ($idEmprestimo < 0 || !in_array($novoStatus, $permitidos, true)) {
        responder(false, 'Dados inválidos para atualizar o empréstimo.');
    }

    if (!is_numeric($multa)) {
        responder(false, 'Valor da multa inválido.');
    }

    $pdo->beginTransaction();

    $stmtEmp = $pdo->prepare("
        SELECT *
        FROM EMPRESTIMO
        WHERE IDEMPRESTIMO = ?
        FOR UPDATE
    ");
    $stmtEmp->execute([$idEmprestimo]);
    $emprestimo = $stmtEmp->fetch();

    if (!$emprestimo) {
        throw new Exception('Empréstimo não encontrado.');
    }

    $statusAntigo = calcular_status_emprestimo($emprestimo);

    $stmtItens = $pdo->prepare("
        SELECT IDCOMPONENTE, QUANTIDADE
        FROM CARRINHO
        WHERE IDEMPRESTIMO = ?
    ");
    $stmtItens->execute([$idEmprestimo]);
    $itens = $stmtItens->fetchAll();

    if ($novoStatus === 'APROVADO') {
        if ($statusAntigo !== 'PENDENTE') {
            throw new Exception('Só é possível aprovar um empréstimo pendente.');
        }

        $stmtStock = $pdo->prepare("
            SELECT NOME, QUANTIDADE
            FROM COMPONENTE
            WHERE IDCOMPONENTE = ?
            FOR UPDATE
        ");

        foreach ($itens as $item) {
            $stmtStock->execute([$item['IDCOMPONENTE']]);
            $comp = $stmtStock->fetch();

            if (!$comp) {
                throw new Exception('Um componente do empréstimo não existe mais.');
            }

            if ((int)$item['QUANTIDADE'] > (int)$comp['QUANTIDADE']) {
                throw new Exception("Estoque insuficiente para aprovar {$comp['NOME']}.");
            }
        }

        $stmtBaixar = $pdo->prepare("
            UPDATE COMPONENTE
            SET QUANTIDADE = QUANTIDADE - ?
            WHERE IDCOMPONENTE = ?
        ");

        foreach ($itens as $item) {
            $stmtBaixar->execute([
                (int)$item['QUANTIDADE'],
                (int)$item['IDCOMPONENTE']
            ]);
        }

        $stmtUpdate = $pdo->prepare("
            UPDATE EMPRESTIMO
            SET 
                STATUS = 'APROVADO',
                IDADM = ?,
                DATA_APROVACAO = CURDATE(),
                DATA_LIMITE_RETIRADA = DATE_ADD(CURDATE(), INTERVAL 7 DAY),
                DATA_RETIRADA = NULL,
                DATA_DEVOLUCAO = NULL,
                PRAZO_DEVOLUCAO = NULL
            WHERE IDEMPRESTIMO = ?
        ");

        $stmtUpdate->execute([
            $_SESSION['idusuario'],
            $idEmprestimo
        ]);
    }

    if ($novoStatus === 'RECUSADO') {
        if ($statusAntigo === 'APROVADO' || $statusAntigo === 'RETIRADO') {
            $stmtDevolver = $pdo->prepare("
                UPDATE COMPONENTE
                SET QUANTIDADE = QUANTIDADE + ?
                WHERE IDCOMPONENTE = ?
            ");

            foreach ($itens as $item) {
                $stmtDevolver->execute([
                    (int)$item['QUANTIDADE'],
                    (int)$item['IDCOMPONENTE']
                ]);
            }
        }

        $stmtUpdate = $pdo->prepare("
            UPDATE EMPRESTIMO
            SET 
                STATUS = 'RECUSADO',
                IDADM = ?,
                DATA_RETIRADA = NULL,
                DATA_DEVOLUCAO = NULL,
                PRAZO_DEVOLUCAO = NULL
            WHERE IDEMPRESTIMO = ?
        ");

        $stmtUpdate->execute([
            $_SESSION['idusuario'],
            $idEmprestimo
        ]);
    }

    if ($novoStatus === 'RETIRADO') {
        if ($statusAntigo !== 'APROVADO') {
            throw new Exception('Só é possível marcar como retirado um empréstimo aprovado.');
        }

        if ($prazoDevolucao === '') {
            throw new Exception('Informe o prazo de devolução.');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $prazoDevolucao)) {
            throw new Exception('Prazo de devolução inválido. Use o formato AAAA-MM-DD.');
        }

        $stmtUpdate = $pdo->prepare("
            UPDATE EMPRESTIMO
            SET 
                STATUS = 'RETIRADO',
                DATA_RETIRADA = CURDATE(),
                PRAZO_DEVOLUCAO = ?
            WHERE IDEMPRESTIMO = ?
        ");

        $stmtUpdate->execute([
            $prazoDevolucao,
            $idEmprestimo
        ]);
    }

    if ($novoStatus === 'DEVOLVIDO') {
        if ($statusAntigo !== 'RETIRADO') {
            throw new Exception('Só é possível marcar como devolvido um empréstimo retirado.');
        }

        if ($houveDano !== 0 && $houveDano !== 1) {
            throw new Exception('Valor inválido para dano.');
        }

        if ($houveDano === 1) {
            if ($tipoDano === '') {
                throw new Exception('Informe o tipo do dano.');
            }

            if ($gravidade === '') {
                throw new Exception('Informe a gravidade do dano.');
            }

            if ($laudo === '') {
                throw new Exception('Informe o laudo da devolução.');
            }
        } else {
            $tipoDano = '';
            $gravidade = '';
        }

        $stmtExisteFeedback = $pdo->prepare("
            SELECT IDFEEDBACK
            FROM DEVOLUCAO_FEEDBACK
            WHERE IDEMPRESTIMO = ?
            LIMIT 1
        ");
        $stmtExisteFeedback->execute([$idEmprestimo]);

        if ($stmtExisteFeedback->fetch()) {
            throw new Exception('Este empréstimo já possui feedback de devolução.');
        }

        $stmtDevolver = $pdo->prepare("
            UPDATE COMPONENTE
            SET QUANTIDADE = QUANTIDADE + ?
            WHERE IDCOMPONENTE = ?
        ");

        foreach ($itens as $item) {
            $stmtDevolver->execute([
                (int)$item['QUANTIDADE'],
                (int)$item['IDCOMPONENTE']
            ]);
        }

        $stmtFeedback = $pdo->prepare("
            INSERT INTO DEVOLUCAO_FEEDBACK
            (
                IDEMPRESTIMO,
                IDADM,
                HOUVE_DANO,
                TIPO_DANO,
                GRAVIDADE,
                LAUDO,
                MULTA
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmtFeedback->execute([
            $idEmprestimo,
            $_SESSION['idusuario'],
            $houveDano,
            $tipoDano,
            $gravidade,
            $laudo,
            (float)$multa
        ]);

        $stmtUpdate = $pdo->prepare("
            UPDATE EMPRESTIMO
            SET 
                STATUS = 'DEVOLVIDO',
                DATA_DEVOLUCAO = CURDATE()
            WHERE IDEMPRESTIMO = ?
        ");

        $stmtUpdate->execute([
            $idEmprestimo
        ]);
    }

    if ($novoStatus === 'CANCELADO') {
        if ($statusAntigo === 'APROVADO' || $statusAntigo === 'RETIRADO') {
            $stmtDevolver = $pdo->prepare("
                UPDATE COMPONENTE
                SET QUANTIDADE = QUANTIDADE + ?
                WHERE IDCOMPONENTE = ?
            ");

            foreach ($itens as $item) {
                $stmtDevolver->execute([
                    (int)$item['QUANTIDADE'],
                    (int)$item['IDCOMPONENTE']
                ]);
            }
        }

        $stmtUpdate = $pdo->prepare("
            UPDATE EMPRESTIMO
            SET 
                STATUS = 'CANCELADO',
                IDADM = ?
            WHERE IDEMPRESTIMO = ?
        ");

        $stmtUpdate->execute([
            $_SESSION['idusuario'],
            $idEmprestimo
        ]);
    }

    $pdo->commit();

    responder(true, 'Status atualizado com sucesso.');
}
    if ($acao === 'enviar_mensagem') {
    $idEmprestimo = isset($_POST['idemprestimo']) ? (int)$_POST['idemprestimo'] : -1;
    $mensagem = trim($_POST['mensagem'] ?? '');

    if ($idEmprestimo < 0 || $mensagem === '') {
        responder(false, 'Dados inválidos para enviar mensagem.');
    }

    $stmtVerifica = $pdo->prepare("SELECT IDEMPRESTIMO FROM EMPRESTIMO WHERE IDEMPRESTIMO = ?");
    $stmtVerifica->execute([$idEmprestimo]);

    if (!$stmtVerifica->fetch()) {
        responder(false, 'Empréstimo não encontrado.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO MENSAGEM_EMPRESTIMO
        (IDEMPRESTIMO, IDUSUARIO, MENSAGEM)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $idEmprestimo,
        $_SESSION['idusuario'],
        $mensagem
    ]);

    responder(true, 'Mensagem enviada com sucesso.');
}

    responder(false, 'Ação inválida.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    responder(false, $e->getMessage());
}
