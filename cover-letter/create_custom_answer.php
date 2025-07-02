<?php
session_start();
require_once __DIR__ . '/../components/sidebar/sidebar.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) && !isset($_COOKIE['user_id'])) {
    header('Location: /login/index.php');
    exit;
} else {
    if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
        $_SESSION['user_id'] = $_COOKIE['user_id'];
        $_SESSION['username'] = isset($_COOKIE['username']) ? $_COOKIE['username'] : 'User';
    }
    $user_id = $_SESSION['user_id'];
}

$pdo = getPDO();
$custom_question_details = null;
$current_answer = '';
$error_message = '';
$success_message = '';

if (!isset($_GET['custom_question_id']) || empty($_GET['custom_question_id'])) {
    $_SESSION['error_message'] = "질문 ID가 제공되지 않았습니다.";
    header('Location: /cover-letter/index.php');
    exit;
}

$custom_question_id = filter_input(INPUT_GET, 'custom_question_id', FILTER_VALIDATE_INT);

if (!$custom_question_id) {
    $_SESSION['error_message'] = "유효하지 않은 질문 ID입니다.";
    header('Location: /cover-letter/index.php');
    exit;
}

try {
    $sql_question = "SELECT
                        ccq.id AS custom_question_id,
                        ccq.question AS custom_question_text,
                        ccq.organization AS custom_orgainzation_name,
                        clqc.name AS custom_category_name
                    FROM
                        cover_letter_custom_question ccq
                    LEFT JOIN
                        cover_letter_question_category clqc ON ccq.category_id = clqc.id
                    WHERE
                        ccq.id = :custom_question_id AND ccq.user_id = :user_id";
    $stmt_question = $pdo->prepare($sql_question);
    $stmt_question->bindParam(':custom_question_id', $custom_question_id, PDO::PARAM_INT);
    $stmt_question->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_question->execute();
    $custom_question_details = $stmt_question->fetch(PDO::FETCH_ASSOC);

    if (!$custom_question_details) {
        $_SESSION['error_message'] = "해당 커스텀 질문을 찾을 수 없거나 접근 권한이 없습니다.";
        header('Location: /cover-letter/index.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("커스텀 질문 조회 오류: " . $e->getMessage());
    $error_message = "질문 정보를 불러오는 중 오류가 발생했습니다.";
}

try {
    $sql_get_answer = "SELECT answer FROM cover_letter_custom_answer WHERE user_id = :user_id AND custrom_question_id = :custom_question_id";
    $stmt_get_answer = $pdo->prepare($sql_get_answer);
    $stmt_get_answer->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_get_answer->bindParam(':custom_question_id', $custom_question_id, PDO::PARAM_INT);
    $stmt_get_answer->execute();
    $current_answer_data = $stmt_get_answer->fetch(PDO::FETCH_ASSOC);
    if ($current_answer_data) {
        $current_answer = $current_answer_data['answer'];
    }
} catch (PDOException $e) {
    error_log("기존 커스텀 답변 조회 오류: " . $e->getMessage());

}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answer_text = trim($_POST['answer'] ?? '');

    if (empty($answer_text) && $answer_text !== '0') {
        $error_message = "답변 내용을 입력해주세요.";
    } else {
        try {
            $sql_check = "SELECT id FROM cover_letter_custom_answer WHERE user_id = :user_id AND custrom_question_id = :custom_question_id";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_check->bindParam(':custom_question_id', $custom_question_id, PDO::PARAM_INT);
            $stmt_check->execute();
            $existing_answer = $stmt_check->fetch();

            if ($existing_answer) {
                $sql_update = "UPDATE cover_letter_custom_answer SET answer = :answer, updated_at = NOW() WHERE id = :answer_id AND user_id = :user_id";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->bindParam(':answer', $answer_text, PDO::PARAM_STR);
                $stmt_update->bindParam(':answer_id', $existing_answer['id'], PDO::PARAM_INT);
                $stmt_update->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt_update->execute();
                $_SESSION['success_message'] = "커스텀 답변이 성공적으로 수정되었습니다.";
            } else {
                $sql_insert = "INSERT INTO cover_letter_custom_answer (user_id, custrom_question_id, answer, created_at, updated_at) VALUES (:user_id, :custom_question_id, :answer, NOW(), NOW())";
                $stmt_insert = $pdo->prepare($sql_insert);
                $stmt_insert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt_insert->bindParam(':custom_question_id', $custom_question_id, PDO::PARAM_INT);
                $stmt_insert->bindParam(':answer', $answer_text, PDO::PARAM_STR);
                $stmt_insert->execute();
                $_SESSION['success_message'] = "커스텀 답변이 성공적으로 저장되었습니다.";
            }
            header('Location: /cover-letter/index.php');
            exit;
        } catch (PDOException $e) {
            error_log("커스텀 답변 저장/수정 오류: " . $e->getMessage());
            $error_message = "답변을 저장/수정하는 중 오류가 발생했습니다: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>커스텀 자기소개서 답변 작성</title>
    <script src="/tailwind.js"></script>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.css" rel="stylesheet">
    <style>
        body { font-family: 'Pretendard', sans-serif; margin: 0; padding: 0; background-color: #f7f7fb; }
        .content-wrapper { display: flex; }
        .main-content { margin-left: 260px; padding: 20px; width: calc(100% - 260px); padding-top: 100px; }
        .navbar { background-color: white; padding: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 1.5rem; font-weight: bold; background: linear-gradient(90deg, #5F43FF 11.64%, #8243FF 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; text-fill-color: transparent; }
        .navbar-user { font-size: 0.9rem; color: #505050; }
        .btn { padding: 0.5rem 1rem; border-radius: 0.375rem; font-weight: 600; transition: background-color 0.3s ease; text-decoration: none; display: inline-block; font-size: 0.875rem; }
        .btn-primary { background-color: #5F43FF; color: white; }
        .btn-primary:hover { background-color: #8243FF; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-secondary:hover { background-color: #5a6268; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333; }
        .form-input, .form-textarea { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 0.375rem; font-size: 0.9rem; box-sizing: border-box; }
        .form-textarea { min-height: 200px; resize: vertical; }
        .question-text { font-size: 1.1rem; color: #333; margin-bottom: 0.5rem; }
    </style>
</head>
<body>
<div class="navbar">
        <div class="flex items-center">
            <div class="navbar-brand">devcareer</div>
            <span class="ml-[106px] mx-4 text-gray-400">|</span>
            <a href="/main/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">홈</a>
            <a href="/cover-letter/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-primary hover:text-primary-hover">자기소개서</a>
        </div>
        <div class="navbar-user">
             <div class="font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#808080] flex items-center">
            <?php if (isset($_SESSION['email'])): ?>
                <span><?php echo htmlspecialchars($_SESSION['email']); ?> 님 환영합니다.</span>
                <a href="/login/logout.php" title="로그아웃" class="ml-4 text-gray-600 hover:text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/><path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/></svg>
                </a>
            <?php else: ?>
                <a href="/login/index.php" class="text-primary hover:text-primary-hover">로그인</a>
            <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <?php display_sidebar('/cover-letter/index.php'); ?>
        <div class="main-content">
            <h1 class="text-3xl font-bold mb-8 text-gray-800">커스텀 자기소개서 답변 작성</h1>

            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p class="font-bold">오류</p>
                    <p><?php echo $error_message; ?></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($success_message) && empty($error_message)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    <p class="font-bold">성공</p>
                    <p><?php echo $success_message; ?></p>
                </div>
            <?php endif; ?>

            <?php if ($custom_question_details): ?>
                <div class="mb-8 p-6 bg-white rounded-xl shadow-lg">
                    <h2 class="text-2xl font-semibold text-gray-700 mb-3">질문 내용</h2>
                    <p class="text-lg text-gray-800 leading-relaxed mb-2">
                        <?php echo nl2br(htmlspecialchars($custom_question_details['custom_question_text'])); ?>
                    </p>
                    <div class="text-sm text-gray-500">
                        <span class="font-medium">기관:</span> <?php echo htmlspecialchars($custom_question_details['custom_orgainzation_name']); ?> |
                        <span class="font-medium">카테고리:</span> <?php echo htmlspecialchars($custom_question_details['custom_category_name']); ?>
                    </div>
                </div>

                <div class="p-6 bg-white rounded-xl shadow-lg">
                    <h2 class="text-2xl font-semibold text-gray-700 mb-6">답변 작성</h2>
                    <form action="create_custom_answer.php?custom_question_id=<?php echo $custom_question_id; ?>" method="POST">
                        <div class="mb-6">
                            <label for="answer" class="form-label text-lg">답변</label>
                            <textarea id="answer" name="answer" class="form-textarea mt-2" rows="12" placeholder="여기에 답변을 입력하세요..."><?php echo htmlspecialchars($current_answer); ?></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <a href="/cover-letter/index.php" class="btn btn-secondary px-6 py-2">취소</a>
                            <button type="submit" class="btn btn-primary px-6 py-2">답변 저장</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 mt-6 rounded-md shadow" role="alert">
                    <p class="font-bold">알림</p>
                    <p>질문 정보를 불러올 수 없습니다. 질문 ID (<?php echo isset($custom_question_id) ? htmlspecialchars($custom_question_id) : '제공되지 않음'; ?>)가 올바른지 확인해주세요.</p>
                    <p class="mt-2">
                        <a href="/cover-letter/index.php" class="font-semibold underline hover:text-yellow-800">커스텀 질문 목록으로 돌아가기</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>