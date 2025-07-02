<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>사이드바 테스트</title>
    <script src="/tailwind.js"></script>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'pretendard': ['Pretendard', 'sans-serif'],
                    },
                    colors: {
                        'primary': '#5F43FF',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-[#F7F7FB]">
    <div class="flex">
        <?php include 'sidebar.php'; ?>
        
        <div class="ml-[260px] p-6">
            <h1 class="text-2xl font-pretendard font-bold mb-4">사이드바 테스트 페이지</h1>
            <p class="font-pretendard">사이드바가 Tailwind CSS로 성공적으로 구현되었습니다.</p>
            <p class="font-pretendard mt-2">각 메뉴 항목의 높이는 56px이며, 선택된 항목과 호버 효과가 적용되어 있습니다.</p>
        </div>
    </div>
</body>
</html>