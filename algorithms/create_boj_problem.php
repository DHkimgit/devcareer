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
$error_message = '';
$success_message = '';
$categories = [];

try {
    $stmt_categories = $pdo->query("SELECT id, name FROM algorithm_category ORDER BY name ASC");
    $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $error_message = "카테고리 목록을 불러오는 중 오류가 발생했습니다.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $problem_number = trim($_POST['problem_number']);
    $title = trim($_POST['title']);
    $summary = trim($_POST['summary']);
    $solution = trim($_POST['solution']);
    $site_url = trim($_POST['site_url']);
    $selected_categories = isset($_POST['categories']) ? $_POST['categories'] : [];

    if (empty($problem_number) || empty($title)) {
        $error_message = "문제 번호와 제목은 필수 항목입니다.";
    } else if (!is_numeric($problem_number)) {
        $error_message = "문제 번호는 숫자여야 합니다.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt_problem = $pdo->prepare("INSERT INTO boj_problem (problem_number, title, user_id, summary, solution, site_url) VALUES (:problem_number, :title, :user_id, :summary, :solution, :site_url)");
            $stmt_problem->bindParam(':problem_number', $problem_number, PDO::PARAM_INT);
            $stmt_problem->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt_problem->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_problem->bindParam(':summary', $summary, PDO::PARAM_STR);
            $stmt_problem->bindParam(':solution', $solution, PDO::PARAM_STR);
            $stmt_problem->bindParam(':site_url', $site_url, PDO::PARAM_STR);
            $stmt_problem->execute();
            $boj_problem_id = $pdo->lastInsertId();

            if (!empty($selected_categories) && $boj_problem_id) {
                $stmt_category_link = $pdo->prepare("INSERT INTO boj_problem_category (boj_problem_id, category_id) VALUES (:boj_problem_id, :category_id)");
                foreach ($selected_categories as $category_id) {
                    $stmt_category_link->bindParam(':boj_problem_id', $boj_problem_id, PDO::PARAM_INT);
                    $stmt_category_link->bindParam(':category_id', $category_id, PDO::PARAM_INT);
                    $stmt_category_link->execute();
                }
            }

            $pdo->commit();
            $success_message = "백준 문제가 성공적으로 등록되었습니다.";
            header("Location: /algorithms/index.php?status=boj_success");
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                 error_log("BOJ Problem Creation DB Error (Unique Constraint): " . $e->getMessage());
                $error_message = "이미 등록된 문제 번호입니다. 다른 번호를 입력해주세요.";
            } else {
                error_log("BOJ Problem Creation DB Error: " . $e->getMessage());
                $error_message = "문제 등록 중 오류가 발생했습니다. 다시 시도해주세요.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOJ 문제 등록</title>
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
        .form-input { width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 0.25rem; margin-bottom: 1rem; font-size: 0.875rem; }
        .form-textarea { width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 0.25rem; margin-bottom: 1rem; font-size: 0.875rem; min-height: 100px; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333; font-size: 0.875rem;}
        .category-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 0.5rem; margin-bottom: 1rem; max-height: 200px; overflow-y: auto; border: 1px solid #eee; padding: 10px; border-radius: 5px;}
        .category-item label { font-size: 0.875rem; }
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
            <h1 class="text-2xl font-bold text-gray-800 mb-6">새로운 BOJ 문제 등록</h1>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                 <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>

            <form action="create_boj_problem.php" method="POST" class="bg-white p-6 rounded-lg shadow-md">
                <div class="mb-4">
                    <label for="problem_number" class="form-label">문제 번호 <span class="text-red-500">*</span></label>
                    <input type="number" id="problem_number" name="problem_number" class="form-input" required>
                </div>
                <div class="mb-4">
                    <label for="title" class="form-label">제목 <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" class="form-input" required>
                </div>
                <div class="mb-4">
                    <label for="summary" class="form-label">요약</label>
                    <textarea id="summary" name="summary" class="form-textarea"></textarea>
                </div>
                <div class="mb-4">
                    <label for="solution" class="form-label">풀이</label>
                    <textarea id="solution" name="solution" class="form-textarea"></textarea>
                </div>
                <div class="mb-4">
                    <label for="site_url" class="form-label">문제 링크 (Site URL)</label>
                    <input type="url" id="site_url" name="site_url" class="form-input" placeholder="https://www.acmicpc.net/problem/xxxx">
                </div>

                <div class="mb-6">
                    <label class="form-label">카테고리</label>
                    <?php if (!empty($categories)): ?>
                        <div class="category-grid custom-scrollbar">
                            <?php foreach ($categories as $category): ?>
                                <div class="category-item">
                                    <input type="checkbox" id="category_<?php echo $category['id']; ?>" name="categories[]" value="<?php echo $category['id']; ?>" class="mr-2">
                                    <label for="category_<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500">등록된 카테고리가 없습니다. <a href="/algorithms/manage_categories.php" class="text-primary hover:underline">카테고리 관리</a></p>
                    <?php endif; ?>
                </div>

                <div class="flex justify-end">
                    <a href="/algorithms/index.php" class="btn bg-gray-300 hover:bg-gray-400 text-gray-800 mr-2">취소</a>
                    <button type="submit" class="btn btn-primary">문제 등록</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>