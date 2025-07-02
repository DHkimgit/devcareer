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
$questions = [];
$user_answers = [];
$page_success_message = $_SESSION['success_message'] ?? null;
$page_error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_answer') {
    if (isset($_POST['answer_id'])) {
        $answer_id_to_delete = filter_input(INPUT_POST, 'answer_id', FILTER_VALIDATE_INT);
        if ($answer_id_to_delete) {
            try {
                $sql_delete = "DELETE FROM cover_letter_answer WHERE id = :answer_id AND user_id = :user_id";
                $stmt_delete = $pdo->prepare($sql_delete);
                $stmt_delete->bindParam(':answer_id', $answer_id_to_delete, PDO::PARAM_INT);
                $stmt_delete->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                
                if ($stmt_delete->execute()) {
                    if ($stmt_delete->rowCount() > 0) {
                        $_SESSION['success_message'] = "답변이 성공적으로 삭제되었습니다.";
                    } else {
                        $_SESSION['error_message'] = "답변을 삭제할 권한이 없거나 이미 삭제된 답변입니다.";
                    }
                } else {
                    $_SESSION['error_message'] = "답변 삭제 중 오류가 발생했습니다.";
                }
            } catch (PDOException $e) {
                error_log("답변 삭제 오류: " . $e->getMessage());
                $_SESSION['error_message'] = "답변 삭제 중 데이터베이스 오류가 발생했습니다.";
            }
            header('Location: /cover-letter/index.php');
            exit;
        } else {
            $_SESSION['error_message'] = "잘못된 답변 ID입니다.";
            header('Location: /cover-letter/index.php');
            exit;
        }
    }
}

try {
    $sql_user_answers = "SELECT
                            cla.id AS answer_id,
                            cla.answer,
                            cla.updated_at,
                            clq.id AS question_id,
                            clq.question,
                            o.name AS organization_name,
                            o.type AS organization_type,
                            clqc.name AS category_name
                        FROM
                            cover_letter_answer cla
                        JOIN
                            cover_letter_question clq ON cla.question_id = clq.id
                        JOIN
                            orgainzation o ON clq.orgainzation_id = o.id
                        JOIN
                            cover_letter_question_category clqc ON clq.category_id = clqc.id
                        WHERE
                            cla.user_id = :user_id
                        ORDER BY
                            cla.updated_at DESC";
    $stmt_user_answers = $pdo->prepare($sql_user_answers);
    $stmt_user_answers->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_user_answers->execute();
    $user_answers = $stmt_user_answers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("사용자 답변 조회 오류: " . $e->getMessage());
    $page_error_message = ($page_error_message ? $page_error_message . "<br>" : "") . "작성한 답변 목록을 불러오는 중 오류가 발생했습니다.";
}

try {
    $sql = "SELECT
                clq.id AS question_id,
                clq.question,
                o.name AS organization_name,
                o.type AS organization_type,
                clqc.name AS category_name
            FROM
                cover_letter_question clq
            JOIN
                orgainzation o ON clq.orgainzation_id = o.id 
            JOIN
                cover_letter_question_category clqc ON clq.category_id = clqc.id
            ORDER BY
                o.name ASC, clqc.name ASC, clq.id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("질문 목록 조회 오류: " . $e->getMessage());
    $questions_list_error = "질문 목록을 불러오는 중 오류가 발생했습니다.";
}

try {
    $stmt_categories = $pdo->query("SELECT id, name FROM cover_letter_question_category ORDER BY name ASC");
    if ($stmt_categories) {
        $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);
    } else {
    }

    $sql_custom_questions = "
    SELECT
        ccq.id AS custom_question_id,
        ccq.organization AS custom_orgainzation_name,
        ccq.question AS custom_question_text,
        clqc.name AS custom_category_name,
        cca.id AS custom_answer_id,
        cca.answer AS custom_answer_text,
        ccq.created_at AS custom_question_created_at,
        cca.updated_at AS custom_answer_updated_at
    FROM
        cover_letter_custom_question ccq
    LEFT JOIN
        cover_letter_question_category clqc ON ccq.category_id = clqc.id
    LEFT JOIN
        cover_letter_custom_answer cca ON ccq.id = cca.custrom_question_id AND cca.user_id = :cca_user_id
    WHERE
        ccq.user_id = :ccq_user_id
    ORDER BY
        ccq.organization ASC, clqc.name ASC, ccq.id ASC";
    $stmt_custom_questions = $pdo->prepare($sql_custom_questions);
    $stmt_custom_questions->bindParam(':ccq_user_id', $user_id, PDO::PARAM_INT);
    $stmt_custom_questions->bindParam(':cca_user_id', $user_id, PDO::PARAM_INT);
    if ($stmt_custom_questions->execute()) {
        $custom_questions_list = $stmt_custom_questions->fetchAll(PDO::FETCH_ASSOC);
    } else {
        error_log("사용자 커스텀 질문 목록 조회 쿼리 실패");
    }

} catch (PDOException $e) {
    error_log("커스텀 질문 관련 데이터 조회 오류: " . $e->getMessage());
    $current_error = "커스텀 질문 관련 정보를 불러오는 중 오류가 발생했습니다. $e";
    $page_error_message = ($page_error_message ? $page_error_message . "<br>" : "") . $current_error;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>자기소개서</title>
    <script src="/tailwind.js"></script>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.css" rel="stylesheet">
    <style>
        body { font-family: 'Pretendard', sans-serif; margin: 0; padding: 0; background-color: #f7f7fb; }
        .content-wrapper { display: flex; }
        .main-content { margin-left: 260px; padding: 20px; width: calc(100% - 260px); padding-top: 100px; }
        .navbar { background-color: white; padding: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 1.5rem; font-weight: bold; background: linear-gradient(90deg, #5F43FF 11.64%, #8243FF 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;}
        .navbar-user { font-size: 0.9rem; color: #505050; }
        .btn { padding: 0.5rem 1rem; border-radius: 0.375rem; font-weight: 600; transition: background-color 0.3s ease; text-decoration: none; display: inline-block; font-size: 0.875rem; }
        .btn-primary { background-color: #5F43FF; color: white; }
        .btn-primary:hover { background-color: #8243FF; }
        .btn-sm { padding: 0.25rem 0.75rem; font-size: 0.75rem; line-height: 1.5; border-radius: 0.2rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 0.875rem; }
        th { background-color: #f8f9fa; font-weight: 600; color: #333; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        tr:hover { background-color: #e9ecef; }
        .table-responsive { overflow-x: auto; }
        .section-title { font-size: 1.5rem; font-weight: bold; color: #333; margin-bottom: 1rem; }
        .card-answer-text { max-height: 6rem; overflow-y: auto; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #c5c5c5; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
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
            <a href="/main/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-house-door-fill mr-2" viewBox="0 0 16 16"><path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.505a.5.5 0 0 0 .5.5h2.5a.5.5 0 0 0 .5-.5v-4.09c0-.29-.12-.55-.32-.73l-6-5.5a.5.5 0 0 0-.64 0l-6 5.5c-.2.18-.32.44-.32.73V14a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5z"/><path d="M7.293 1.293a1 1 0 0 1 1.414 0l6.647 6.646a.5.5 0 0 1-.708.708L8 2.207 1.354 8.646a.5.5 0 1 1-.708-.708L7.293 1.293z"/></svg>
                홈
            </a>
            <a href="/projects/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-folder-fill mr-2" viewBox="0 0 16 16"><path d="M9.828 3h3.982a2 2 0 0 1 1.992 2.181l-.637 7A2 2 0 0 1 13.174 14H2.826a2 2 0 0 1-1.991-1.819l-.637-7a1.99 1.99 0 0 1 .342-1.31L.5 3a2 2 0 0 1 2-2h3.672a2 2 0 0 1 1.414.586l.828.828A2 2 0 0 0 9.828 3zm-8.322.12C1.72 3.042 1.95 3 2.19 3h5.396l-.707-.707A1 1 0 0 0 6.172 2H2.5a1 1 0 0 0-1 .981l.006.139z"/></svg>
                프로젝트
            </a>
            <a href="/blog/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square mr-2" viewBox="0 0 16 16">
                    <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                    <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                </svg>
                블로그
            </a>
            <a href="/jobs/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-briefcase-fill mr-2" viewBox="0 0 16 16">
                    <path d="M6.5 1A1.5 1.5 0 0 0 5 2.5V3H1.5A1.5 1.5 0 0 0 0 4.5v1.384l7.614 2.03a1.5 1.5 0 0 0 .772 0L16 5.884V4.5A1.5 1.5 0 0 0 14.5 3H11v-.5A1.5 1.5 0 0 0 9.5 1h-3zm0 1h3a.5.5 0 0 1 .5.5V3H6v-.5a.5.5 0 0 1 .5-.5z"/>
                    <path d="M0 12.5A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5V6.85L8.129 8.947a.5.5 0 0 1-.258 0L0 6.85v5.65z"/>
                </svg>
                채용 공고
            </a>
            <a href="/courses/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-book-fill mr-2" viewBox="0 0 16 16">
                    <path d="M8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783z"/>
                </svg>
                수강 정보
            </a>
            <a href="/cover-letter/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-primary hover:text-primary-hover">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-text-fill mr-2" viewBox="0 0 16 16">
                    <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0M9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1M4.5 9a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1zM4.5 11a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1zM4.5 13a.5.5 0 0 1 0-1h4a.5.5 0 0 1 0 1z"/>
                </svg>
                자기소개서
            </a>
            <a href="/algorithms/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-text-fill mr-2" viewBox="0 0 16 16">
                    <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0M9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1M4.5 9a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1zM4.5 11a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1zM4.5 13a.5.5 0 0 1 0-1h4a.5.5 0 0 1 0 1z"/>
                </svg>
                알고리즘
            </a>
            <a href="/resume/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">
                 <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-person-fill mr-2" viewBox="0 0 16 16"><path d="M12 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zm-1 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm-3 4c2.623 0 4.146.826 5 1.755V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-1.245C3.854 11.825 5.377 11 8 11z"/></svg>
                이력서
            </a>
        </div>
        <div class="navbar-user">
             <div class="font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#808080] flex items-center">
            <?php if (isset($_SESSION['email'])): ?>
                <span><?php echo htmlspecialchars($_SESSION['email']); ?> 님 환영합니다.</span>
                <a href="/login/logout.php" title="로그아웃" class="ml-4 text-gray-600 hover:text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
                        <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                    </svg>
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

            <?php if (!empty($page_success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($page_success_message); ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($page_error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $page_error_message;?></span>
                </div>
            <?php endif; ?>

            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-700 mb-4">나의 답변 목록</h2>
                <?php if (count($user_answers) > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($user_answers as $ans): ?>
                            <div class="bg-white p-5 rounded-lg shadow-md flex flex-col justify-between">
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">
                                        <?php echo htmlspecialchars($ans['organization_name']); ?> (<?php echo htmlspecialchars($ans['organization_type']); ?>) | <?php echo htmlspecialchars($ans['category_name']); ?>
                                    </p>
                                    <h3 class="font-semibold text-gray-800 mb-2 truncate" title="<?php echo htmlspecialchars($ans['question']); ?>">
                                        <?php echo htmlspecialchars($ans['question']); ?>
                                    </h3>
                                    <div class="text-sm text-gray-600 mb-3 leading-relaxed card-answer-text custom-scrollbar">
                                        <?php echo nl2br(htmlspecialchars($ans['answer'])); ?>
                                    </div>
                                </div>
                                <div class="mt-auto pt-3 border-t border-gray-200">
                                    <p class="text-xs text-gray-400 mb-2">마지막 수정: <?php echo date('Y-m-d H:i', strtotime($ans['updated_at'])); ?></p>
                                    <div class="flex justify-end space-x-2">
                                        <a href="/cover-letter/create_answer.php?question_id=<?php echo $ans['question_id']; ?>" class="btn btn-sm bg-blue-500 hover:bg-blue-600 text-white">수정</a>
                                        <form method="POST" action="/cover-letter/index.php" onsubmit="return confirm('정말로 이 답변을 삭제하시겠습니까?');" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_answer">
                                            <input type="hidden" name="answer_id" value="<?php echo $ans['answer_id']; ?>">
                                            <button type="submit" class="btn btn-sm bg-red-500 hover:bg-red-600 text-white">삭제</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                     <?php if (empty($page_error_message) || strpos($page_error_message, "작성한 답변 목록을 불러오는 중 오류가 발생했습니다.") === false) : ?>
                        <div class="bg-white p-6 rounded-lg shadow-md text-center">
                            <p class="text-gray-500">아직 작성한 답변이 없습니다. 아래 목록에서 질문을 선택하여 답변을 작성해보세요.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="bg-white p-6 rounded-lg shadow mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="section-title mb-0">커스텀 질문 및 답변</h2>
                    <a href="/cover-letter/create_custom_question.php" class="btn btn-primary">
                        커스텀 질문 등록하기
                    </a>
                </div>
                <?php if (isset($custom_questions_list_error)): ?>
                    <p class="text-red-500"><?php echo htmlspecialchars($custom_questions_list_error); ?></p>
                <?php elseif (empty($custom_questions_list)): ?>
                    <p class="text-gray-600 py-4">등록된 커스텀 질문이 없습니다. 질문을 추가해보세요.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">기관/회사명</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">카테고리</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">질문</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">답변</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">관리</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($custom_questions_list as $cq): ?>
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 w-5"><?php echo htmlspecialchars($cq['custom_orgainzation_name'] ?: '-'); ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 w-3"><?php echo htmlspecialchars($cq['custom_category_name'] ?: '미분류'); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><div class="max-w-xs md:max-w-md lg:max-w-lg break-words"><?php echo nl2br(htmlspecialchars($cq['custom_question_text'])); ?></div></td>
                                        <td class="px-4 py-3 text-sm text-gray-700 text-center">
                                            <?php if (!empty($cq['custom_answer_text'])): ?>
                                                <div class="card-answer-text custom-scrollbar mb-1"><?php echo nl2br(htmlspecialchars($cq['custom_answer_text'])); ?></div>
                                            <?php else: ?>
                                                <a href="/cover-letter/create_custom_answer.php?custom_question_id=<?php echo $cq['custom_question_id']; ?>" class="btn btn-primary btn-sm text-center">답변하기</a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium w-3">
                                            <form action="/cover-letter/index.php" method="POST" onsubmit="return confirm('정말로 이 질문과 관련 답변을 모두 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.');" style="display:inline;">
                                                <input type="hidden" name="action" value="delete_custom_question">
                                                <input type="hidden" name="custom_question_id_to_delete" value="<?php echo $cq['custom_question_id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-800">삭제</button>
                                            </form>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex justify-between items-center">
                    <h2 class="section-title">질문 목록 및 답변 관리</h2>
            </div>

            <?php if (isset($questions_list_error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">오류:</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($questions_list_error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (empty($questions_list_error)):?>
                <div class="bg-white p-5 rounded-lg shadow-md table-responsive">
                    <?php if (count($questions) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>회사/기관</th>
                                    <th>유형</th>
                                    <th>카테고리</th>
                                    <th>질문</th>
                                    <th>관리</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($questions as $question): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($question['organization_name']); ?></td>
                                        <td><?php echo htmlspecialchars($question['organization_type']); ?></td>
                                        <td><?php echo htmlspecialchars($question['category_name']); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($question['question'])); ?></td>
                                        <td>
                                            <a href="/cover-letter/create_answer.php?question_id=<?php echo $question['question_id']; ?>" class="btn btn-primary">답변 작성/수정</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-center text-gray-500 py-5">등록된 자기소개서 질문이 없습니다.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>