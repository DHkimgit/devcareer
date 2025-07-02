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
$categories = [];
$page_success_message = '';
$page_error_message = '';

try {
    $stmt_categories = $pdo->query("SELECT id, name FROM cover_letter_question_category ORDER BY name ASC");
    $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("카테고리 로드 오류: " . $e->getMessage());
    $page_error_message = "카테고리 목록을 불러오는 중 오류가 발생했습니다.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_custom_question') {
    $custom_organization = trim($_POST['custom_organization'] ?? '');
    $custom_category_id = !empty($_POST['custom_category_id']) ? (int)$_POST['custom_category_id'] : null;
    $custom_question_text = trim($_POST['custom_question_text'] ?? '');

    if (empty($custom_question_text)) {
        $page_error_message = "질문 내용을 입력해주세요.";
    } else {
        try {
            $sql_insert_custom_question = "INSERT INTO cover_letter_custom_question (user_id, category_id, organization, question, created_at, updated_at) 
                                           VALUES (:user_id, :category_id, :organization, :question, NOW(), NOW())";
            $stmt_insert = $pdo->prepare($sql_insert_custom_question);
            $stmt_insert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_insert->bindParam(':category_id', $custom_category_id, PDO::PARAM_INT);
            if ($custom_category_id === null) {
                $stmt_insert->bindValue(':category_id', null, PDO::PARAM_NULL);
            } else {
                $stmt_insert->bindParam(':category_id', $custom_category_id, PDO::PARAM_INT);
            }
            $stmt_insert->bindParam(':organization', $custom_organization, PDO::PARAM_STR);
            $stmt_insert->bindParam(':question', $custom_question_text, PDO::PARAM_STR);

            if ($stmt_insert->execute()) {
                $_SESSION['success_message'] = "커스텀 질문이 성공적으로 등록되었습니다.";
                header('Location: /cover-letter/index.php');
                exit;
            } else {
                $page_error_message = "커스텀 질문 등록 중 오류가 발생했습니다.";
            }
        } catch (PDOException $e) {
            error_log("커스텀 질문 등록 오류: " . $e->getMessage());
            $page_error_message = "커스텀 질문 등록 중 데이터베이스 오류가 발생했습니다. " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>커스텀 질문 등록 - 자기소개서 관리</title>
    <script src="/tailwind.js"></script>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.css" rel="stylesheet">
    <style>
        body { font-family: 'Pretendard', sans-serif; margin: 0; padding: 0; background-color: #f7f7fb; }
        .content-wrapper { display: flex; }
        .main-content { margin-left: 260px; padding: 20px; width: calc(100% - 260px); padding-top: 100px; }
        .navbar { background-color: white; padding: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 1.5rem; font-weight: bold; background: linear-gradient(90deg, #5F43FF 11.64%, #8243FF 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .navbar-user { font-size: 0.9rem; color: #505050; }
        .btn { padding: 0.5rem 1rem; border-radius: 0.375rem; font-weight: 600; transition: background-color 0.3s ease; text-decoration: none; display: inline-block; font-size: 0.875rem; }
        .btn-primary { background-color: #5F43FF; color: white; }
        .btn-primary:hover { background-color: #8243FF; }
        .btn-sm { padding: 0.25rem 0.75rem; font-size: 0.75rem; line-height: 1.5; border-radius: 0.2rem; };
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
            <h1 class="text-2xl font-bold text-gray-800 mb-6">커스텀 질문 등록</h1>

            <?php if (!empty($page_success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($page_success_message); ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($page_error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $page_error_message; ?></span>
                </div>
            <?php endif; ?>

            <div class="bg-white p-6 rounded-lg shadow mb-8">
                <form action="/cover-letter/create_custom_question.php" method="POST">
                    <input type="hidden" name="action" value="add_custom_question">
                    <div class="mb-4">
                        <label for="custom_organization" class="block text-sm font-medium text-gray-700 mb-1">기관/회사명</label>
                        <input type="text" name="custom_organization" id="custom_organization" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" placeholder="예: 삼성전자, 네이버" value="<?php echo htmlspecialchars($_POST['custom_organization'] ?? ''); ?>">
                    </div>
                    <div class="mb-4">
                        <label for="custom_category_id" class="block text-sm font-medium text-gray-700 mb-1">카테고리</label>
                        <select name="custom_category_id" id="custom_category_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                            <option value="">카테고리 선택 (선택 사항)</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo (isset($_POST['custom_category_id']) && $_POST['custom_category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="custom_question_text" class="block text-sm font-medium text-gray-700 mb-1">질문 내용 <span class="text-red-500">*</span></label>
                        <textarea name="custom_question_text" id="custom_question_text" rows="4" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" placeholder="등록할 질문 내용을 입력하세요."><?php echo htmlspecialchars($_POST['custom_question_text'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-full sm:w-auto">커스텀 질문 등록하기</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>