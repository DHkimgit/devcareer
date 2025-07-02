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

$project_id_to_delete = null;
if (isset($_GET['id'])) {
    $project_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
}

if (!$project_id_to_delete) {
    $_SESSION['page_error_message'] = "잘못된 접근입니다. 삭제할 프로젝트 ID가 제공되지 않았습니다.";
    header('Location: /projects/index.php');
    exit;
}

$pdo = getPDO();

try {
    $pdo->beginTransaction();

    // 0. 프로젝트에 연결된 블로그 게시물의 project_id를 NULL로 업데이트 (선택적: 또는 게시물도 함께 삭제)
    $stmt_update_blog = $pdo->prepare("UPDATE blog_post SET project_id = NULL WHERE project_id = :project_id AND user_id = :user_id");
    $stmt_update_blog->bindParam(':project_id', $project_id_to_delete, PDO::PARAM_INT);
    $stmt_update_blog->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_update_blog->execute();

    // 1. 프로젝트 기술 스택 매핑 삭제
    $stmt_delete_tech = $pdo->prepare("DELETE FROM project_technology_mapping WHERE project_id = :project_id");
    $stmt_delete_tech->bindParam(':project_id', $project_id_to_delete, PDO::PARAM_INT);
    // $stmt_delete_tech->bindParam(':user_id', $user_id, PDO::PARAM_INT); // project_technology_mapping에는 user_id가 없으므로 프로젝트 소유권은 projects 테이블에서 확인
    $stmt_delete_tech->execute();
    
    // 2. 프로젝트 삭제 (user_id 조건으로 소유권 확인)
    $stmt_delete_project = $pdo->prepare("DELETE FROM projects WHERE id = :project_id AND user_id = :user_id");
    $stmt_delete_project->bindParam(':project_id', $project_id_to_delete, PDO::PARAM_INT);
    $stmt_delete_project->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_delete_project->execute();

    if ($stmt_delete_project->rowCount() > 0) {
        $_SESSION['page_success_message'] = "프로젝트가 성공적으로 삭제되었습니다.";
    } else {
        $_SESSION['page_error_message'] = "프로젝트를 삭제할 수 없거나 권한이 없습니다.";
    }

    $pdo->commit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['page_error_message'] = "프로젝트 삭제 중 오류가 발생했습니다: " . $e->getMessage();
}

header('Location: /projects/index.php');
exit;
?>