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

$project_id_to_edit = null;
if (isset($_GET['id'])) {
    $project_id_to_edit = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
}

if (!$project_id_to_edit) {
    $_SESSION['page_error_message'] = "잘못된 접근입니다. 프로젝트 ID가 제공되지 않았습니다.";
    header('Location: /projects/index.php');
    exit;
}

$pdo = getPDO();
$project_data = null;
$project_technologies = [];

try {
    $stmt_project = $pdo->prepare("SELECT * FROM projects WHERE id = :project_id AND user_id = :user_id");
    $stmt_project->bindParam(':project_id', $project_id_to_edit, PDO::PARAM_INT);
    $stmt_project->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_project->execute();
    $project_data = $stmt_project->fetch(PDO::FETCH_ASSOC);

    if (!$project_data) {
        $_SESSION['page_error_message'] = "프로젝트를 찾을 수 없거나 수정 권한이 없습니다.";
        header('Location: /projects/index.php');
        exit;
    }

    $stmt_tech = $pdo->prepare("SELECT technology_id FROM project_technology_mapping WHERE project_id = :project_id");
    $stmt_tech->bindParam(':project_id', $project_id_to_edit, PDO::PARAM_INT);
    $stmt_tech->execute();
    $project_technologies = $stmt_tech->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $_SESSION['page_error_message'] = "프로젝트 정보를 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
    header('Location: /projects/index.php');
    exit;
}

$technologies_from_db = [];
$stmt_all_tech = $pdo->query("SELECT id, name, badge_url FROM project_technology_stack ORDER BY name ASC");
$technologies_from_db = $stmt_all_tech->fetchAll(PDO::FETCH_ASSOC);


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
            $pdo->beginTransaction();

            $sql_project_update = "UPDATE projects SET 
                name = ?, description = ?, start_date = ?, end_date = ?, status = ?, 
                progress_percentage = ?, role = ?, repo_url = ?, demo_url = ?, current_focus_issue = ?
                WHERE id = ? AND user_id = ?";
            $stmt_project_update = $pdo->prepare($sql_project_update);
            $stmt_project_update->execute([
                $project_name,
                $description,
                empty($start_date) ? null : $start_date,
                empty($end_date) ? null : $end_date,
                $status,
                $progress_percentage === false ? null : $progress_percentage,
                $role,
                $repo_url === false ? null : $repo_url,
                $demo_url === false ? null : $demo_url,
                $current_focus_issue,
                $project_id_to_edit,
                $user_id
            ]);

            $stmt_delete_tech_map = $pdo->prepare("DELETE FROM project_technology_mapping WHERE project_id = ?");
            $stmt_delete_tech_map->execute([$project_id_to_edit]);

            if (!empty($selected_technologies)) {
                $sql_tech_map_insert = "INSERT INTO project_technology_mapping (project_id, technology_id) VALUES (?, ?)";
                $stmt_tech_map_insert = $pdo->prepare($sql_tech_map_insert);
                foreach ($selected_technologies as $tech_id) {
                    $stmt_tech_map_insert->execute([$project_id_to_edit, $tech_id]);
                }
            }

            $pdo->commit();
            $message = '<p class="text-green-500">프로젝트가 성공적으로 수정되었습니다!</p>';
            $stmt_project->execute();
            $project_data = $stmt_project->fetch(PDO::FETCH_ASSOC);
            $stmt_tech->execute();
            $project_technologies = $stmt_tech->fetchAll(PDO::FETCH_COLUMN);

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = '<p class="text-red-500">프로젝트 수정 중 오류가 발생했습니다: ' . $e->getMessage() . '</p>';
        }
    }
}
$page_title = '프로젝트 수정';
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
        body { font-family: 'Pretendard', sans-serif; margin: 0; padding: 0; background-color: #f7f7fb; }
        .content-wrapper { display: flex; }
        .main-content { margin-left: 260px; padding: 20px; width: calc(100% - 260px); padding-top: 80px; }
        .navbar { background-color: white; padding: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 1.5rem; font-weight: bold; background: linear-gradient(90deg, #5F43FF 11.64%, #8243FF 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; text-fill-color: transparent; }
        .navbar-user { font-size: 0.9rem; color: #505050; }
        .form-input { width: 100%; padding-top: 1rem; padding-bottom: 1rem; padding-left: 1rem; padding-right: 1rem; font-size: 0.875rem; line-height: 1.25rem; color: #4B5563; background-color: #EBE9FB; border-radius: 12px; font-family: 'Noto Sans KR', sans-serif; font-weight: 500; box-shadow: inset -1px -1px 1px #FFFFFF, inset 1px 1px 1px rgba(0,0,0,0.1); }
        .form-input:focus { outline: none; box-shadow: 0 0 0 2px #5F43FF, inset -1px -1px 1px #FFFFFF, inset 1px 1px 1px rgba(0,0,0,0.1); }
        .tech-badge-selector { display: inline-block; padding: 0.5rem 1rem; margin: 0.25rem; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s ease-in-out; }
        .tech-badge-selector.selected { background-color: #5F43FF; color: white; }
        .tech-badge-selector:not(.selected) { background-color: #e0e0e0; color: #333; }
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
            <a href="/projects/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-primary hover:text-primary-hover">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-folder-fill mr-2" viewBox="0 0 16 16"><path d="M9.828 3h3.982a2 2 0 0 1 1.992 2.181l-.637 7A2 2 0 0 1 13.174 14H2.826a2 2 0 0 1-1.991-1.819l-.637-7a1.99 1.99 0 0 1 .342-1.31L.5 3a2 2 0 0 1 2-2h3.672a2 2 0 0 1 1.414.586l.828.828A2 2 0 0 0 9.828 3zm-8.322.12C1.72 3.042 1.95 3 2.19 3h5.396l-.707-.707A1 1 0 0 0 6.172 2H2.5a1 1 0 0 0-1 .981l.006.139z"/></svg>
                프로젝트
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
        <main class="main-content">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($page_title); ?></h1>
                <a href="/projects/index.php" class="text-sm text-primary hover:underline">프로젝트 목록으로 돌아가기</a>
            </div>

            <?php if (!empty($message)): ?>
                <div class="mb-4 p-4 rounded-md <?php echo strpos($message, '성공적으로') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="/projects/edit_project.php?id=<?php echo $project_id_to_edit; ?>" method="POST" class="bg-white shadow-md rounded-lg p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="project_name" class="block text-sm font-medium text-gray-700 mb-1">프로젝트 이름 <span class="text-red-500">*</span></label>
                        <input type="text" name="project_name" id="project_name" value="<?php echo htmlspecialchars($project_data['name'] ?? ''); ?>" required class="form-input">
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">진행 상태</label>
                        <select name="status" id="status" class="form-input bg-white border border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                            <option value="계획중" <?php echo ($project_data['status'] ?? '') === '계획중' ? 'selected' : ''; ?>>계획중</option>
                            <option value="진행중" <?php echo ($project_data['status'] ?? '진행중') === '진행중' ? 'selected' : ''; ?>>진행중</option>
                            <option value="완료" <?php echo ($project_data['status'] ?? '') === '완료' ? 'selected' : ''; ?>>완료</option>
                            <option value="보류" <?php echo ($project_data['status'] ?? '') === '보류' ? 'selected' : ''; ?>>보류</option>
                            <option value="중단" <?php echo ($project_data['status'] ?? '') === '중단' ? 'selected' : ''; ?>>중단</option>
                        </select>
                    </div>
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">시작일</label>
                        <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($project_data['start_date'] ?? ''); ?>" class="form-input bg-white border border-gray-300">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">종료일</label>
                        <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($project_data['end_date'] ?? ''); ?>" class="form-input bg-white border border-gray-300">
                    </div>
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">프로젝트 설명</label>
                        <textarea name="description" id="description" rows="4" class="form-input bg-white border border-gray-300"><?php echo htmlspecialchars($project_data['description'] ?? ''); ?></textarea>
                    </div>
                     <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">나의 역할</label>
                        <input type="text" name="role" id="role" value="<?php echo htmlspecialchars($project_data['role'] ?? ''); ?>" class="form-input">
                    </div>
                    <div>
                        <label for="progress_percentage" class="block text-sm font-medium text-gray-700 mb-1">진행률 (%)</label>
                        <input type="number" name="progress_percentage" id="progress_percentage" value="<?php echo htmlspecialchars($project_data['progress_percentage'] ?? '0'); ?>" min="0" max="100" class="form-input">
                    </div>
                    <div>
                        <label for="repo_url" class="block text-sm font-medium text-gray-700 mb-1">저장소 URL (GitHub 등)</label>
                        <input type="url" name="repo_url" id="repo_url" value="<?php echo htmlspecialchars($project_data['repo_url'] ?? ''); ?>" placeholder="https://example.com" class="form-input">
                    </div>
                    <div>
                        <label for="demo_url" class="block text-sm font-medium text-gray-700 mb-1">데모/서비스 URL</label>
                        <input type="url" name="demo_url" id="demo_url" value="<?php echo htmlspecialchars($project_data['demo_url'] ?? ''); ?>" placeholder="https://example.com" class="form-input">
                    </div>
                    <div class="md:col-span-2">
                        <label for="current_focus_issue" class="block text-sm font-medium text-gray-700 mb-1">현재 집중하고 있는 이슈/작업</label>
                        <input type="text" name="current_focus_issue" id="current_focus_issue" value="<?php echo htmlspecialchars($project_data['current_focus_issue'] ?? ''); ?>" class="form-input">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">사용 기술 스택</label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <?php foreach ($technologies_from_db as $tech): ?>
                                <label class="tech-badge-selector <?php echo in_array($tech['id'], $project_technologies) ? 'selected' : ''; ?>">
                                    <input type="checkbox" name="technologies[]" value="<?php echo $tech['id']; ?>" class="sr-only" <?php echo in_array($tech['id'], $project_technologies) ? 'checked' : ''; ?>>
                                    <?php if (!empty($tech['badge_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($tech['badge_url']); ?>" alt="<?php echo htmlspecialchars($tech['name']); ?>" class="inline h-5 mr-1">
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($tech['name']); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-primary text-white font-semibold rounded-lg shadow-md hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-75 transition duration-150">
                        프로젝트 수정
                    </button>
                </div>
            </form>
        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const techBadges = document.querySelectorAll('.tech-badge-selector');
            techBadges.forEach(badge => {
                badge.addEventListener('click', function () {
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    checkbox.checked = !checkbox.checked;
                    this.classList.toggle('selected', checkbox.checked);
                });
            });
        });
    </script>
</body>
</html>