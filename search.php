<?php
?>

<div class="search-header" style="background-color: #1a1a1a; position: relative; z-index: 10;">
    <div class="container">
        <div class="search-container">
            <form action="search_results.php" method="GET" id="search-form">
                <div class="search-input-wrapper">
                    <input type="text" name="query" id="search-input" placeholder="Маршмеллоу..." autocomplete="off"
                        value="<?php echo isset($_GET['query']) ? htmlspecialchars(trim($_GET['query'])) : ''; ?>">
                    <button type="submit" class="search-button">
                        <img src="img/search.png" alt="Поиск" class="search-icon">
                    </button>
                </div>

                <div class="search-history-container" id="search-history-container">
                    <div class="search-history-header">
                        <span>История поиска</span>
                        <button type="button" id="clear-history-btn">Очистить</button>
                    </div>
                    <ul id="search-history-list">
                    </ul>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/search.js"></script>