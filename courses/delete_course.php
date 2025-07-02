<?php
    session_start();
    require_once __DIR__ . '/../config/db.php';

    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id']) && !isset($_COOKIE['user_id'])) {
        echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
        exit;
    }

    $user_id = $_SESSION['user_id'] ?? $_COOKIE['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $course_id = $_POST['course_id'] ?? null;

        if (empty($course_id)) {
            echo json_encode(['success' => false, 'message' => '강좌 ID가 제공되지 않았습니다.']);
            exit;
        }

        $pdo = getPDO();

        try {
            $pdo->beginTransaction();

            $stmt_delete_assignments = $pdo->prepare("DELETE FROM college_assignment WHERE course_id = :course_id AND user_id = :user_id");
            $stmt_delete_assignments->bindParam(':course_id', $course_id, PDO::PARAM_INT);
            $stmt_delete_assignments->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_delete_assignments->execute();

            $stmt_delete_course = $pdo->prepare("DELETE FROM college_course WHERE id = :id AND user_id = :user_id");
            $stmt_delete_course->bindParam(':id', $course_id, PDO::PARAM_INT);
            $stmt_delete_course->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_delete_course->execute();

            if ($stmt_delete_course->rowCount() > 0) {
                $pdo->commit();
            } else {
                $pdo->rollBack();
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Course deletion error: " . $e->getMessage());
        }
    } 
?>