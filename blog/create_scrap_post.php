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

$project_id = $title = $tags = $url = $memo = "";
$title_err = $content_err = $url_err = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["title"]))) {
        $title_err = "제목을 입력해주세요.";
    } else {
        $title = trim($_POST["title"]);
    }

    if (empty(trim($_POST["memo"]))) {
        $memo_err = "내용을 입력해주세요.";
    } else {
        $memo = trim($_POST["memo"]);
    }

    if (empty(trim($_POST["url"]))) {
        $url_err = "url을 입력해주세요.";
    } else {
        $url = trim($_POST["url"]);
    }
    
    $tags = trim($_POST["tags"]);

    if (empty($title_err) && empty($url_err)) {
        try {
            $pdo = getPDO();
            $sql = "INSERT INTO blog_scrap (user_id, original_title, original_url, tags, memo, created_at, updated_at) VALUES (:user_id, :original_title, :original_url, :tags, :memo, NOW(), NOW())";
            
            if ($stmt = $pdo->prepare($sql)) {
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->bindParam(":original_title", $title, PDO::PARAM_STR);
                $stmt->bindParam(":original_url", $url, PDO::PARAM_STR);
                $stmt->bindParam(":memo", $memo, PDO::PARAM_STR);
                
                $tags_to_db = !empty($tags) ? $tags : null;
                $stmt->bindParam(":tags", $tags_to_db, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "프로젝트 게시글이 성공적으로 등록되었습니다.";
                    header("Location: /blog/index.php");
                    exit();
                } else {
                    echo "<script>alert('게시글 등록에 실패했습니다. 다시 시도해주세요.');</script>";
                }
                unset($stmt);
            }
        } catch (PDOException $e) {
            $message = $e->getMessage();
            error_log("프로젝트 게시글 등록 오류: " . $e->getMessage());
            echo "<script>alert('데이터베이스 오류가 발생했습니다.');</script> $message";
        }
        unset($pdo);
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>새 프로젝트 게시글 작성 - devcareer</title>
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
            padding-top: 80px;
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
        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: #374151;
            background-color: #F3F4F6;
            border: 1px solid #D1D5DB;
            border-radius: 0.5rem;
            font-family: 'Pretendard', sans-serif;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
        }
        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: #5F43FF;
            box-shadow: 0 0 0 2px rgba(95, 67, 255, 0.3);
        }
        .form-textarea {
            min-height: 150px;
            resize: vertical;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: background-color 0.3s ease;
            cursor: pointer;
        }
        .btn-primary {
            background-color: #5F43FF;
            color: white;
            border: none;
        }
        .btn-primary:hover {
            background-color: #8243FF;
        }
        .error-message {
            color: #EF4444;
            font-size: 0.875rem;
            margin-top: 0.25rem;
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
            <a href="/blog/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-primary hover:text-primary-hover">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square mr-2" viewBox="0 0 16 16">
                    <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                    <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                </svg>
                블로그
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
        <?php display_sidebar('/blog/index.php'); ?>
        <div class="main-content">
            <div class="bg-white p-8 rounded-lg shadow-md max-w-2xl mx-auto">
                <h1 class="text-2xl font-bold text-primary mb-6">새 스크랩 게시글 작성</h1>
                
                <?php if (!empty($project_fetch_error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $project_fetch_error; ?></span>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-4">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">제목<span class="text-red-500">*</span></label>
                        <input type="text" name="title" id="title" class="form-input <?php echo (!empty($title_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($title); ?>">
                        <?php if(!empty($title_err)): ?>
                            <p class="error-message"><?php echo $title_err; ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-6">
                        <label for="url" class="block text-sm font-medium text-gray-700 mb-1">게시글 url <span class="text-red-500">*</span></label>
                        <input type="text" name="url" id="url" class="form-input <?php echo (!empty($title_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($url); ?>">
                        <?php if(!empty($content_err)): ?>
                            <p class="error-message"><?php echo $content_err; ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-6">
                        <label for="content" class="block text-sm font-medium text-gray-700 mb-1">메모<span class="text-red-500">*</span></label>
                        <textarea name="memo" id="memo" class="form-textarea <?php echo (!empty($content_err)) ? 'border-red-500' : ''; ?>"><?php echo htmlspecialchars($memo); ?></textarea>
                        <?php if(!empty($content_err)): ?>
                            <p class="error-message"><?php echo $content_err; ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-6">
                        <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">태그 (쉼표로 구분)</label>
                        <input type="text" name="tags" id="tags" class="form-input" value="<?php echo htmlspecialchars($tags); ?>">
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary w-full">게시글 등록</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>