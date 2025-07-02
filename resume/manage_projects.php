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
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_project_ids = $_POST['project_selection'] ?? [];
    $project_orders_input = $_POST['project_order'] ?? [];

    $projects_to_save = [];
    if (!empty($selected_project_ids)) {
        foreach ($selected_project_ids as $project_id) {
            $project_id = (int)$project_id;
                $order_value = isset($project_orders_input[$project_id]) ? (int)$project_orders_input[$project_id] : 999;
                $projects_to_save[] = ['id' => $project_id, 'order' => $order_value];
        }
    }

    usort($projects_to_save, function ($a, $b) {
        if ($a['order'] == $b['order']) {
            return $a['id'] <=> $b['id'];
        }
        return $a['order'] <=> $b['order'];
    });

    try {
        $pdo->beginTransaction();

        $delete_stmt = $pdo->prepare("DELETE FROM resume_project WHERE user_id = :user_id");
        $delete_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $delete_stmt->execute();

        $insert_stmt = $pdo->prepare("INSERT INTO resume_project (user_id, project_id, display_order) VALUES (:user_id, :project_id, :display_order)");
        $current_display_order = 1;
        foreach ($projects_to_save as $proj_data) {
            $insert_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':project_id', $proj_data['id'], PDO::PARAM_INT);
            $insert_stmt->bindParam(':display_order', $current_display_order, PDO::PARAM_INT);
            $insert_stmt->execute();
            $current_display_order++;
        }

        $pdo->commit();
        $success_message = "프로젝트 표시 정보가 성공적으로 업데이트되었습니다.";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "프로젝트 정보 업데이트 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

$all_user_projects = [];
try {
    $stmt_all_projects = $pdo->prepare("SELECT id, name, DATE_FORMAT(start_date, '%Y-%m') as start_month, DATE_FORMAT(end_date, '%Y-%m') as end_month FROM projects WHERE user_id = :user_id ORDER BY start_date DESC, name ASC");
    $stmt_all_projects->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_all_projects->execute();
    $all_user_projects = $stmt_all_projects->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "사용자 프로젝트 목록을 불러오는 중 오류: " . $e->getMessage();
}

$current_resume_project_settings = [];
try {
    $stmt_resume_projects = $pdo->prepare("SELECT project_id, display_order FROM resume_project WHERE user_id = :user_id");
    $stmt_resume_projects->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_resume_projects->execute();
    $rows = $stmt_resume_projects->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $current_resume_project_settings[$row['project_id']] = $row['display_order'];
    }
} catch (PDOException $e) {
    $error_message = "선택된 프로젝트 정보를 불러오는 중 오류: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>이력서 프로젝트 관리 - 나의 이력서</title>
    <script src="/tailwind.js"></script>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.css" rel="stylesheet">
    <style>
        body { font-family: 'Pretendard', sans-serif; margin: 0; padding: 0; background-color: #f7f7fb; }
        .content-wrapper { display: flex; }
        .main-content { flex-grow: 1; padding: 2rem; margin-left: 280px; background-color: #ffffff; }
        .form-input, .form-checkbox { border-color: #e2e8f0; }
        .form-input:focus { border-color: #5F43FF; box-shadow: 0 0 0 2px rgba(95, 67, 255, 0.2); }
        .form-label { font-weight: 500; }
        .project-item { border: 1px solid #e2e8f0; border-radius: 0.375rem; padding: 1rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; }
        .project-info { flex-grow: 1; }
        .project-controls { display: flex; align-items: center; gap: 0.5rem; min-width: 150px; }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <?php display_sidebar('/resume/index.php'); ?>
        <main class="main-content">
            <div class="container mx-auto px-4 py-8">
                <h1 class="text-3xl font-bold mb-8 text-gray-800">이력서 프로젝트 표시 관리</h1>

                <?php if (!empty($success_message)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md" role="alert">
                        <p><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error_message)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                <?php endif; ?>

                <form action="manage_projects.php" method="POST" class="bg-white shadow-lg rounded-lg px-8 pt-6 pb-8 mb-10">
                    <?php if (empty($all_user_projects)): ?>
                        <p class="text-gray-600">등록된 프로젝트가 없습니다. <a href="/projects/create.php" class="text-blue-600 hover:underline">새 프로젝트를 먼저 추가해주세요.</a></p>
                    <?php else: ?>
                        <div class="mb-6 text-sm text-gray-600">
                            <p>이력서에 표시할 프로젝트를 선택하고, 표시 순서를 숫자로 입력해주세요. (낮은 숫자가 먼저 표시됩니다)</p>
                        </div>
                        <?php foreach ($all_user_projects as $project): ?>
                            <?php
                                $project_id = $project['id'];
                                $is_selected = array_key_exists($project_id, $current_resume_project_settings);
                                $display_order_value = $is_selected ? $current_resume_project_settings[$project_id] : '';
                            ?>
                            <div class="project-item hover:bg-gray-50">
                                <div class="project-info">
                                    <h3 class="text-lg font-semibold text-gray-700"><?php echo htmlspecialchars($project['name']); ?></h3>
                                    <p class="text-sm text-gray-500">
                                        기간: <?php echo htmlspecialchars($project['start_month'] ?? 'N/A'); ?> ~ <?php echo htmlspecialchars($project['end_month'] ?? '진행중'); ?>
                                    </p>
                                </div>
                                <div class="project-controls">
                                    <label for="order_<?php echo $project_id; ?>" class="text-sm mr-1">순서:</label>
                                    <input type="number" name="project_order[<?php echo $project_id; ?>]" id="order_<?php echo $project_id; ?>" value="<?php echo htmlspecialchars($display_order_value); ?>" class="form-input w-16 text-center py-1 px-2 text-sm rounded" min="1">
                                    <input type="checkbox" name="project_selection[]" id="select_<?php echo $project_id; ?>" value="<?php echo $project_id; ?>" class="form-checkbox h-5 w-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 ml-2" <?php echo $is_selected ? 'checked' : ''; ?>>
                                    <label for="select_<?php echo $project_id; ?>" class="text-sm ml-1">선택</label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($all_user_projects)): ?>
                    <div class="flex items-center justify-start mt-10 pt-6 border-t border-gray-200">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-md focus:outline-none focus:shadow-outline transition-colors">
                            선택 및 순서 저장
                        </button>
                    </div>
                    <?php endif; ?>
                </form>

                 <div class="mt-8 text-center">
                    <a href="/resume/index.php" class="inline-block bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-6 rounded-md focus:outline-none focus:shadow-outline transition-colors">
                        이력서로 돌아가기
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>