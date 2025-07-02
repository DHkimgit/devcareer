<?php
    session_start();
    require_once __DIR__ . '/../config/db.php';

    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id']) && !isset($_COOKIE['user_id'])) {
        exit;
    }

    $user_id = $_SESSION['user_id'] ?? $_COOKIE['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $assignment_id = $_POST['assignment_id'] ?? null;

        if (empty($assignment_id)) {
            exit;
        }

        $pdo = getPDO();

        try {
            $stmt = $pdo->prepare("DELETE FROM college_assignment WHERE id = :id AND user_id = :user_id");
            $stmt->bindParam(':id', $assignment_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Assignment deletion error: " . $e->getMessage());
        }
    }
?>