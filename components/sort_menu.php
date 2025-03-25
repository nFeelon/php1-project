<?php
// Устанавливаем значения по умолчанию, если они не были переданы
if (!isset($current_sort)) {
    $current_sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
}

if (!isset($base_url)) {
    $base_url = $_SERVER['PHP_SELF'];
}

if (!isset($extra_params) || !is_array($extra_params)) {
    $extra_params = [];
}

// Определяем доступные методы сортировки в зависимости от страницы
$sort_methods = [];

// Определяем, на какой странице находимся
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Проверяем наличие параметра query в GET-запросе для определения страницы поиска
$is_search_page = ($current_page === 'search_results' || isset($_GET['query']));

if ($is_search_page) {
    // Методы сортировки для страницы поиска
    $sort_methods = [
        'relevance' => 'По релевантности',
        'date_desc' => 'Сначала новые',
        'date_asc' => 'Сначала старые',
        'views_desc' => 'По просмотрам: убывание',
        'views_asc' => 'По просмотрам: возрастание'
    ];
} else {
    // Методы сортировки для других страниц (профиль, канал и т.д.)
    $sort_methods = [
        'date_desc' => 'Сначала новые',
        'date_asc' => 'Сначала старые',
        'views_desc' => 'По просмотрам: убывание',
        'views_asc' => 'По просмотрам: возрастание',
        'title_asc' => 'По названию: А-Я',
        'title_desc' => 'По названию: Я-А'
    ];
}

// Если текущий метод сортировки не существует в доступных методах, устанавливаем по умолчанию
if (!isset($sort_methods[$current_sort])) {
    $current_sort = array_key_first($sort_methods);
}

function getSortUrl($base_url, $sort_method, $extra_params) {
    $params = $extra_params;
    $params['sort'] = $sort_method;
    
    $query_string = http_build_query($params);
    return $base_url . ($query_string ? '?' . $query_string : '');
}
?>

<div class="sort-container">
    <div class="sort-dropdown">
        <button class="sort-button">
            <span>Сортировка: <?php echo $sort_methods[$current_sort]; ?></span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <div class="sort-dropdown-content">
            <?php foreach ($sort_methods as $method => $name): ?>
                <a href="<?php echo getSortUrl($base_url, $method, $extra_params); ?>" 
                   class="sort-item <?php echo $method === $current_sort ? 'active' : ''; ?>">
                    <?php echo $name; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
