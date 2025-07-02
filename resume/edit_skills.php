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

$user_skill_ids = [];
try {
    $user_skills_stmt = $pdo->prepare("SELECT skill_id FROM resume_skill WHERE user_id = :user_id");
    $user_skills_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $user_skills_stmt->execute();
    $user_skill_ids = $user_skills_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $error_message = "현재 스킬 정보를 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
}


$all_skills_data = [];
if (empty($error_message)) { 
    try {
        $stmt_categories = $pdo->query("SELECT category_id, category_name FROM resume_skill_category ORDER BY category_name");
        $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

        foreach ($categories as $category) {
            $stmt_skills_ref = $pdo->prepare("SELECT reference_id, name, url FROM resume_skill_reference WHERE category_id = :category_id ORDER BY name");
            $stmt_skills_ref->bindParam(':category_id', $category['category_id'], PDO::PARAM_INT);
            $stmt_skills_ref->execute();
            $skills_in_category = $stmt_skills_ref->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($skills_in_category)) {
                $all_skills_data[$category['category_name']] = $skills_in_category;
            }
        }
    } catch (PDOException $e) {
        $error_message = "스킬 목록을 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_skill_ids = isset($_POST['skill_ids']) ? $_POST['skill_ids'] : [];
    $selected_skill_ids = array_map('intval', $selected_skill_ids);

    try {
        $pdo->beginTransaction();

        $delete_stmt = $pdo->prepare("DELETE FROM resume_skill WHERE user_id = :user_id");
        $delete_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $delete_stmt->execute();

        if (!empty($selected_skill_ids)) {
            $insert_stmt = $pdo->prepare("INSERT INTO resume_skill (user_id, skill_id) VALUES (:user_id, :skill_id)");
            foreach ($selected_skill_ids as $skill_id) {
                if ($skill_id > 0) {
                    $insert_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $insert_stmt->bindParam(':skill_id', $skill_id, PDO::PARAM_INT);
                    $insert_stmt->execute();
                }
            }
        }
        
        $pdo->commit();
        $success_message = "스킬 정보가 성공적으로 업데이트되었습니다.";

        $user_skills_stmt->execute();
        $user_skill_ids = $user_skills_stmt->fetchAll(PDO::FETCH_COLUMN);

    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "스킬 정보 업데이트 중 오류가 발생했습니다: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>스킬 편집 - 나의 이력서 관리</title>
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
            flex-grow: 1; 
            padding: 2rem; 
            margin-left: 280px; 
            background-color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <?php display_sidebar('/resume/index.php');  ?>
        <main class="main-content">
            <div class="container mx-auto px-4 py-8">
                <h1 class="text-3xl font-bold mb-8 text-gray-800">스킬 편집</h1>

                <?php if (!empty($success_message)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md" role="alert">
                        <p class="font-bold">성공</p>
                        <p><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error_message)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                        <p class="font-bold">오류</p>
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                <?php endif; ?>

                <form action="edit_skills.php" method="POST" class="bg-white shadow-xl rounded-lg px-8 pt-6 pb-8 mb-4">
                    <?php if (empty($all_skills_data) && empty($error_message)): ?>
                        <p class="text-gray-600 text-center py-4">등록된 스킬 카테고리 또는 스킬이 없습니다. 관리자 페이지에서 스킬 및 카테고리를 추가해주세요.</p>
                    <?php else: ?>
                        <?php foreach ($all_skills_data as $category_name => $skills_in_category): ?>
                            <div class="mb-8">
                                <h2 class="text-xl font-semibold text-gray-700 mb-4 pb-2 border-b border-gray-200"><?php echo htmlspecialchars($category_name); ?></h2>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                    <?php foreach ($skills_in_category as $skill): ?>
                                        <label class="flex items-center space-x-3 p-3 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors cursor-pointer">
                                            <input type="checkbox" name="skill_ids[]" value="<?php echo $skill['reference_id']; ?>"
                                                <?php echo in_array($skill['reference_id'], $user_skill_ids) ? 'checked' : ''; ?>
                                                class="form-checkbox h-5 w-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                            <span class="text-gray-800"><?php echo htmlspecialchars($skill['name']); ?></span>
                                            <?php if (!empty($skill['url'])): ?>
                                                <a href="<?php echo htmlspecialchars($skill['url']); ?>" target="_blank" rel="noopener noreferrer" class="text-blue-500 hover:text-blue-700 text-xs ml-auto" title="참고 자료">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                </a>
                                            <?php endif; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($all_skills_data) || !empty($error_message)):?>
                    <div class="flex items-center justify-start mt-10 pt-6 border-t border-gray-200">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-md focus:outline-none focus:shadow-outline transition-colors">
                            스킬 저장
                        </button>
                        <a href="/resume/index.php" class="ml-4 inline-block align-baseline font-semibold text-sm text-gray-600 hover:text-gray-800">
                            이력서로 돌아가기
                        </a>
                    </div>
                    <?php elseif (empty($error_message)): ?>
                     <div class="flex items-center justify-start mt-10 pt-6 border-t border-gray-200">
                        <a href="/resume/index.php" class="inline-block align-baseline font-semibold text-sm text-gray-600 hover:text-gray-800">
                            이력서로 돌아가기
                        </a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </main>
    </div>
</body>
</html>