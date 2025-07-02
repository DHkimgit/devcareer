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
        $_SESSION['email'] = isset($_COOKIE['email']) ? $_COOKIE['email'] : 'User';
    }
    $user_id = $_SESSION['user_id'];
}

$pdo = getPDO();
$custom_question_details = null;
$custom_answer_text = '';
$error_message = '';
$success_message = '';
$custom_question_id = null;

if (!isset($_GET['custom_question_id']) || empty($_GET['custom_question_id'])) {
    $error_message = "커스텀 질문 ID가 제공되지 않았습니다.";
} else {
    $custom_question_id = filter_input(INPUT_GET, 'custom_question_id', FILTER_VALIDATE_INT);
    if (!$custom_question_id) {
        $error_message = "유효하지 않은 커스텀 질문 ID입니다.";
    } else {
        try {
            $sql_fetch = "SELECT
                            ccq.id AS custom_question_id,
                            ccq.organization,
                            ccq.question,
                            clqc.name AS category_name,
                            cca.answer AS custom_answer_text
                        FROM
                            cover_letter_custom_question ccq
                        LEFT JOIN
                            cover_letter_question_category clqc ON ccq.category_id = clqc.id
                        LEFT JOIN
                            cover_letter_custom_answer cca ON ccq.id = cca.custrom_question_id AND cca.user_id = :user_id_cca
                        WHERE
                            ccq.id = :custom_question_id AND ccq.user_id = :user_id_ccq";
            $stmt_fetch = $pdo->prepare($sql_fetch);
            $stmt_fetch->bindParam(':user_id_cca', $user_id, PDO::PARAM_INT);
            $stmt_fetch->bindParam(':custom_question_id', $custom_question_id, PDO::PARAM_INT);
            $stmt_fetch->bindParam(':user_id_ccq', $user_id, PDO::PARAM_INT);
            $stmt_fetch->execute();
            $result = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                $error_message = "해당 커스텀 질문을 찾을 수 없거나 접근 권한이 없습니다.";
            } else {
                $custom_question_details = $result;
                $custom_answer_text = $result['custom_answer_text'] ?? '';
            }
        } catch (PDOException $e) {
            error_log("Custom Question/Answer Fetch DB Error: " . $e->getMessage());
            $error_message = "정보를 불러오는 중 오류가 발생했습니다.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $custom_question_id && $custom_question_details && !$error_message) {
    $answer_text = trim($_POST['answer_text'] ?? '');

    try {
        $pdo->beginTransaction();

        $stmt_check = $pdo->prepare("SELECT id FROM cover_letter_custom_answer WHERE custrom_question_id = :custom_question_id AND user_id = :user_id");
        $stmt_check->bindParam(':custom_question_id', $custom_question_id, PDO::PARAM_INT);
        $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_check->execute();
        $existing_answer = $stmt_check->fetch();

        if ($existing_answer) {
            $stmt_update = $pdo->prepare("UPDATE cover_letter_custom_answer SET answer = :answer, updated_at = NOW() WHERE id = :answer_id AND user_id = :user_id");
            $stmt_update->bindParam(':answer', $answer_text, PDO::PARAM_STR);
            $stmt_update->bindParam(':answer_id', $existing_answer['id'], PDO::PARAM_INT);
            $stmt_update->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_update->execute();
        } else {
            $stmt_insert = $pdo->prepare("INSERT INTO cover_letter_custom_answer (user_id, custrom_question_id, answer, created_at, updated_at) VALUES (:user_id, :custom_question_id, :answer, NOW(), NOW())");
            $stmt_insert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_insert->bindParam(':custom_question_id', $custom_question_id, PDO::PARAM_INT);
            $stmt_insert->bindParam(':answer', $answer_text, PDO::PARAM_STR);
            $stmt_insert->execute();
        }
        
        $pdo->commit();
        $success_message = "답변이 성공적으로 저장되었습니다.";
        $custom_answer_text = $answer_text;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Custom Answer Save DB Error: " . $e->getMessage());
        $error_message = "답변 저장 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>커스텀 질문 답변 작성/수정</title>
    <script src="/tailwind.js"></script>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.css" rel="stylesheet">
    <style>
        body { font-family: 'Pretendard', sans-serif; margin: 0; padding: 0; background-color: #f7f7fb; }
        .content-wrapper { display: flex; }
        .main-content { margin-left: 260px; padding: 20px; width: calc(100% - 260px); padding-top: 100px; }
        .navbar { background-color: white; padding: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 1.5rem; font-weight: bold; background: linear-gradient(90deg, #5F43FF 11.64%, #8243FF 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; text-fill-color: transparent; }
        .navbar-user { font-size: 0.9rem; color: #505050; }
        .form-label { display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.95rem; }
        .form-input, .form-textarea { width: 100%; padding: 0.75rem; border: 1px solid #D1D5DB; border-radius: 0.375rem; font-size: 0.95rem; color: #374151; margin-bottom: 1rem; box-sizing: border-box; }
        .form-textarea { min-height: 200px; font-family: 'Pretendard', sans-serif; }
        .question-info { background-color: #f9fafb; border: 1px solid #e5e7eb; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1.5rem; }
        .question-info p { margin-bottom: 0.25rem; }
        .question-info strong { color: #374151; }
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
            <a href="/projects/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">프로젝트</a>
            <a href="/blog/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">블로그</a>
            <a href="/jobs/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">채용 공고</a>
            <a href="/courses/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">수강 정보</a>
            <a href="/cover-letter/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-primary hover:text-primary-hover">자기소개서</a>
            <a href="/algorithms/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">알고리즘</a>
            <a href="/resume/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">이력서</a>
        </div>
        <div class="navbar-user">
             <div class="font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#808080] flex items-center">
            <?php if (isset($_SESSION['email'])): ?>
                <span><?php echo htmlspecialchars($_SESSION['email']); ?> 님</span>
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
            <h1 class="text-3xl font-bold text-gray-800 mb-6">커스텀 질문 답변 작성/수정</h1>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
                     <div class="mt-2">
                        <a href="/cover-letter/index.php" class="text-sm text-blue-600 hover:underline">답변 목록으로 돌아가기</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($custom_question_details): ?>
                <div class="question-info">
                    <p><strong class="w-20 inline-block">기관/회사:</strong> <?php echo htmlspecialchars($custom_question_details['organization']); ?></p>
                    <?php if (!empty($custom_question_details['category_name'])): ?>
                        <p><strong class="w-20 inline-block">카테고리:</strong> <?php echo htmlspecialchars($custom_question_details['category_name']); ?></p>
                    <?php endif; ?>
                    <p><strong class="w-20 inline-block">질문:</strong> <?php echo htmlspecialchars($custom_question_details['question']); ?></p>
                </div>

                <form action="/cover-letter/edit_custom_answer.php?custom_question_id=<?php echo $custom_question_id; ?>" method="POST" class="bg-white p-6 rounded-lg shadow-md">
                    <input type="hidden" name="custom_question_id" value="<?php echo $custom_question_id; ?>">
                    
                    <div class="mb-6">
                        <label for="answer_text" class="form-label">답변 내용</label>
                        <textarea id="answer_text" name="answer_text" class="form-textarea" rows="10"><?php echo htmlspecialchars($custom_answer_text); ?></textarea>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <a href="/cover-letter/index.php" class="text-sm text-gray-600 hover:underline">&laquo; 취소하고 목록으로</a>
                        <button type="submit" class="bg-primary text-white px-6 py-2 rounded hover:bg-primary-hover">저장하기</button>
                    </div>
                </form>
            <?php elseif (!$error_message && $custom_question_id): ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline">답변을 작성할 질문을 불러올 수 없습니다. ID를 확인해주세요.</span>
                     <div class="mt-4">
                         <a href="/cover-letter/index.php" class="text-sm text-blue-600 hover:underline">자기소개서 목록으로 돌아가기</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>