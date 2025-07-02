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
        $_SESSION['email'] = isset($_COOKIE['email']) ? $_COOKIE['email'] : '';
    }
    $user_id = $_SESSION['user_id'];
    $username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : (isset($_COOKIE['username']) ? htmlspecialchars($_COOKIE['username']) : '사용자');
}

$pdo = getPDO();
$projects = [];
$page_error_message = '';

try {
    $sql_projects = "
        SELECT 
            p.id AS project_id, 
            p.name AS project_name, 
            p.description AS project_description, 
            p.start_date, 
            p.end_date, 
            p.status AS project_status, 
            p.progress_percentage, 
            p.role AS project_role, 
            p.repo_url, 
            p.demo_url, 
            p.current_focus_issue,
            p.created_at AS project_created_at
        FROM projects p
        WHERE p.user_id = :user_id
        ORDER BY p.created_at DESC";
    $stmt_projects = $pdo->prepare($sql_projects);
    $stmt_projects->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_projects->execute();
    $projects_data = $stmt_projects->fetchAll(PDO::FETCH_ASSOC);

    foreach ($projects_data as $project_item) {
        // Fetch technologies
        $sql_tech = "
            SELECT pts.name, pts.badge_url 
            FROM project_technology_stack pts
            JOIN project_technology_mapping ptm ON pts.id = ptm.technology_id
            WHERE ptm.project_id = :project_id
            ORDER BY pts.name";
        $stmt_tech = $pdo->prepare($sql_tech);
        $stmt_tech->bindParam(':project_id', $project_item['project_id'], PDO::PARAM_INT);
        $stmt_tech->execute();
        $project_item['technologies'] = $stmt_tech->fetchAll(PDO::FETCH_ASSOC);

        // Fetch blog posts
        $sql_blog = "
            SELECT bp.id AS post_id, bp.title AS post_title, bp.created_at AS post_created_at
            FROM blog_post bp
            WHERE bp.project_id = :project_id AND bp.user_id = :user_id
            ORDER BY bp.created_at DESC";
        $stmt_blog = $pdo->prepare($sql_blog);
        $stmt_blog->bindParam(':project_id', $project_item['project_id'], PDO::PARAM_INT);
        $stmt_blog->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_blog->execute();
        $project_item['blog_posts'] = $stmt_blog->fetchAll(PDO::FETCH_ASSOC);
        
        $projects[] = $project_item;
    }

} catch (PDOException $e) {
    $page_error_message = "프로젝트 정보를 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
    error_log($page_error_message);
}

$page_title = '프로젝트 목록';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - DevCareer</title>
    <script src="/tailwind.js"></script>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Pretendard', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f7f7fb;
        }
        .content-wrapper {
            display: flex;
        }
        .main-content {
            margin-left: 260px; /* 사이드바 너비만큼 */
            padding: 20px;
            width: calc(100% - 260px);
            padding-top: 80px; /* 네비게이션 바 높이만큼 */
        }
        .navbar {
            background-color: white;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            background: linear-gradient(90deg, #5F43FF 11.64%, #8243FF 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
        }
        .navbar-user {
            font-size: 0.9rem;
            color: #505050;
        }
        .tech-badge img {
            height: 1.25rem; /* 20px */
            width: auto;
            margin-right: 0.375rem; /* 6px */
        }
        .progress-bar-bg { background-color: #e5e7eb; /* gray-200 */ }
        .progress-bar { background-color: #5F43FF; /* primary */ height: 100%; border-radius: 9999px; }

    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'pretendard': ['Pretendard', 'sans-serif'],
                    },
                    colors: {
                        'primary': '#5F43FF',
                        'primary-hover': '#8243FF',
                    }
                }
            }
        }
        function confirmDelete(projectId, projectName) {
            if (confirm("'" + projectName + "' 프로젝트를 정말 삭제하시겠습니까?")) {
                window.location.href = '/projects/delete_project.php?id=' + projectId;
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
            <a href="/projects/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-primary hover:text-primary-hover">
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
                 <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-briefcase-fill mr-2" viewBox="0 0 16 16"><path d="M6.5 1A1.5 1.5 0 0 0 5 2.5V3H1.5A1.5 1.5 0 0 0 0 4.5v1.384l7.614 2.03a1.5 1.5 0 0 0 .772 0L16 5.884V4.5A1.5 1.5 0 0 0 14.5 3H11v-.5A1.5 1.5 0 0 0 9.5 1h-3zm0 1h3a.5.5 0 0 1 .5.5V3H6v-.5a.5.5 0 0 1 .5-.5z"/><path d="M0 12.5A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5V6.85L8.129 8.947a.5.5 0 0 1-.258 0L0 6.85v5.65z"/></svg>
                채용 공고
            </a>
            <a href="/courses/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-book-fill mr-2" viewBox="0 0 16 16"><path d="M8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783z"/></svg>
                수강 정보
            </a>
            <a href="/cover-letter/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-text-fill mr-2" viewBox="0 0 16 16">
                    <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0M9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1M4.5 9a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1zM4.5 11a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1zM4.5 13a.5.5 0 0 1 0-1h4a.5.5 0 0 1 0 1z"/></svg>
                </svg>
                자기소개서
            </a>
            <a href="/algorithms/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-code-slash mr-2" viewBox="0 0 16 16"><path d="M10.478 1.647a.5.5 0 1 0-.956-.294l-4 13a.5.5 0 0 0 .956.294l4-13zM4.854 4.146a.5.5 0 0 1 0 .708L1.707 8l3.147 3.146a.5.5 0 0 1-.708.708l-3.5-3.5a.5.5 0 0 1 0-.708l3.5-3.5a.5.5 0 0 1 .708 0zm6.292 0a.5.5 0 0 0 0 .708L14.293 8l-3.147 3.146a.5.5 0 0 0 .708.708l3.5-3.5a.5.5 0 0 0 0-.708l3.5-3.5a.5.5 0 0 0-.708 0z"/></svg>
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
        <?php display_sidebar('/projects/index.php'); ?>
        <main class="main-content mt-5">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-primary"><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="/projects/create_project.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 0 1 1-1z" clip-rule="evenodd" />
                    </svg>
                    새 프로젝트 등록
                </a>
            </div>

            <?php if (!empty($page_error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">오류:</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($page_error_message); ?></span>
                </div>
            <?php endif; ?>

            <?php if (empty($projects) && empty($page_error_message)): ?>
                <div class="text-center py-12 bg-white shadow-md rounded-lg">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">등록된 프로젝트가 없습니다.</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        새로운 프로젝트를 추가하여 진행 상황을 관리하고 관련 기록을 남겨보세요.
                    </p>
                    <div class="mt-6">
                        <a href="/projects/create_project.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 0 1 1-1z" clip-rule="evenodd" />
                            </svg>
                            새 프로젝트 등록
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($projects as $project): ?>
                        <div class="bg-white shadow-xl rounded-lg p-6 flex flex-col justify-between hover:shadow-2xl transition-shadow duration-300">
                            <div>
                                <div class="flex justify-between items-start mb-3">
                                    <h2 class="text-xl font-bold text-primary truncate" title="<?php echo htmlspecialchars($project['project_name']); ?>">
                                        <?php echo htmlspecialchars($project['project_name']); ?>
                                    </h2>
                                    <span class="flex-shrink-0 px-2.5 py-0.5 text-xs font-semibold rounded-full
                                        <?php 
                                            $status_class = 'bg-gray-100 text-gray-800'; // Default
                                            if ($project['project_status'] === '진행중') $status_class = 'bg-blue-100 text-blue-800';
                                            elseif ($project['project_status'] === '완료') $status_class = 'bg-green-100 text-green-800';
                                            elseif ($project['project_status'] === '보류') $status_class = 'bg-yellow-100 text-yellow-800';
                                            echo $status_class;
                                        ?>">
                                        <?php echo htmlspecialchars($project['project_status']); ?>
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mb-3">
                                    기간: <?php echo $project['start_date'] ? date('Y.m.d', strtotime($project['start_date'])) : 'N/A'; ?> ~ <?php echo $project['end_date'] ? date('Y.m.d', strtotime($project['end_date'])) : '진행중'; ?>
                                </p>
                                <p class="text-sm text-gray-600 mb-4 h-20 overflow-y-auto custom-scrollbar">
                                    <?php echo nl2br(htmlspecialchars($project['project_description'] ?? '설명 없음')); ?>
                                </p>

                                <?php if (isset($project['progress_percentage'])): ?>
                                <div class="mb-4">
                                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                                        <span>진행률</span>
                                        <span><?php echo $project['progress_percentage']; ?>%</span>
                                    </div>
                                    <div class="w-full progress-bar-bg rounded-full h-2">
                                        <div class="progress-bar" style="width: <?php echo $project['progress_percentage']; ?>%"></div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($project['project_role'])): ?>
                                <p class="text-xs text-gray-500 mb-1"><strong>역할:</strong> <?php echo htmlspecialchars($project['project_role']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($project['current_focus_issue'])): ?>
                                <div class="bg-indigo-50 p-2 rounded-md my-3">
                                    <p class="text-xs text-indigo-700"><strong class="font-semibold">집중 사항:</strong> <?php echo htmlspecialchars($project['current_focus_issue']); ?></p>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($project['technologies'])): ?>
                                <div class="mb-4">
                                    <h4 class="text-xs font-semibold text-gray-700 mb-1.5">사용 기술:</h4>
                                    <div class="flex flex-wrap gap-1.5">
                                        <?php foreach ($project['technologies'] as $tech): ?>
                                            <span class="tech-badge inline-flex items-center px-2 py-0.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200 transition-colors">
                                                <?php if (!empty($tech['badge_url'])): ?>
                                                    <img src="<?php echo htmlspecialchars($tech['badge_url']); ?>" alt="<?php echo htmlspecialchars($tech['name']); ?>" class="h-4 w-auto mr-1">
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($tech['name']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div>
                                <div class="flex space-x-3 text-xs mb-4 mt-auto pt-4 border-t border-gray-200">
                                    <?php if (!empty($project['repo_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($project['repo_url']); ?>" target="_blank" class="text-primary hover:text-primary-hover font-medium inline-flex items-center">
                                            <i class="fab fa-github fa-fw mr-1"></i> Repository
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($project['demo_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($project['demo_url']); ?>" target="_blank" class="text-primary hover:text-primary-hover font-medium inline-flex items-center">
                                            <i class="fas fa-external-link-alt fa-fw mr-1"></i> Live Demo
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($project['blog_posts'])): ?>
                                <div class="border-t border-gray-200 pt-3">
                                    <h4 class="text-xs font-semibold text-gray-700 mb-1.5">블로그 게시글:</h4>
                                    <ul class="space-y-1 max-h-24 overflow-y-auto custom-scrollbar">
                                        <?php foreach ($project['blog_posts'] as $blog_post): ?>
                                            <li class="text-xs">
                                                <a href="/blog/view_post.php?id=<?php echo $blog_post['post_id']; ?>" class="text-indigo-600 hover:text-indigo-800 hover:underline truncate block" title="<?php echo htmlspecialchars($blog_post['post_title']); ?>">
                                                    <?php echo htmlspecialchars($blog_post['post_title']); ?>
                                                </a>
                                                <span class="text-gray-400 text-xxs ml-1">(<?php echo date('y.m.d', strtotime($blog_post['post_created_at'])); ?>)</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php endif; ?>         
                                <?php if (empty($project['blog_posts']) && empty($project['repo_url']) && empty($project['demo_url'])): ?>
                                <?php endif; ?>
                                <div class="flex space-x-2 justify-end">
                                    <a href="/projects/edit_project.php?id=<?php echo $project['project_id']; ?>" class="text-gray-400 hover:text-primary transition-colors text-sm" title="수정">
                                    <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $project['project_id']; ?>, '<?php echo htmlspecialchars(addslashes($project['project_name']), ENT_QUOTES); ?>')" class="text-gray-400 hover:text-red-500 transition-colors text-sm" title="삭제">
                                    <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #c5c5c5;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #a0a0a0;
        }
        .text-xxs {
            font-size: 0.65rem; /* Adjust as needed */
            line-height: 0.85rem; /* Adjust as needed */
        }
    </style>
</body>
</html>
