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
    $username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : (isset($_COOKIE['username']) ? htmlspecialchars($_COOKIE['username']) : '사용자');
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>블로그</title>
    <script src="/tailwind.js"></script>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.css" rel="stylesheet">
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
            margin-left: 260px;
            padding: 20px;
            width: calc(100% - 260px);
            padding-top: 100px;
        }
        .post-content {
            margin-left: 260px;
            padding: 20px;
            width: calc(100% - 260px);
            padding-top: 20px;
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
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .btn-primary {
            background-color: #5F43FF;
            color: white;
        }
        .btn-primary:hover {
            background-color: #8243FF;
        }
        .btn-secondary {
            background-color: #EBE9FB;
            color: #5F43FF;
            border: 1px solid #5F43FF;
        }
        .btn-secondary:hover {
            background-color: #DAD6FF;
        }
        .btn-url {
            padding: 0.35rem 0.8rem;
            background-color: #8243FF;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .btn-url-primary {
            background-color: #5F43FF;
            color: white;
        }
        .btn-url-primary:hover {
            background-color: #8243FF;
        }
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
            <a href="/blog/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-primary hover:text-primary-hover">
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
            <a href="/cover-letter/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-[#505050] hover:text-primary">
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
        <?php display_sidebar('/blog/index.php');?>
        <div class="main-content">
        <h1 class="text-2xl font-bold text-primary mb-6">게시글 등록</h1>
            <div class="bg-white p-8 rounded-lg shadow-md">
                
                <div class="flex space-x-4">
                    <a href="/blog/create_project_post.php" class="btn btn-primary">
                        프로젝트 게시글 등록
                    </a>
                    <a href="/blog/create_scrap_post.php" class="btn btn-primary">
                        스크랩 게시글 등록
                        </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<div class="post-content">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-primary">블로그 게시글</h1>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $_SESSION['success_message']; ?></span>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php
    require_once __DIR__ . '/../config/db.php';
    $user_id = $_SESSION['user_id'];

    $pdo_project_post = getPDO();
    $sql = "SELECT bp.*, p.name AS project_name, p.description AS project_description
            FROM blog_post bp
            LEFT JOIN projects p ON bp.project_id = p.id
            WHERE bp.user_id = :user_id
            ORDER BY bp.created_at DESC";
    $stmt = $pdo_project_post-> prepare($sql);
    $stmt-> bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt-> execute();
    $blog_posts = $stmt-> fetchAll(PDO::FETCH_ASSOC);

    $pdo_scrap_post = getPDO();
    $sql_scrap = "SELECT * FROM blog_scrap WHERE user_id = :user_id ORDER BY created_at DESC";
    $stmt_scrap = $pdo_scrap_post -> prepare($sql_scrap);
    $stmt_scrap->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt_scrap-> execute();
    $scrap_posts = $stmt_scrap-> fetchAll(PDO::FETCH_ASSOC);
    ?>

<div class="bg-white p-8 rounded-lg shadow-md">
    <div class="mb-5">
        <h2 class="text-xl font-bold mb-4">프로젝트 게시글</h2>
        <?php if (count($blog_posts) > 0): ?>
            <table class="min-w-full bg-white border rounded-lg">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b">제목</th>
                        <th class="py-2 px-4 border-b">프로젝트</th>
                        <th class="py-2 px-4 border-b">태그</th>
                        <th class="py-2 px-4 border-b">작성일</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($blog_posts as $row): ?>
                    <tr>
                        <td class="py-2 px-4 border-b text-center">
                            <a href="/blog/view_post.php?id=<?php echo $row['id']; ?>" class="text-primary hover:underline">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </a>
                        </td>
                        <td class="py-2 px-4 border-b text-center">
                            <?php if ($row['project_id'] && $row['project_name']): ?>
                                <span title="<?php echo htmlspecialchars($row['project_description']); ?>">
                                    <?php echo htmlspecialchars($row['project_name']); ?>
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="py-2 px-4 border-b text-center">
                            <?php echo htmlspecialchars($row['tags']); ?>
                        </td>
                        <td class="py-2 px-4 border-b text-center">
                            <?php echo date('Y-m-d', strtotime($row['created_at'])); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-gray-500">등록된 게시글이 없습니다.</div>
        <?php endif; ?>
    </div>

    <div>
        <h2 class="text-xl font-bold mb-4">스크랩 블로그 게시글</h2>
        <?php if (count($scrap_posts) > 0): ?>
            <table class="min-w-full bg-white border rounded-lg">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b">제목</th>
                        <th class="py-2 px-4 border-b">태그</th>
                        <th class="py-2 px-4 border-b">메모</th>
                        <th class="py-2 px-4 border-b">스크랩 일자</th>
                        <th class="py-2 px-4 border-b">링크</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($scrap_posts as $row): ?>
                    <tr>
                        <td class="py-2 px-4 border-b text-center">
                            <?php echo htmlspecialchars($row['original_title']); ?>
                        </td>
                        <td class="py-2 px-4 border-b text-center">
                            <?php echo htmlspecialchars($row['tags']); ?>
                        </td>
                        <td class="py-2 px-4 border-b text-center">
                            <?php echo htmlspecialchars($row['memo']); ?>
                        </td>
                        <td class="py-2 px-4 border-b text-center">
                            <?php echo date('Y-m-d', strtotime($row['created_at'])); ?>
                        </td>
                        <td class="py-2 px-4 border-b text-center">
                            <a href="<?php echo htmlspecialchars($row['original_url']); ?>" target="_blank" class="btn-url btn-url-primary">바로가기</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-gray-500">등록된 스크랩 게시글이 없습니다.</div>
        <?php endif; ?>
    </div>
</div>
</div>
</div>
</div>
</body>
</html>