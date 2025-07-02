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

$technologies_from_db = [];
try {
    $pdo_tech = getPDO();
    $stmt_tech = $pdo_tech->query("SELECT id, name, badge_url FROM project_technology_stack ORDER BY name ASC");
    $technologies_from_db = $stmt_tech->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $project_name = trim($_POST['project_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $status = $_POST['status'] ?? '진행중';
    $progress_percentage = filter_input(INPUT_POST, 'progress_percentage', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
    $selected_technologies = $_POST['technologies'] ?? [];
    $role = trim($_POST['role'] ?? '');
    $repo_url = filter_input(INPUT_POST, 'repo_url', FILTER_VALIDATE_URL);
    $demo_url = filter_input(INPUT_POST, 'demo_url', FILTER_VALIDATE_URL);
    $current_focus_issue = trim($_POST['current_focus_issue'] ?? '');

    if (empty($project_name)) {
        $message = '<p class="text-red-500">프로젝트 이름을 입력해주세요.</p>';
    } else {
        try {
            $pdo = getPDO();
            $pdo->beginTransaction();

            $sql_project = "INSERT INTO projects (user_id, name, description, start_date, end_date, status, progress_percentage, role, repo_url, demo_url, current_focus_issue) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_project = $pdo->prepare($sql_project);
            $stmt_project->execute([
                $user_id,
                $project_name,
                $description,
                empty($start_date) ? null : $start_date,
                empty($end_date) ? null : $end_date,
                $status,
                $progress_percentage === false ? null : $progress_percentage,
                $role,
                $repo_url === false ? null : $repo_url,
                $demo_url === false ? null : $demo_url,
                $current_focus_issue
            ]);
            $project_id = $pdo->lastInsertId();

            if (!empty($selected_technologies) && $project_id) {
                $sql_tech_map = "INSERT INTO project_technology_mapping (project_id, technology_id) VALUES (?, ?)";
                $stmt_tech_map = $pdo->prepare($sql_tech_map);
                foreach ($selected_technologies as $tech_id) {
                    $stmt_tech_map->execute([$project_id, $tech_id]);
                }
            }

            $pdo -> commit();
            $message = '프로젝트가 성공적으로 등록되었습니다!</p>';
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = '프로젝트 등록 중 오류가 발생했습니다.</p>';
        } catch (PDOException $e) {
            $message = '프로젝트 등록 중 오류가 발생했습니다.</p>';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>프로젝트 관리</title>
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
        .form-input {
            width: 100%;
            padding-top: 1rem;
            padding-bottom: 1rem;
            padding-left: 1rem;
            padding-right: 1rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: #4B5563;
            background-color: #EBE9FB;
            border-radius: 12px;
            font-family: 'Noto Sans KR', sans-serif;
            font-weight: 500;
            box-shadow: inset -1px -1px 1px #FFFFFF, inset 1px 1px 1px rgba(0,0,0,0.1);
        }
        .form-input:focus {
            outline: none;
            box-shadow: 0 0 0 2px #5F43FF, 
                        inset -1px -1px 1px #FFFFFF, 
                        inset 1px 1px 1px rgba(0,0,0,0.1);
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
        <?php display_sidebar('/projects/index.php');?>
        <div class="main-content">
            <div class="bg-white p-8 rounded-lg shadow-md">
                <h1 class="text-2xl font-bold text-primary mb-6">새 프로젝트 등록</h1>
                <?php if (!empty($message)) echo "<div class='mb-4'>{$message}</div>"; ?>
                <form action="" method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <label for="project_name" class="form-label">프로젝트 이름 <span class="text-red-500">*</span></label>
                            <input type="text" id="project_name" name="project_name" class="form-input" required>
                        </div>
                        <div>
                            <label for="status" class="form-label">상태</label>
                            <select id="status" name="status" class="form-input">
                                <option value="진행중" selected>진행중</option>
                                <option value="완료">완료</option>
                                <option value="보류">보류</option>
                                <option value="계획중">계획중</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label">설명</label>
                        <textarea id="description" name="description" rows="4" class="form-input"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <label for="start_date" class="form-label">시작일</label>
                            <input type="date" id="start_date" name="start_date" class="form-input">
                        </div>
                        <div>
                            <label for="end_date" class="form-label">종료일</label>
                            <input type="date" id="end_date" name="end_date" class="form-input">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="progress_percentage" class="form-label">진행률 (%)</label>
                        <input type="number" id="progress_percentage" name="progress_percentage" min="0" max="100" class="form-input">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">사용 기술</label>
                        <div class="mt-2 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            <?php foreach ($technologies_from_db as $tech): ?>
                                <label for="tech_<?php echo htmlspecialchars($tech['id']); ?>" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                    <input type="checkbox" id="tech_<?php echo htmlspecialchars($tech['id']); ?>" name="technologies[]" value="<?php echo htmlspecialchars($tech['id']); ?>" class="form-checkbox h-5 w-5 text-primary focus:ring-primary border-gray-300 rounded mr-3">
                                    <img src="<?php echo htmlspecialchars(trim($tech['badge_url'])); ?>" alt="<?php echo htmlspecialchars($tech['name']); ?>" class="h-6 mr-2">
                                    <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($tech['name']); ?></span>
                                </label>
                            <?php endforeach; ?>
                            <?php if (empty($technologies_from_db)): ?>
                                <p class="text-gray-500 col-span-full">등록된 기술 스택이 없습니다. 관리자에게 문의하세요.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="role" class="form-label">나의 역할</label>
                        <textarea id="role" name="role" rows="3" class="form-input"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <label for="repo_url" class="form-label">저장소 URL</label>
                            <input type="url" id="repo_url" name="repo_url" class="form-input" placeholder="https://github.com/user/project">
                        </div>
                        <div>
                            <label for="demo_url" class="form-label">데모 URL</label>
                            <input type="url" id="demo_url" name="demo_url" class="form-input" placeholder="https://project-demo.com">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="current_focus_issue" class="form-label">현재 집중하고 있는 이슈 (대시보드용)</label>
                        <input type="text" id="current_focus_issue" name="current_focus_issue" class="form-input" maxlength="100">
                    </div>

                    <div>
                        <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white font-bold py-3 px-4 rounded-md transition duration-150 ease-in-out">
                            프로젝트 등록
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>