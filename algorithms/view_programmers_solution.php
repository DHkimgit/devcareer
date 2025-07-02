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
$error_message = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $error_message = "문제 ID가 제공되지 않았습니다.";
} else {
    $problem_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$problem_id) {
        $error_message = "유효하지 않은 문제 ID입니다.";
    } else {
        try {
            $stmt_problem = $pdo->prepare("SELECT * FROM programmers_problem WHERE id = :id AND user_id = :user_id");
            $stmt_problem->bindParam(':id', $problem_id, PDO::PARAM_INT);
            $stmt_problem->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_problem->execute();
            $problem = $stmt_problem->fetch(PDO::FETCH_ASSOC);

            if (!$problem) {
                $error_message = "해당 문제를 찾을 수 없거나 접근 권한이 없습니다.";
            }
        } catch (PDOException $e) {
            error_log("Programmers Problem View DB Error: " . $e->getMessage());
            $error_message = "문제 정보를 불러오는 중 오류가 발생했습니다.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programmers 문제 풀이 보기</title>
    <script src="/tailwind.js"></script>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.css" rel="stylesheet">
    <style>
        body { font-family: 'Pretendard', sans-serif; margin: 0; padding: 0; background-color: #f7f7fb; }
        .content-wrapper { display: flex; }
        .main-content { margin-left: 260px; padding: 20px; width: calc(100% - 260px); padding-top: 100px; }
        .navbar { background-color: white; padding: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 1.5rem; font-weight: bold; background: linear-gradient(90deg, #5F43FF 11.64%, #8243FF 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; text-fill-color: transparent; }
        .navbar-user { font-size: 0.9rem; color: #505050; }
        .problem-section { margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #eee; }
        .problem-section h2 { font-size: 1.25rem; font-weight: 600; color: #333; margin-bottom: 0.5rem; }
        .problem-section p, .problem-section div { font-size: 0.95rem; color: #555; line-height: 1.6; white-space: pre-wrap; word-wrap: break-word; }
        .problem-section strong { color: #333; }
        .level-badge { display: inline-block; background-color: #d1fae5; color: #065f46; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.8rem; margin-left: 0.5rem;}
        .code-block { background-color: #f3f4f6; padding: 1rem; border-radius: 0.375rem; overflow-x: auto; font-family: 'Courier New', Courier, monospace; font-size: 0.9rem; margin-top: 0.5rem; }
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
            <?php if ($error_message): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                     <div class="mt-4">
                         <a href="/algorithms/index.php" class="text-sm text-blue-600 hover:underline">알고리즘 목록으로 돌아가기</a>
                    </div>
                </div>
            <?php elseif ($problem): ?>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <?php echo htmlspecialchars($problem['title']); ?>
                    <?php if (!empty($problem['level'])): ?>
                        <span class="level-badge">Level <?php echo htmlspecialchars($problem['level']); ?></span>
                    <?php endif; ?>
                </h1>
                <p class="text-gray-600 mb-6">Programmers 문제 ID: <?php echo htmlspecialchars($problem['problem_number']); ?></p>
                
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <?php if (!empty($problem['site_url'])): ?>
                    <div class="problem-section">
                        <h2>문제 링크</h2>
                        <p><a href="<?php echo htmlspecialchars($problem['site_url']); ?>" target="_blank" class="text-primary hover:underline"><?php echo htmlspecialchars($problem['site_url']); ?></a></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($problem['summary'])): ?>
                    <div class="problem-section">
                        <h2>요약</h2>
                        <p><?php echo nl2br(htmlspecialchars($problem['summary'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($problem['solution'])): ?>
                    <div class="problem-section">
                        <h2>풀이</h2>
                        <div class="code-block">
                           <pre><code><?php echo htmlspecialchars($problem['solution']); ?></code></pre>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                     <div class="mt-8 flex justify-between items-center">
                        <a href="/algorithms/index.php" class="text-sm text-blue-600 hover:underline">&laquo; 목록으로 돌아가기</a>
                        <a href="/algorithms/edit_programmers_problem.php?id=<?php echo $problem['id']; ?>" class="bg-primary text-white px-4 py-2 rounded hover:bg-primary-hover text-sm">수정하기</a>
                    </div>
                </div>
            <?php else: ?>
                 <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline">문제를 찾을 수 없습니다.</span>
                    <div class="mt-4">
                         <a href="/algorithms/index.php" class="text-sm text-blue-600 hover:underline">알고리즘 목록으로 돌아가기</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>