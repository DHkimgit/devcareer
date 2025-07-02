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
$question_details = null;
$error_message = '';
$success_message = '';

if (!isset($_GET['question_id']) || empty($_GET['question_id'])) {
    header('Location: /cover-letter/index.php');
    exit;
}

$question_id = filter_input(INPUT_GET, 'question_id', FILTER_VALIDATE_INT);

if (!$question_id) {
    header('Location: /cover-letter/index.php');
    exit;
}

try {
    $sql = "SELECT
                clq.id AS question_id,
                clq.question,
                o.name AS organization_name,
                clqc.name AS category_name
            FROM
                cover_letter_question clq
            JOIN
                orgainzation o ON clq.orgainzation_id = o.id
            JOIN
                cover_letter_question_category clqc ON clq.category_id = clqc.id
            WHERE
                clq.id = :question_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':question_id', $question_id, PDO::PARAM_INT);
    $stmt->execute();
    $question_details = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$question_details) {
        $_SESSION['error_message'] = "해당 질문을 찾을 수 없습니다.";
        header('Location: /cover-letter/index.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("질문 조회 오류: " . $e->getMessage());
    $error_message = "질문 정보를 불러오는 중 오류가 발생했습니다.";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answer = trim($_POST['answer'] ?? '');

    if (empty($answer)) {
        $error_message = "답변 내용을 입력해주세요.";
    } else {
        try {
            $sql_check = "SELECT id FROM cover_letter_answer WHERE user_id = :user_id AND question_id = :question_id";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_check->bindParam(':question_id', $question_id, PDO::PARAM_INT);
            $stmt_check->execute();
            $existing_answer = $stmt_check->fetch();

            if ($existing_answer) {
                $sql_update = "UPDATE cover_letter_answer SET answer = :answer, updated_at = NOW() WHERE id = :answer_id AND user_id = :user_id";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->bindParam(':answer', $answer, PDO::PARAM_STR);
                $stmt_update->bindParam(':answer_id', $existing_answer['id'], PDO::PARAM_INT);
                $stmt_update->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt_update->execute();
                $_SESSION['success_message'] = "답변이 성공적으로 수정되었습니다.";
            } else {
                $sql_insert = "INSERT INTO cover_letter_answer (user_id, question_id, answer) VALUES (:user_id, :question_id, :answer)";
                $stmt_insert = $pdo->prepare($sql_insert);
                $stmt_insert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt_insert->bindParam(':question_id', $question_id, PDO::PARAM_INT);
                $stmt_insert->bindParam(':answer', $answer, PDO::PARAM_STR);
                $stmt_insert->execute();
                $_SESSION['success_message'] = "답변이 성공적으로 저장되었습니다.";
            }
            header('Location: /cover-letter/index.php');
            exit;
        } catch (PDOException $e) {
            error_log("답변 저장 오류: " . $e->getMessage());
            $error_message = "답변을 저장하는 중 오류가 발생했습니다. 다시 시도해주세요.";
        }
    }
} else {
    try {
        $sql_get_answer = "SELECT answer FROM cover_letter_answer WHERE user_id = :user_id AND question_id = :question_id";
        $stmt_get_answer = $pdo->prepare($sql_get_answer);
        $stmt_get_answer->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_get_answer->bindParam(':question_id', $question_id, PDO::PARAM_INT);
        $stmt_get_answer->execute();
        $current_answer_data = $stmt_get_answer->fetch(PDO::FETCH_ASSOC);
        if ($current_answer_data) {
            $current_answer = $current_answer_data['answer'];
        } else {
            $current_answer = '';
        }
    } catch (PDOException $e) {
        error_log("기존 답변 조회 오류: " . $e->getMessage());
        $current_answer = '';
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>자기소개서 답변 작성</title>
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
        .question-box { background-color: #f8f9fa; border: 1px solid #e9ecef; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1.5rem; }
        .question-text { font-size: 1.1rem; color: #333; margin-bottom: 0.5rem; }
        .question-meta { font-size: 0.9rem; color: #6c757d; }
        .section-title { font-size: 1.5rem; font-weight: bold; color: #333; margin-bottom: 1rem; }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { 'pretendard': ['Pretendard', 'sans-serif'], },
                    colors: { 'primary': '#5F43FF', 'primary-hover': '#8243FF', }
                }
            }
        }
    </script>
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
            <h1 class="section-title">자기소개서 답변 작성</h1>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">오류:</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($question_details): ?>
                <div class="question-box">
                    <p class="question-meta">
                        <strong>회사/기관:</strong> <?php echo htmlspecialchars($question_details['organization_name']); ?> | 
                        <strong>카테고리:</strong> <?php echo htmlspecialchars($question_details['category_name']); ?>
                    </p>
                    <p class="question-text mt-2"><?php echo nl2br(htmlspecialchars($question_details['question'])); ?></p>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <form method="POST" action="/cover-letter/create_answer.php?question_id=<?php echo $question_id; ?>">
                        <input type="hidden" name="question_id" value="<?php echo $question_id; ?>">
                        <div class="mb-4">
                            <label for="answer" class="form-label">나의 답변</label>
                            <textarea id="answer" name="answer" class="form-textarea" rows="10" placeholder="여기에 답변을 입력하세요..."><?php echo htmlspecialchars($current_answer ?? ''); ?></textarea>
                        </div>
                        <div class="flex justify-end space-x-2">
                            <a href="/cover-letter/index.php" class="btn btn-secondary">목록으로</a>
                            <button type="submit" class="btn btn-primary">답변 저장</button>
                        </div>
                    </form>
                </div>
            <?php elseif(!$error_message):?>
                <p class="text-center text-gray-500 py-5">답변을 작성할 질문을 선택해주세요.</p>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>