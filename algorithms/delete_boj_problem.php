<?php
    session_start();
    require_once __DIR__ . '/../config/db.php';

    if (!isset($_SESSION['user_id']) && !isset($_COOKIE['user_id'])) {
        header('Location: /login/index.php');
        exit;
    } else {
        if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
            $_SESSION['user_id'] = $_COOKIE['user_id'];
        }
        $user_id = $_SESSION['user_id'];
    }

    $message = '';
    $message_type = '';

    if (!isset($_GET['id']) || empty($_GET['id'])) {
        $message = "잘못된 접근입니다. 문제 ID가 제공되지 않았습니다.";
        $message_type = 'error';
    } else {
        $problem_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$problem_id) {
            $message = "유효하지 않은 문제 ID입니다.";
            $message_type = 'error';
        } else {
            $pdo = getPDO();
            try {
                $pdo->beginTransaction();

                $stmt_delete_cats = $pdo->prepare("DELETE FROM boj_problem_category WHERE boj_problem_id = :problem_id");
                $stmt_delete_cats->bindParam(':problem_id', $problem_id, PDO::PARAM_INT);
                $stmt_delete_cats->execute();

                $stmt_delete_problem = $pdo->prepare("DELETE FROM boj_problem WHERE id = :id AND user_id = :user_id");
                $stmt_delete_problem->bindParam(':id', $problem_id, PDO::PARAM_INT);
                $stmt_delete_problem->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt_delete_problem->execute();

                if ($stmt_delete_problem->rowCount() > 0) {
                    $pdo->commit();
                    $message = "문제가 성공적으로 삭제되었습니다.";
                    $message_type = 'success';
                } else {
                    $pdo->rollBack();
                    $message = "문제를 삭제할 수 없거나 해당 문제에 대한 권한이 없습니다.";
                    $message_type = 'error';
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("BOJ Problem Delete DB Error: " . $e->getMessage());
                $message = "문제 삭제 중 오류가 발생했습니다.";
                $message_type = 'error';
            }
        }
    }

    $_SESSION['delete_message'] = $message;
    $_SESSION['delete_message_type'] = $message_type;
    header('Location: /algorithms/index.php');
    exit;
?>