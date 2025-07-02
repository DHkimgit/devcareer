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
$page_error_message = '';
$total_solved_problems = 0;
$last_solved_date_str = "아직 해결한 문제가 없습니다.";

$boj_problems = [];
$programmers_problems = [];

try {
    $stmt_boj_summary = $pdo->prepare("SELECT COUNT(*) as count, MAX(created_at) as last_solved FROM boj_problem WHERE user_id = :user_id");
    $stmt_boj_summary->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_boj_summary->execute();
    $boj_summary = $stmt_boj_summary->fetch(PDO::FETCH_ASSOC);

    $stmt_programmers_summary = $pdo->prepare("SELECT COUNT(*) as count, MAX(created_at) as last_solved FROM programmers_problem WHERE user_id = :user_id");
    $stmt_programmers_summary->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_programmers_summary->execute();
    $programmers_summary = $stmt_programmers_summary->fetch(PDO::FETCH_ASSOC);

    $total_solved_problems = ($boj_summary['count'] ?? 0) + ($programmers_summary['count'] ?? 0);

    $boj_last_solved_ts = $boj_summary['last_solved'] ? strtotime($boj_summary['last_solved']) : null;
    $programmers_last_solved_ts = $programmers_summary['last_solved'] ? strtotime($programmers_summary['last_solved']) : null;

    $last_solved_ts = null;
    if ($boj_last_solved_ts && $programmers_last_solved_ts) {
        $last_solved_ts = max($boj_last_solved_ts, $programmers_last_solved_ts);
    } elseif ($boj_last_solved_ts) {
        $last_solved_ts = $boj_last_solved_ts;
    } elseif ($programmers_last_solved_ts) {
        $last_solved_ts = $programmers_last_solved_ts;
    }

    if ($last_solved_ts) {
        $last_solved_date_str = date('Y-m-d H:i', $last_solved_ts);
    }

    $stmt_boj = $pdo->prepare("SELECT id, problem_number, title, summary, site_url, created_at, updated_at FROM boj_problem WHERE user_id = :user_id ORDER BY updated_at DESC");
    $stmt_boj->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_boj->execute();
    $boj_problems = $stmt_boj->fetchAll(PDO::FETCH_ASSOC);

    $stmt_programmers = $pdo->prepare("SELECT id, problem_number, title, summary, level, site_url, created_at, updated_at FROM programmers_problem WHERE user_id = :user_id ORDER BY updated_at DESC");
    $stmt_programmers->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_programmers->execute();
    $programmers_problems = $stmt_programmers->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Algorithm page DB Error: " . $e->getMessage());
    $page_error_message = "데이터를 불러오는 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.";
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>알고리즘 풀이 기록</title>
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
        .btn-sm { padding: 0.25rem 0.75rem; font-size: 0.75rem; line-height: 1.5; border-radius: 0.2rem; } /* 작은 버튼용 */
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
<body class="bg-gray-100">
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
                    <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
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
            <a href="/cover-letter/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-text-fill mr-2" viewBox="0 0 16 16">
                    <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0M9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1M4.5 9a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1zM4.5 11a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1zM4.5 13a.5.5 0 0 1 0-1h4a.5.5 0 0 1 0 1z"/>
                </svg>
                자기소개서
            </a>
            <a href="/cover-letter/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-primary hover:text-primary-hover">
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
        <?php display_sidebar('/algorithms/index.php'); ?>
        <div class="main-content">
            <h1 class="text-3xl font-bold text-gray-800 mb-8 text-primary">알고리즘 풀이 기록</h1>

            <?php if (!empty($page_error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($page_error_message); ?></span>
                </div>
            <?php endif; ?>

            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h2 class="text-xl font-semibold text-gray-700 mb-4">풀이 현황</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">총 푼 문제 수</p>
                        <p class="text-2xl font-bold text-primary"><?php echo $total_solved_problems; ?>개</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">최근 문제 해결일</p>
                        <p class="text-lg text-gray-700"><?php echo htmlspecialchars($last_solved_date_str); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="section-title text-2xl font-semibold text-gray-800 mb-0 text-primary">백준 문제</h2>
                    <a href="/algorithms/create_boj_problem.php" class="btn btn-primary">
                        BOJ 문제 등록하기
                    </a>
                </div>
                <?php if (!empty($boj_problems)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($boj_problems as $problem): ?>
                            <div class="bg-gray-50 p-4 rounded-lg shadow hover:shadow-lg transition-shadow duration-200 ease-in-out flex flex-col h-full">
                                <div class="flex-grow">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                        <a href="<?php echo htmlspecialchars($problem['site_url'] ?: '#'); ?>" target="_blank" class="hover:text-primary">
                                            <?php echo htmlspecialchars($problem['title']) . ' (' . htmlspecialchars($problem['problem_number']) . ')'; ?>
                                        </a>
                                    </h3>
                                    <?php if (!empty($problem['summary'])): ?>
                                    <div class="text-sm text-gray-600 mb-3 leading-relaxed card-summary-text custom-scrollbar">
                                        <?php echo nl2br(htmlspecialchars($problem['summary'])); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-auto pt-3 border-t border-gray-200">
                                    <p class="text-xs text-gray-400 mb-2">마지막 수정: <?php echo date('Y-m-d H:i', strtotime($problem['updated_at'])); ?></p>
                                    <div class="flex justify-end space-x-2">
                                        <a href="/algorithms/view_boj_solution.php?id=<?php echo $problem['id']; ?>" class="btn btn-sm bg-blue-500 hover:bg-blue-600 text-white">풀이 보기</a>
                                        <a href="/algorithms/edit_boj_problem.php?id=<?php echo $problem['id']; ?>" class="btn btn-sm bg-yellow-500 hover:bg-yellow-600 text-white">수정</a>
                                        <a href="/algorithms/delete_boj_problem.php?id=<?php echo $problem['id']; ?>" class="btn btn-sm bg-red-500 hover:bg-red-600 text-white" onclick="return confirm('정말로 이 문제를 삭제하시겠습니까?');">삭제</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">아직 등록된 백준 문제가 없습니다.</p>
                <?php endif; ?>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="section-title text-2xl font-semibold text-gray-800 mb-0 text-primary">프로그래머스 문제</h2>
                    <a href="/algorithms/create_programmers_problem.php" class="btn btn-primary">
                        Programmers 문제 등록하기
                    </a>
                </div>
                <?php if (!empty($programmers_problems)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($programmers_problems as $problem): ?>
                            <div class="bg-gray-50 p-4 rounded-lg shadow hover:shadow-lg transition-shadow duration-200 ease-in-out flex flex-col h-full">
                                <div class="flex-grow">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                        <a href="<?php echo htmlspecialchars($problem['site_url'] ?: '#'); ?>" target="_blank" class="hover:text-primary">
                                            <?php echo htmlspecialchars($problem['title']); ?>
                                        </a>
                                    </h3>
                                    <p class="text-sm text-gray-500 mb-1">ID: <?php echo htmlspecialchars($problem['problem_number']); ?></p>
                                    <?php if (!empty($problem['level'])): ?>
                                        <p class="text-sm text-gray-500 mb-1">Level: <?php echo htmlspecialchars($problem['level']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($problem['summary'])): ?>
                                    <div class="text-sm text-gray-600 mt-2 mb-3 leading-relaxed card-summary-text custom-scrollbar">
                                        <?php echo nl2br(htmlspecialchars($problem['summary'])); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-auto pt-3 border-t border-gray-200">
                                    <p class="text-xs text-gray-400 mb-2">마지막 수정: <?php echo date('Y-m-d H:i', strtotime($problem['updated_at'])); ?></p>
                                    <div class="flex justify-end space-x-2">
                                        <a href="/algorithms/view_programmers_solution.php?id=<?php echo $problem['id']; ?>" class="btn btn-sm bg-green-500 hover:bg-green-600 text-white">풀이 보기</a>
                                        <a href="/algorithms/edit_programmers_problem.php?id=<?php echo $problem['id']; ?>" class="btn btn-sm bg-yellow-500 hover:bg-yellow-600 text-white">수정</a>
                                        <a href="/algorithms/delete_programmers_problem.php?id=<?php echo $problem['id']; ?>" class="btn btn-sm bg-red-500 hover:bg-red-600 text-white" onclick="return confirm('정말로 이 문제를 삭제하시겠습니까?');">삭제</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500">아직 등록된 프로그래머스 문제가 없습니다.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>
</body>
</html>