<?php
if (!function_exists('render_icon')) {
    // 아이콘 렌더링 함수
    function render_icon($icon_name, $is_active = false) {
        $color = $is_active ? '#5F43FF' : '#505050';
        $icon_path = '/components/icons/sidebar-icons.svg#icon-' . $icon_name;
        return "<svg width='20' height='20' fill='$color'><use xlink:href='$icon_path'></use></svg>";
    }
}

if (!function_exists('display_sidebar')) {
    function display_sidebar($current_page_path = '') {
        // 메뉴 아이템 배열 정의
        $menu_items = [
            ['name' => '홈', 'icon' => 'home', 'path' => '/main/index.php'],
            ['name' => '프로젝트', 'icon' => 'project', 'path' => '/projects/index.php'],
            ['name' => '블로그', 'icon' => 'blog', 'path' => '/blog/index.php'],
            ['name' => '채용 공고', 'icon' => 'job', 'path' => '/jobs/index.php'],
            ['name' => '수강 정보', 'icon' => 'class', 'path' => '/courses/index.php'],
            ['name' => '자기소개서', 'icon' => 'self-intro', 'path' => '/cover-letter/index.php'],
            ['name' => '알고리즘', 'icon' => 'algorithm', 'path' => '/algorithms/index.php'],
            ['name' => '이력서', 'icon' => 'resume', 'path' => '/resume/index.php']
        ];

        // 현재 페이지 URL의 경로 부분만 가져오기
        $current_script_path = $current_page_path ?: strtok($_SERVER["REQUEST_URI"], '?');

echo <<<HTML
<div class="fixed left-0 top-[80px] w-[240px] h-[calc(100vh-80px)] bg-[#f8f9fa] border-r border-[#E5E5EC] py-5 ">
    <nav>
        <ul class="list-none p-0 m-0">
HTML;

        foreach ($menu_items as $item) {
            $is_active = ($current_script_path === $item['path']);
            $text_color = $is_active ? 'text-[#5F43FF]' : 'text-[#505050]';
            $active_class_bg = $is_active ? 'opacity-100' : 'opacity-0 group-hover:opacity-100';
            $icon_svg = render_icon($item['icon'], $is_active);

echo <<<HTML
            <li class="h-[56px] relative group">
                <a href="{$item['path']}" class="block w-full h-full">
                    <div class="absolute w-[210px] h-[56px] left-1/2 -translate-x-1/2 top-0 bg-[#EBE9FB] shadow-[inset_1px_1px_2px_rgba(17,0,116,0.15),inset_-1px_-1px_1px_#FFFFFF] rounded-[12px] {$active_class_bg} transition-opacity"></div>
                    <div class="flex flex-row items-center gap-2 absolute h-[22px] left-[31px] top-1/2 -translate-y-1/2 z-10">
                        {$icon_svg}
                        <span class="font-pretendard font-semibold text-base leading-[140%] tracking-[-0.025em] {$text_color}">{$item['name']}</span>
                    </div>
                </a>
            </li>
HTML;
        }

echo <<<HTML
        </ul>
    </nav>
</div>
HTML;
    }
}

// 이 파일이 직접 요청되었을 때 (예: 테스트 또는 이전 방식의 include/require) 사이드바를 표시합니다.
// 하지만 display_sidebar() 함수를 사용하는 것이 권장됩니다.
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    // display_sidebar(); // 기본값으로 호출하거나, 특정 경로를 전달할 수 있습니다.
    // 또는 아무것도 표시하지 않거나 오류 메시지를 표시할 수 있습니다.
    // echo "Please use display_sidebar() function to render the sidebar.";
}

?>
<!-- Pretendard 폰트 및 Tailwind CSS 스크립트는 각 페이지에서 로드되므로 여기서 중복 제거 -->