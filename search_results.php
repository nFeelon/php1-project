<?php
require_once './includes/auth_check.php';
require_once './includes/VideoManager.php';

$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';

$allowed_sort_methods = ['relevance', 'date_desc', 'date_asc', 'views_desc', 'views_asc'];
if (!in_array($sort, $allowed_sort_methods)) {
    $sort = 'relevance';
}

$filters = [
    'subscriptions' => isset($_GET['subscriptions']) ? true : null,
    'viewed' => isset($_GET['viewed']) ? true : null,
    'unviewed' => isset($_GET['unviewed']) ? true : null,
    'liked' => isset($_GET['liked']) ? true : null,
    'disliked' => isset($_GET['disliked']) ? true : null
];

$filters = array_filter($filters);

$videoManager = new VideoManager();
$searchResults = [];
if (!empty($query)) {
    $searchResults = $videoManager->searchVideos($query, 20, $sort, $filters);
}

$title = 'Результаты поиска: ' . htmlspecialchars($query);
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> | TrueWatch</title>
    <link rel="icon" type="image/png" href="img/favicon.png">

    <link rel="preload" href="css/reset.css" as="style">
    <link rel="preload" href="css/variables.css" as="style">
    <link rel="preload" href="css/fonts.css" as="style">
    <link rel="preload" href="css/general.css" as="style">

    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/general.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/video-grid.css">
    <link rel="stylesheet" href="css/search.css">
    <link rel="stylesheet" href="css/sort_menu.css">
</head>

<body>
    <?php include 'search.php'; ?>
    <?php include 'navbar.php'; ?>

    <div id="main-content">
        <div class="container">
            <div class="search-results-header">
                <h1>Результаты поиска: "<?php echo htmlspecialchars($query); ?>"</h1>
            </div>

            <?php if (empty($query)): ?>
                <div class="empty-message">Введите запрос для поиска видео</div>
            <?php else: ?>
                <div class="search-results-info">
                    <div class="results-count">
                        <?php if (empty($searchResults)): ?>
                            По запросу "<?php echo htmlspecialchars($query); ?>" ничего не найдено
                        <?php else: ?>
                            Найдено видео: <?php echo count($searchResults); ?>
                        <?php endif; ?>
                    </div>

                    <div class="search-controls">
                        <?php
                        $base_url = 'search_results.php';
                        $extra_params = ['query' => $query];

                        foreach ($filters as $key => $value) {
                            $extra_params[$key] = 1;
                        }

                        $current_sort = $sort;
                        include 'components/sort_menu.php';
                        ?>

                        <button class="filters-button">
                            <span>Фильтры</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="search-filters-container">
                    <div class="filters-content">
                        <?php
                        function createFilterUrl($query, $sort, $filters, $filterName)
                        {
                            $url = 'search_results.php?query=' . urlencode($query) . '&sort=' . $sort;
                            $newFilters = $filters;

                            if (isset($newFilters[$filterName])) {
                                unset($newFilters[$filterName]);
                            } else {
                                $newFilters[$filterName] = true;

                                if ($filterName === 'viewed') {
                                    unset($newFilters['unviewed']);
                                }
                                if ($filterName === 'unviewed') {
                                    unset($newFilters['viewed']);
                                }
                                if ($filterName === 'liked') {
                                    unset($newFilters['disliked']);
                                }
                                if ($filterName === 'disliked') {
                                    unset($newFilters['liked']);
                                }
                            }
                            foreach ($newFilters as $name => $value) {
                                if ($value) {
                                    $url .= '&' . $name . '=1';
                                }
                            }

                            return $url;
                        }
                        ?>
                        <div class="filters-list">
                            <a href="<?php echo createFilterUrl($query, $sort, $filters, 'subscriptions'); ?>"
                                class="filter-item <?php echo isset($filters['subscriptions']) ? 'active' : ''; ?>"
                                data-filter="subscriptions">
                                Подписки
                            </a>
                            <a href="<?php echo createFilterUrl($query, $sort, $filters, 'unviewed'); ?>"
                                class="filter-item <?php echo isset($filters['unviewed']) ? 'active' : ''; ?>"
                                data-filter="unviewed">
                                Не просмотренное
                            </a>
                            <a href="<?php echo createFilterUrl($query, $sort, $filters, 'viewed'); ?>"
                                class="filter-item <?php echo isset($filters['viewed']) ? 'active' : ''; ?>"
                                data-filter="viewed">
                                Просмотренное
                            </a>
                            <a href="<?php echo createFilterUrl($query, $sort, $filters, 'liked'); ?>"
                                class="filter-item <?php echo isset($filters['liked']) ? 'active' : ''; ?>"
                                data-filter="liked">
                                С моим лайком
                            </a>
                            <a href="<?php echo createFilterUrl($query, $sort, $filters, 'disliked'); ?>"
                                class="filter-item <?php echo isset($filters['disliked']) ? 'active' : ''; ?>"
                                data-filter="disliked">
                                С моим дизлайком
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (!empty($searchResults)): ?>
                    <div class="video-grid">
                        <?php foreach ($searchResults as $video): ?>
                            <?php include 'components/video_card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/search.js"></script>
    <script src="js/sort_menu.js"></script>
    <script src="js/search_filters.js"></script>
    <script src="js/utils.js"></script>
</body>

</html>