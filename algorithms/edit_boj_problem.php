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
$problem = null;
$problem_categories = [];
$all_categories = [];
$error_message = '';
$success_message = '';

try {
    $stmt_all_categories = $pdo->query("SELECT id, name FROM algorithm_category ORDER BY name ASC");
    $all_categories = $stmt_all_categories->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Category Fetch DB Error: " . $e->getMessage());
    $error_message = "카테고리 정보를 불러오는 중 오류가 발생했습니다.";
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $error_message = "문제 ID가 제공되지 않았습니다.";
} else {
    $problem_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$problem_id) {
        $error_message = "유효하지 않은 문제 ID입니다.";
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            try {
                $stmt_problem = $pdo->prepare("SELECT * FROM boj_problem WHERE id = :id AND user_id = :user_id");
                $stmt_problem->bindParam(':id', $problem_id, PDO::PARAM_INT);
                $stmt_problem->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt_problem->execute();
                $problem = $stmt_problem->fetch(PDO::FETCH_ASSOC);

                if (!$problem) {
                    $error_message = "해당 문제를 찾을 수 없거나 접근 권한이 없습니다.";
                } else {
                    $stmt_problem_cats = $pdo->prepare("SELECT category_id FROM boj_problem_category WHERE boj_problem_id = :problem_id");
                    $stmt_problem_cats->bindParam(':problem_id', $problem['id'], PDO::PARAM_INT);
                    $stmt_problem_cats->execute();
                    $problem_categories = $stmt_problem_cats->fetchAll(PDO::FETCH_COLUMN);
                }
            } catch (PDOException $e) {
                error_log("BOJ Problem Edit Fetch DB Error: " . $e->getMessage());
                $error_message = "문제 정보를 불러오는 중 오류가 발생했습니다.";
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $problem_id && !$error_message) {
    $title = trim($_POST['title'] ?? '');
    $problem_number = trim($_POST['problem_number'] ?? '');
    $site_url = trim($_POST['site_url'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $solution = trim($_POST['solution'] ?? '');
    $selected_category_ids = $_POST['categories'] ?? [];

    if (empty($title) || empty($problem_number)) {
        $error_message = "문제 제목과 문제 번호는 필수입니다.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt_update_problem = $pdo->prepare("
                UPDATE boj_problem 
                SET title = :title, problem_number = :problem_number, site_url = :site_url, summary = :summary, solution = :solution
                WHERE id = :id AND user_id = :user_id
            ");
            $stmt_update_problem->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt_update_problem->bindParam(':problem_number', $problem_number, PDO::PARAM_STR);
            $stmt_update_problem->bindParam(':site_url', $site_url, PDO::PARAM_STR);
            $stmt_update_problem->bindParam(':summary', $summary, PDO::PARAM_STR);
            $stmt_update_problem->bindParam(':solution', $solution, PDO::PARAM_STR);
            $stmt_update_problem->bindParam(':id', $problem_id, PDO::PARAM_INT);
            $stmt_update_problem->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_update_problem->execute();

            $stmt_delete_cats = $pdo->prepare("DELETE FROM boj_problem_category WHERE boj_problem_id = :problem_id");
            $stmt_delete_cats->bindParam(':problem_id', $problem_id, PDO::PARAM_INT);
            $stmt_delete_cats->execute();

            if (!empty($selected_category_ids)) {
                $stmt_insert_cat = $pdo->prepare("INSERT INTO boj_problem_category (boj_problem_id, category_id) VALUES (:problem_id, :category_id)");
                foreach ($selected_category_ids as $category_id) {
                    if (filter_var($category_id, FILTER_VALIDATE_INT)) {
                        $stmt_insert_cat->bindParam(':problem_id', $problem_id, PDO::PARAM_INT);
                        $stmt_insert_cat->bindParam(':category_id', $category_id, PDO::PARAM_INT);
                        $stmt_insert_cat->execute();
                    }
                }
            }
            
            $pdo->commit();
            $success_message = "문제가 성공적으로 수정되었습니다.";

            $stmt_problem = $pdo->prepare("SELECT * FROM boj_problem WHERE id = :id AND user_id = :user_id");
            $stmt_problem->bindParam(':id', $problem_id, PDO::PARAM_INT);
            $stmt_problem->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_problem->execute();
            $problem = $stmt_problem->fetch(PDO::FETCH_ASSOC);

            $stmt_problem_cats = $pdo->prepare("SELECT category_id FROM boj_problem_category WHERE boj_problem_id = :problem_id");
            $stmt_problem_cats->bindParam(':problem_id', $problem['id'], PDO::PARAM_INT);
            $stmt_problem_cats->execute();
            $problem_categories = $stmt_problem_cats->fetchAll(PDO::FETCH_COLUMN);

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("BOJ Problem Update DB Error: " . $e->getMessage());
            $error_message = "문제 수정 중 오류가 발생했습니다: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOJ 문제 풀이 수정</title>
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
        .form-textarea { min-height: 150px; font-family: 'Courier New', Courier, monospace; }
        .form-checkbox-group { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; }
        .form-checkbox-label { display: flex; align-items: center; font-size: 0.9rem; color: #374151; padding: 0.5rem 0.75rem; border: 1px solid #D1D5DB; border-radius: 0.375rem; cursor: pointer; }
        .form-checkbox-label input { margin-right: 0.5rem; }
        .form-checkbox-label:has(input:checked) { background-color: #e0e7ff; border-color: #5F43FF; color: #4338ca; }
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
            <a href="/algorithms/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-primary hover:text-primary-hover">알고리즘</a>
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
        <?php display_sidebar('/algorithms/index.php'); ?>
        <div class="main-content">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">BOJ 문제 풀이 수정</h1>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
                     <div class="mt-2">
                        <a href="/algorithms/view_boj_solution.php?id=<?php echo $problem_id; ?>" class="text-sm text-blue-600 hover:underline">수정된 문제 보기</a>
                        <span class="mx-2 text-gray-400">|</span>
                        <a href="/algorithms/index.php" class="text-sm text-blue-600 hover:underline">목록으로 돌아가기</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($problem): ?>
                <form action="/algorithms/edit_boj_problem.php?id=<?php echo $problem['id']; ?>" method="POST" class="bg-white p-6 rounded-lg shadow-md">
                    <input type="hidden" name="id" value="<?php echo $problem['id']; ?>">
                    
                    <div class="mb-4">
                        <label for="title" class="form-label">문제 제목 <span class="text-red-500">*</span></label>
                        <input type="text" id="title" name="title" class="form-input" value="<?php echo htmlspecialchars($problem['title']); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label for="problem_number" class="form-label">문제 번호 <span class="text-red-500">*</span></label>
                        <input type="text" id="problem_number" name="problem_number" class="form-input" value="<?php echo htmlspecialchars($problem['problem_number']); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label for="site_url" class="form-label">문제 링크 (URL)</label>
                        <input type="url" id="site_url" name="site_url" class="form-input" value="<?php echo htmlspecialchars($problem['site_url']); ?>">
                    </div>

                    <div class="mb-4">
                        <label for="categories" class="form-label">카테고리</label>
                        <div class="form-checkbox-group">
                            <?php foreach ($all_categories as $category): ?>
                                <label class="form-checkbox-label">
                                    <input type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>" 
                                        <?php echo in_array($category['id'], $problem_categories) ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                         <p class="text-xs text-gray-500 mt-1">선택된 카테고리가 없으면, 기존 카테고리 연결이 모두 해제됩니다.</p>
                    </div>

                    <div class="mb-4">
                        <label for="summary" class="form-label">요약</label>
                        <textarea id="summary" name="summary" class="form-textarea !font-pretendard"><?php echo htmlspecialchars($problem['summary']); ?></textarea>
                    </div>

                    <div class="mb-6">
                        <label for="solution" class="form-label">풀이 (코드)</label>
                        <textarea id="solution" name="solution" class="form-textarea"><?php echo htmlspecialchars($problem['solution']); ?></textarea>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <a href="/algorithms/view_boj_solution.php?id=<?php echo $problem['id']; ?>" class="text-sm text-gray-600 hover:underline">&laquo; 취소하고 문제 보기로</a>
                        <button type="submit" class="bg-primary text-white px-6 py-2 rounded hover:bg-primary-hover">저장하기</button>
                    </div>
                </form>
            <?php elseif (!$error_message):?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline">수정할 문제를 불러올 수 없습니다. ID를 확인해주세요.</span>
                     <div class="mt-4">
                         <a href="/algorithms/index.php" class="text-sm text-blue-600 hover:underline">알고리즘 목록으로 돌아가기</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>