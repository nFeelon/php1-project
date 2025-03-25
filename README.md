# TrueWatch

## О проекте
TrueWatch - будущий веб-сайт видеохостинг.

### Технический стек

#### Frontend
- HTML5
- CSS3 (с использованием переменных и модульной структуры)
- JavaScript (нативный, без фреймворков)
- Адаптивный дизайн

#### Backend
- PHP
- MySQL (структура базы данных в `/database`)
- REST API для авторизации

## Структура проекта
Подробная структура проекта описана в файле [project_structure.md](./project_structure.md)

## Установка и настройка

### Требования
- PHP 7.4+
- MySQL 5.7+
- Веб-сервер (Apache/Nginx)

### Настройка базы данных
1. Создайте новую базу данных
2. Импортируйте структуру из файла `/database/database.sql`
3. При необходимости импортируйте тестовые данные из `/database/example.sql`

### Конфигурация
Настройки подключения к базе данных находятся в файле `/includes/Database.php`

## Разработка
Проект имеет четкое разделение на frontend и backend части для удобства разработки и поддержки. 

### Frontend
- Стили разделены на модули по функциональности
- Используются CSS-переменные для единообразия стилей
- JavaScript код организован в отдельные модули

### Backend
- Реализована объектно-ориентированная структура
- API endpoints для работы с авторизацией
- Отдельные классы для работы с пользователями и базой данных
