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

$edit_mode = false;
$experience_to_edit = [
    'id' => '',
    'company_name' => '',
    'position' => '',
    'start_date' => '',
    'end_date' => '',
    'description' => '',
    'url' => '',
    'is_current' => false
];

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM resume_work_experience WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $edit_mode = true;
            $experience_to_edit = $data;
            $experience_to_edit['is_current'] = is_null($data['end_date']);
            if ($experience_to_edit['is_current']) {
                $experience_to_edit['end_date'] = '';
            }
        } else {
            $error_message = "수정할 경력 정보를 찾을 수 없습니다.";
        }
    } catch (PDOException $e) {
        $error_message = "경력 정보 로드 중 오류: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_experience' || $action === 'update_experience') {
            $company_name = trim($_POST['company_name']);
            $position = trim($_POST['position']);
            $start_date = trim($_POST['start_date']);
            $is_current = isset($_POST['is_current']);
            $end_date = $is_current ? null : (trim($_POST['end_date']) ?: null);
            $description = trim($_POST['description']);
            $url = trim($_POST['url']) ?: null;
            $experience_id = isset($_POST['experience_id']) ? intval($_POST['experience_id']) : null;

            if (empty($company_name) || empty($position) || empty($start_date) || empty($description)) {
                $error_message = "회사/프로젝트명, 직책/역할, 시작일, 상세 설명은 필수 항목입니다.";
            } elseif (!$is_current && empty($end_date)) {
                $error_message = "종료일을 입력하거나 '현재 진행 중'을 선택해주세요.";
            } elseif (!$is_current && !empty($end_date) && strtotime($start_date) > strtotime($end_date)) {
                $error_message = "시작일은 종료일보다 이전이어야 합니다.";
            } elseif (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
                $error_message = "유효한 URL을 입력해주세요.";
            }

            if (empty($error_message)) {
                if ($action === 'add_experience') {
                    $stmt = $pdo->prepare("INSERT INTO resume_work_experience (user_id, company_name, position, start_date, end_date, description, url) VALUES (:user_id, :company_name, :position, :start_date, :end_date, :description, :url)");
                } else {
                    $stmt = $pdo->prepare("UPDATE resume_work_experience SET company_name = :company_name, position = :position, start_date = :start_date, end_date = :end_date, description = :description, url = :url, updated_at = NOW() WHERE id = :id AND user_id = :user_id");
                    $stmt->bindParam(':id', $experience_id, PDO::PARAM_INT);
                }
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':company_name', $company_name, PDO::PARAM_STR);
                $stmt->bindParam(':position', $position, PDO::PARAM_STR);
                $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
                $stmt->bindParam(':end_date', $end_date, $end_date === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindParam(':description', $description, PDO::PARAM_STR);
                $stmt->bindParam(':url', $url, $url === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

                if ($stmt->execute()) {
                    $success_message = "경력 정보가 성공적으로 " . ($action === 'add_experience' ? "추가" : "수정") . "되었습니다.";
                    $edit_mode = false;
                    $experience_to_edit = array_fill_keys(array_keys($experience_to_edit), ''); 
                    $experience_to_edit['is_current'] = false;
                } else {
                    $error_message = "경력 정보 처리 중 오류가 발생했습니다.";
                }
            } else {
                $experience_to_edit = [
                    'id' => $experience_id, 'company_name' => $company_name, 'position' => $position,
                    'start_date' => $start_date, 'end_date' => $is_current ? '' : $end_date, 'description' => $description,
                    'url' => $url, 'is_current' => $is_current
                ];
                 if ($action === 'update_experience') $edit_mode = true;
            }

        } elseif ($action === 'delete_experience' && isset($_POST['experience_id'])) {
            $delete_id = intval($_POST['experience_id']);
            $stmt = $pdo->prepare("DELETE FROM resume_work_experience WHERE id = :id AND user_id = :user_id");
            $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                $success_message = "경력 정보가 삭제되었습니다.";
            } else {
                $error_message = "경력 정보 삭제 중 오류가 발생했거나 권한이 없습니다.";
            }
        }
    } catch (PDOException $e) {
        $error_message = "데이터베이스 오류: " . $e->getMessage();
    }
}

$current_experiences = [];
try {
    $stmt_experiences = $pdo->prepare("SELECT * FROM resume_work_experience WHERE user_id = :user_id ORDER BY start_date DESC, id DESC");
    $stmt_experiences->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_experiences->execute();
    $current_experiences = $stmt_experiences->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "경력 목록을 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>경력 관리 - 나의 이력서</title>
    <script src="/tailwind.js"></script>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.css" rel="stylesheet">
    <style>
        body { font-family: 'Pretendard', sans-serif; margin: 0; padding: 0; background-color: #f7f7fb; }
        .content-wrapper { display: flex; }
        .main-content { flex-grow: 1; padding: 2rem; margin-left: 280px; background-color: #ffffff; }
        .form-input, .form-textarea, .form-checkbox, .form-date { border-color: #e2e8f0; }
        .form-input:focus, .form-textarea:focus, .form-date:focus { border-color: #5F43FF; box-shadow: 0 0 0 2px rgba(95, 67, 255, 0.2); }
        .form-label { font-weight: 500; }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <?php display_sidebar('/resume/index.php'); ?>
        <main class="main-content">
            <div class="container mx-auto px-4 py-8">
                <h1 class="text-3xl font-bold mb-8 text-gray-800">경력 및 프로젝트 관리</h1>

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

                <div class="bg-white shadow-lg rounded-lg px-8 pt-6 pb-8 mb-10">
                    <h2 class="text-xl font-semibold text-gray-700 mb-6"><?php echo $edit_mode ? '경력 수정' : '새 경력/프로젝트 추가'; ?></h2>
                    <form action="edit_experience.php<?php echo $edit_mode ? '?action=edit&id='.$experience_to_edit['id'] : ''; ?>" method="POST">
                        <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_experience' : 'add_experience'; ?>">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="experience_id" value="<?php echo htmlspecialchars($experience_to_edit['id']); ?>">
                        <?php endif; ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                            <div>
                                <label for="company_name" class="block text-gray-700 text-sm form-label mb-2">회사명 / 프로젝트명</label>
                                <input type="text" name="company_name" id="company_name" class="form-input shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring" value="<?php echo htmlspecialchars($experience_to_edit['company_name']); ?>" required>
                            </div>
                            <div>
                                <label for="position" class="block text-gray-700 text-sm form-label mb-2">직책 / 역할</label>
                                <input type="text" name="position" id="position" class="form-input shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring" value="<?php echo htmlspecialchars($experience_to_edit['position']); ?>" required>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                            <div>
                                <label for="start_date" class="block text-gray-700 text-sm form-label mb-2">시작일</label>
                                <input type="date" name="start_date" id="start_date" class="form-date shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring" value="<?php echo htmlspecialchars($experience_to_edit['start_date']); ?>" required>
                            </div>
                            <div>
                                <label for="end_date" class="block text-gray-700 text-sm form-label mb-2">종료일</label>
                                <input type="date" name="end_date" id="end_date" class="form-date shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring" value="<?php echo htmlspecialchars($experience_to_edit['end_date']); ?>" <?php echo $experience_to_edit['is_current'] ? 'disabled' : ''; ?>>
                                <div class="mt-2">
                                    <input type="checkbox" name="is_current" id="is_current" class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" <?php echo $experience_to_edit['is_current'] ? 'checked' : ''; ?> onchange="document.getElementById('end_date').disabled = this.checked; if(this.checked) document.getElementById('end_date').value = '';">
                                    <label for="is_current" class="ml-2 text-sm text-gray-600">현재 재직 중 / 프로젝트 진행 중</label>
                                </div>
                            </div>
                        </div>
                         <div class="mb-4">
                            <label for="url" class="block text-gray-700 text-sm form-label mb-2">관련 URL (선택)</label>
                            <input type="url" name="url" id="url" class="form-input shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring" placeholder="https://example.com" value="<?php echo htmlspecialchars($experience_to_edit['url']); ?>">
                        </div>
                        <div class="mb-6">
                            <label for="description" class="block text-gray-700 text-sm form-label mb-2">상세 설명 (주요 업무, 성과 등)</label>
                            <textarea name="description" id="description" rows="5" class="form-textarea shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring" required><?php echo htmlspecialchars($experience_to_edit['description']); ?></textarea>
                        </div>
                        <div class="flex items-center justify-start">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline transition-colors">
                                <?php echo $edit_mode ? '경력 수정하기' : '경력 추가하기'; ?>
                            </button>
                            <?php if ($edit_mode): ?>
                                <a href="edit_experience.php" class="ml-4 inline-block align-baseline font-semibold text-sm text-gray-600 hover:text-gray-800">
                                    취소 (새 경력 추가 모드로)
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="bg-white shadow-lg rounded-lg px-8 pt-6 pb-8">
                    <h2 class="text-xl font-semibold text-gray-700 mb-6">등록된 경력/프로젝트 목록</h2>
                    <?php if (empty($current_experiences)): ?>
                        <p class="text-gray-600">등록된 경력 정보가 없습니다.</p>
                    <?php else: ?>
                        <ul class="space-y-6">
                            <?php foreach ($current_experiences as $exp): ?>
                                <li class="p-4 border border-gray-200 rounded-md hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="text-lg font-semibold text-blue-700"><?php echo htmlspecialchars($exp['company_name']); ?></h3>
                                        <div class="flex space-x-2">
                                            <a href="edit_experience.php?action=edit&id=<?php echo $exp['id']; ?>" class="text-sm text-yellow-600 hover:text-yellow-800 font-medium py-1 px-2 rounded hover:bg-yellow-100 transition-colors">수정</a>
                                            <form action="edit_experience.php" method="POST" onsubmit="return confirm('정말로 이 경력 정보를 삭제하시겠습니까?');" class="inline">
                                                <input type="hidden" name="action" value="delete_experience">
                                                <input type="hidden" name="experience_id" value="<?php echo $exp['id']; ?>">
                                                <button type="submit" class="text-sm text-red-500 hover:text-red-700 font-medium py-1 px-2 rounded hover:bg-red-100 transition-colors">삭제</button>
                                            </form>
                                        </div>
                                    </div>
                                    <p class="text-md text-gray-800 font-medium"><?php echo htmlspecialchars($exp['position']); ?></p>
                                    <p class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars(date("Y년 m월", strtotime($exp['start_date']))); ?> ~ 
                                        <?php echo is_null($exp['end_date']) ? '현재' : htmlspecialchars(date("Y년 m월", strtotime($exp['end_date']))); ?>
                                    </p>
                                    <?php if (!empty($exp['url'])): ?>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <a href="<?php echo htmlspecialchars($exp['url']); ?>" target="_blank" rel="noopener noreferrer" class="text-blue-500 hover:underline break-all">
                                                <?php echo htmlspecialchars($exp['url']); ?>
                                            </a>
                                        </p>
                                    <?php endif; ?>
                                    <div class="mt-2 text-sm text-gray-600 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($exp['description'])); ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                 <div class="mt-8 text-center">
                    <a href="/resume/index.php" class="inline-block bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-6 rounded-md focus:outline-none focus:shadow-outline transition-colors">
                        이력서로 돌아가기
                    </a>
                </div>
            </div>
        </main>
    </div>
    <script>
        const isCurrentCheckbox = document.getElementById('is_current');
        const endDateInput = document.getElementById('end_date');
        if (isCurrentCheckbox && endDateInput) {
            isCurrentCheckbox.addEventListener('change', function() {
                endDateInput.disabled = this.checked;
                if (this.checked) {
                    endDateInput.value = '';
                }
            });
            endDateInput.disabled = isCurrentCheckbox.checked;
        }
    </script>
</body>
</html>