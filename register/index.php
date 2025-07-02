<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회원가입</title>
    <script src="/tailwind.js"></script>
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'noto': ['Noto Sans KR', 'sans-serif'],
                        'pretendard': ['Pretendard', 'sans-serif'],
                    },
                    colors: {
                        'primary': '#5F43FF',
                        'primary-hover': '#8243FF',
                        'primary-border': '#5F43FF',
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer components {
            .form-input {
                @apply w-full py-4 px-4 text-sm text-gray-600 bg-[#EBE9FB] rounded-[12px] font-noto font-medium shadow-[inset_-1px_-1px_1px_#FFFFFF,_inset_1px_1px_1px_rgba(0,0,0,0.1)] focus:outline-none focus:ring-2 focus:ring-primary;
            }
            .btn-primary {
                @apply w-full py-3 px-4 text-white text-sm font-noto font-normal rounded-[12px] transition-colors border border-transparent;
                background: linear-gradient(90deg, #5F43FF 11.64%, #8243FF 100%);
                box-shadow: 0px 2px 8px rgba(38, 0, 255, 0.3);
            }
        }
    </style>
</head>
<body class="bg-[#F7F7FB]">
    <div class="container mx-auto px-4">
        <div class="flex justify-center items-center min-h-screen">
            <div class="w-full max-w-md">
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6">
                        <h2 class="text-left text-[24pt] mb-2 font-pretendard font-semibold text-[#505050]">Sign up</h2>
                        <p class="text-left text-[10pt] mb-6 font-pretendard font-medium text-[#8243FF]">예비 개발자 커리어, 한번에 관리하세요</p>
                        <form action="user_insert.php" method="post">
                            <div class="mb-4">
                                <div class="mb-4">
                                    <input type="email" class="form-input" name="email" id="email" placeholder="이메일을 입력하세요" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="mb-4">
                                    <input type="text" class="form-input" name="name" id="name" placeholder="이름(실명)을 입력하세요" required>
                                </div>
                            </div>
                    
                            <div class="mb-4">
                                <div class="mb-4">
                                    <input type="password" class="form-input" name="password" id="password" placeholder="비밀번호" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="mb-6">
                                    <input type="password" class="form-input" name="confirm_password" id="confirm_password" placeholder="비밀번호 확인" required>
                                </div>
                            </div>
                            
                            <div>
                                <button type="submit" class="btn-primary">회원가입</button>
                            </div>
                            <div class="mt-8 text-right">
                                <a href="/login" class="font-pretendard font-semibold text-[14px] leading-[140%] tracking-[-0.025em] text-primary hover:text-primary-hover">이미 계정이 있으신가요?</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('비밀번호가 일치하지 않습니다.');
            }
        });
    </script>
</body>
</html>