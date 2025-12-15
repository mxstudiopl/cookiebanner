# Cookie Bot

The code will work if you have the jQuery.cookie.js plugin

# Инструкция по использованию Cookie Consent Banner

## Описание

Этот баннер согласия на использование cookies был извлечен из темы WordPress `zielonylisc`. Баннер автоматически появляется через 3 секунды после загрузки страницы, если пользователь еще не дал согласие на использование cookies.

## Структура файлов

В корне проекта находятся следующие файлы:

1. **consent-banner.html** - HTML структура банера
2. **consent-banner.css** - CSS стили для банера
3. **consent.js** - JavaScript логика работы банера
4. **jquery.cookie.js** - jQuery плагин для работы с cookies
5. **example.html** - Пример полной HTML страницы с подключенным баннером

## Установка и подключение

### Вариант 1: Использование на обычной HTML странице

1. Скопируйте все файлы в папку вашего проекта
2. Подключите файлы в вашем HTML документе в следующем порядке:

```html
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ваша страница</title>
    
    <!-- 1. Подключите CSS стили -->
    <link rel="stylesheet" href="consent-banner.css">
    
    <!-- 2. Подключите jQuery (необходимо перед jquery.cookie.js) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- 3. Подключите jQuery Cookie плагин -->
    <script src="jquery.cookie.js"></script>
</head>
<body>
    <!-- Ваш контент страницы -->
    
    <!-- 4. Вставьте HTML структуру банера (из consent-banner.html) -->
    <div id="consent_banner" class="consent_banner">
        <!-- ... содержимое из consent-banner.html ... -->
    </div>
    <div class="consent-overlay"></div>
    
    <!-- 5. Подключите JavaScript логику -->
    <script src="consent.js"></script>
</body>
</html>
```

### Вариант 2: Использование в WordPress

#### Для подключения в теме WordPress:

1. Скопируйте файлы в папку вашей темы (например, в `wp-content/themes/your-theme/`)

2. В файле `functions.php` добавьте подключение скриптов и стилей:

```php
function enqueue_consent_banner_assets() {
    // Подключение CSS
    wp_enqueue_style('consent-banner-css', get_template_directory_uri() . '/consent-banner.css', array(), '1.0.0');
    
    // Подключение jQuery Cookie (если еще не подключен)
    wp_enqueue_script('jquery-cookie', get_template_directory_uri() . '/jquery.cookie.js', array('jquery'), '1.4.1', true);
    
    // Подключение основного скрипта банера
    wp_enqueue_script('consent-banner-js', get_template_directory_uri() . '/consent.js', array('jquery', 'jquery-cookie'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'enqueue_consent_banner_assets');
```

3. В файле `footer.php` перед закрывающим тегом `</body>` вставьте HTML структуру из `consent-banner.html`:

```php
<?php
// Вставьте содержимое из consent-banner.html
?>
<div id="consent_banner" class="consent_banner">
    <!-- ... содержимое ... -->
</div>
<div class="consent-overlay"></div>
```

## Как работает баннер

1. **Автоматический показ**: Баннер появляется через 3 секунды после загрузки страницы, если cookie `consent_cookie` не установлен
2. **Кнопки действий**:
   - **Accept All** - принимает все cookies и сохраняет согласие на 10 лет
   - **Cookie Settings** - открывает панель настроек для выборочного согласия
   - **Reject All** - отклоняет все cookies (кроме необходимых)
3. **Настройки**: Пользователь может выбрать, какие типы cookies разрешить:
   - Storage Cookie (обязательный, нельзя отключить)
   - User Data Cookie
   - Personalization Cookie
   - Analytics Cookie
4. **Сохранение**: После выбора пользователя, настройки сохраняются в cookie `consent_cookie` и страница перезагружается

## Настройка

### Изменение времени задержки показа банера

В файле `consent.js` найдите строку:
```javascript
setTimeout(function () {
    $('#consent_banner').addClass('active');
    $('.consent-overlay').fadeIn(500);
}, 3000)  // Измените 3000 на нужное значение в миллисекундах
```

### Изменение текста и заголовков

Отредактируйте HTML в файле `consent-banner.html` или в вашем шаблоне:
- Заголовок: `<h2>Cookie Consent</h2>`
- Текст: `<p>...</p>`
- Тексты кнопок: `Accept All`, `Cookie Settings`, `Reject All`, `Set Cookie`, `Back`
- Названия типов cookies: `Storage Cookie`, `User Data Cookie`, и т.д.

### Изменение цветов

В файле `consent-banner.css` найдите и измените цвет `#00852D` на нужный вам цвет:
```css
.consent_banner-preview h2 {
    color: #00852D;  /* Измените на ваш цвет */
}
```

### Изменение срока действия cookie

В файле `consent.js` найдите:
```javascript
$.cookie('consent_cookie', jsonData, { expires: 365 * 10, path: '/' });
```

Измените `365 * 10` (10 лет) на нужное количество дней.

## Проверка работы

1. Откройте страницу в браузере
2. Удалите cookie `consent_cookie` через инструменты разработчика (F12 → Application → Cookies)
3. Обновите страницу
4. Через 3 секунды должен появиться баннер
5. После выбора опции, cookie должен быть установлен и баннер больше не должен появляться

## Зависимости

- **jQuery** версии 3.x (подключается через CDN или локально)
- **jquery.cookie.js** версии 1.4.1 (включен в проект)

## Совместимость

Баннер протестирован и работает в современных браузерах:
- Chrome
- Firefox
- Safari
- Edge

## Поддержка мобильных устройств

Баннер адаптирован для мобильных устройств с помощью медиа-запросов в CSS. На экранах меньше 480px кнопки и настройки отображаются в вертикальном расположении.

## Примечания

- Баннер использует jQuery для работы, убедитесь, что jQuery подключен перед другими скриптами
- Cookie сохраняется на 10 лет после принятия согласия
- После отклонения всех cookies, cookie сохраняется на 1 день
- Страница автоматически перезагружается после выбора пользователя

