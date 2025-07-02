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

$pdo = getPDO();

$stmt_profile = $pdo->prepare("SELECT * FROM resume_user_profile WHERE user_id = :user_id LIMIT 1");
$stmt_profile->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_profile->execute();
$profile = $stmt_profile->fetch(PDO::FETCH_ASSOC);

$stmt_channels = $pdo->prepare("SELECT * FROM resume_user_channel WHERE user_id = :user_id ORDER BY platform_name");
$stmt_channels->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_channels->execute();
$channels = $stmt_channels->fetchAll(PDO::FETCH_ASSOC);

$stmt_experience = $pdo->prepare("SELECT * FROM resume_work_experience WHERE user_id = :user_id ORDER BY start_date DESC");
$stmt_experience->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_experience->execute();
$experiences = $stmt_experience->fetchAll(PDO::FETCH_ASSOC);

$stmt_skills = $pdo->prepare("
    SELECT
        rsc.category_name,
        rsr.name AS skill_name,
        rsr.url AS skill_url
    FROM resume_skill rs
    JOIN resume_skill_reference rsr ON rs.skill_id = rsr.reference_id
    JOIN resume_skill_category rsc ON rsr.category_id = rsc.category_id
    WHERE rs.user_id = :user_id
    ORDER BY rsc.category_id, rsr.name
");
$stmt_skills->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_skills->execute();
$skills_data = $stmt_skills->fetchAll(PDO::FETCH_ASSOC);

$stmt_projects = $pdo->prepare("
    SELECT p.*, rp.display_order
    FROM resume_project rp
    JOIN projects p ON rp.project_id = p.id
    WHERE rp.user_id = :user_id
    ORDER BY rp.display_order, p.start_date DESC
");
$stmt_projects->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt_projects->execute();
$resume_projects = $stmt_projects->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>나의 이력서 관리</title>
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
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 600;
            transition: background-color 0.3s ease;
            text-decoration: none;
            display: inline-block;
            font-size: 0.875rem;
        }
        .btn-primary {
            background-color: #5F43FF;
            color: white;
        }
        .btn-primary:hover {
            background-color: #8243FF;
        }
        .btn-secondary {
            background-color: #e2e8f0;
            color: #4a5568;
        }
        .btn-secondary:hover {
            background-color: #cbd5e0;
        }
        .section-card {
            background-color: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-bottom: 1.5rem;
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }
        .item-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }
        .item-card {
            border: 1px solid #e2e8f0;
            padding: 1rem;
            border-radius: 0.375rem;
        }
        .item-card h4 {
            font-weight: 600;
            color: #5F43FF;
        }
        .label {
            font-weight: 500;
            color: #718096;
        }
        .value {
            color: #2d3748;
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
            <a href="/resume/index.php" class="ml-2 inline-flex items-center justify-center px-4 py-2 font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] text-primary hover:text-primary-hover">
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
        <?php display_sidebar('/resume/index.php');?>
        <div class="main-content">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-primary">나의 이력서</h1>
                <?php if (!$profile): ?>
                    <a href="/resume/create_resume.php" class="btn btn-primary">이력서 작성 시작하기</a>
                <?php else: ?>
                <?php endif; ?>
            </div>

            <?php if (!$profile): ?>
                <div class="section-card text-center text-gray-500 py-10">
                    <p class="text-xl mb-4">아직 작성된 이력서가 없습니다.</p>
                    <p>새로운 이력서를 작성하여 당신의 커리어를 관리해보세요!</p>
                </div>
            <?php else: ?>
                <div class="section-card">
                    <div class="flex justify-between items-center">
                        <h2 class="section-title">기본 정보</h2>
                        <a href="/resume/edit_profile.php" class="btn btn-secondary btn-sm">수정</a>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo htmlspecialchars($profile['resume_title']); ?></h3>
                    <p class="mb-1"><span class="label">이메일:</span> <span class="value"><?php echo htmlspecialchars($profile['contact_email']); ?></span></p>
                    <p class="mb-1"><span class="label">연락처:</span> <span class="value"><?php echo htmlspecialchars($profile['phone_number']); ?></span></p>
                    <div class="mt-3">
                        <h4 class="font-semibold text-gray-600">자기소개</h4>
                        <p class="text-gray-700 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($profile['introduction'])); ?></p>
                    </div>
                </div>

                <div class="section-card">
                     <div class="flex justify-between items-center">
                        <h2 class="section-title">채널</h2>
                        <a href="/resume/edit_channels.php" class="btn btn-secondary btn-sm">관리</a>
                    </div>
                    <?php if (!empty($channels)): ?>
                        <div class="item-grid">
                            <?php foreach ($channels as $channel): ?>
                                <div class="item-card">
                                    <h4><?php echo htmlspecialchars($channel['platform_name']); ?></h4>
                                    <a href="<?php echo htmlspecialchars($channel['url']); ?>" target="_blank" class="text-primary hover:underline break-all"><?php echo htmlspecialchars($channel['url']); ?></a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500">등록된 채널 정보가 없습니다.</p>
                    <?php endif; ?>
                </div>

                <div class="section-card">
                    <div class="flex justify-between items-center">
                        <h2 class="section-title">경력 사항</h2>
                        <a href="/resume/edit_experience.php" class="btn btn-secondary btn-sm">추가/수정</a>
                    </div>
                    <?php if (!empty($experiences)): ?>
                        <?php foreach ($experiences as $exp): ?>
                            <div class="mb-4 pb-4 border-b border-gray-200 last:border-b-0">
                                <h4 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($exp['company_name']); ?></h4>
                                <p class="text-md text-primary"><?php echo htmlspecialchars($exp['position']); ?></p>
                                <p class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($exp['start_date']); ?> ~ <?php echo $exp['end_date'] ? htmlspecialchars($exp['end_date']) : '현재 재직중'; ?>
                                </p>
                                <div class="mt-2">
                                    <h5 class="font-semibold text-gray-600">주요 업무 및 성과</h5>
                                    <p class="text-gray-700 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($exp['description'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500">등록된 경력 정보가 없습니다.</p>
                    <?php endif; ?>
                </div>

                <div class="section-card">
                     <div class="flex justify-between items-center">
                        <h2 class="section-title">기술 스택</h2>
                        <a href="/resume/edit_skills.php" class="btn btn-secondary btn-sm">추가/수정</a>
                    </div>
                    <?php if (!empty($skills_data)): ?>
                        <?php
                        $skills_by_category = [];
                        foreach ($skills_data as $skill_item) {
                            $skills_by_category[$skill_item['category_name']][] = $skill_item;
                        }
                        ?>
                        <?php foreach ($skills_by_category as $category_name => $category_skills): ?>
                            <div class="mb-4">
                                <h4 class="font-semibold text-gray-700 text-md mb-2"><?php echo htmlspecialchars($category_name); ?></h4>
                                <div class="flex flex-wrap gap-2 items-center">
                                    <?php foreach ($category_skills as $skill): ?>
                                        <img src="<?php echo htmlspecialchars($skill['skill_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($skill['skill_name']); ?>" 
                                             title="<?php echo htmlspecialchars($skill['skill_name']); ?>" 
                                             class="h-6 md:h-8"> 
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500">등록된 기술 정보가 없습니다.</p>
                    <?php endif; ?>
                </div>

                <div class="section-card">
                    <div class="flex justify-between items-center">
                        <h2 class="section-title">개인 프로젝트</h2>
                        <a href="/resume/manage_projects.php" class="btn btn-secondary btn-sm">연동 프로젝트 관리</a>
                    </div>
                    <?php if (!empty($resume_projects)): ?>
                        <?php foreach ($resume_projects as $project): ?>
                            <div class="mb-4 pb-4 border-b border-gray-200 last:border-b-0">
                                <h4 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($project['name']); ?></h4>
                                <?php if ($project['start_date'] || $project['end_date']): ?>
                                <p class="text-sm text-gray-500">
                                    <?php echo $project['start_date'] ? htmlspecialchars($project['start_date']) : ''; ?>
                                    <?php echo ($project['start_date'] && $project['end_date']) ? ' ~ ' : ''; ?>
                                    <?php echo $project['end_date'] ? htmlspecialchars($project['end_date']) : ($project['start_date'] && !$project['end_date'] && $project['status'] !== '완료' ? ' 진행중' : ''); ?>
                                    <?php if ($project['status']): ?> (<?php echo htmlspecialchars($project['status']); ?>)<?php endif; ?>
                                </p>
                                <?php endif; ?>
                                <p class="text-gray-700 mt-1 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                                <?php if ($project['technologies_used']): ?>
                                <p class="mt-1"><span class="label">사용 기술:</span> <span class="value"><?php echo htmlspecialchars($project['technologies_used']); ?></span></p>
                                <?php endif; ?>
                                <?php if ($project['role']): ?>
                                <p class="mt-1"><span class="label">역할:</span> <span class="value"><?php echo htmlspecialchars($project['role']); ?></span></p>
                                <?php endif; ?>
                                <div class="mt-2">
                                    <?php if ($project['repo_url']): ?>
                                        <a href="<?php echo htmlspecialchars($project['repo_url']); ?>" target="_blank" class="text-primary hover:underline mr-2">저장소</a>
                                    <?php endif; ?>
                                    <?php if ($project['demo_url']): ?>
                                        <a href="<?php echo htmlspecialchars($project['demo_url']); ?>" target="_blank" class="text-primary hover:underline">데모</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500">이력서에 추가된 프로젝트가 없습니다. <a href="/projects/index.php" class="text-primary hover:underline">프로젝트를 먼저 등록</a>하거나, 등록된 프로젝트를 이력서에 추가하세요.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>