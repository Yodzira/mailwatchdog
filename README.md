# Mail Watchdog

**[EN]** wp_mail fails silently: orders, password resets and even WordPress core alerts just disappear. Mail Watchdog is the black box for your site's mail — journal of every outgoing email, plain-language failure reasons, an alert the moment mail breaks, one-click resend.

**[RU]** wp_mail падает молча: заказы, сбросы паролей и даже письма ядра просто исчезают. Mail Watchdog — чёрный ящик почты вашего сайта: журнал всех писем, человеческие причины провалов, алерт в момент поломки, повторная отправка в один клик.

🔗 [**Скачать бесплатно / Download free**](https://github.com/Yodzira/mailwatchdog/releases/latest/download/mailwatchdog.zip)

## Почему это важно

4 миллиона сайтов поставили SMTP-плагины уже после того, как потеряли письма. Mail Watchdog — не SMTP-плагин: он работает ПОВЕРХ любого транспорта и единственный отвечает на вопрос «а дошло ли?»

## Возможности

- журнал каждого исходящего письма: когда, кому, какой плагин отправил, ушло или упало
- причины по-человечески: «сервер отклонил логин/пароль», «адрес получателя неверен»
- алерт при поломке почты: 3 провала подряд → Telegram (опционально) + письмо админу
- Resend: повтор провалившегося письма одной кнопкой
- приватность: тела писем хранятся только для провалов (ради повтора), успешные — без содержимого
- retention 30 дней, чистый uninstall

## Установка / Install

1. Скачайте [`mailwatchdog.zip`](https://github.com/Yodzira/mailwatchdog/releases/latest/download/mailwatchdog.zip)
2. WP-админка → **Плагины → Добавить новый → Загрузить плагин** → zip → Активировать
3. Меню **Mail Watchdog** — журнал наполняется автоматически

## Требования / Requirements

- WordPress 6.0+ (протестировано до 7.1), PHP 7.4+

## Качество / Quality

- PHPUnit (ядро): 5 тестов, 27 assertions ✅ (классификация ошибок, streak-алерт, источник письма, санитизация)
- Интеграция на живом WP 7.1: 14/14 (реальный провал wp_mail → журнал → алерт → сброс) ✅
- Официальный Plugin Checker: 0 errors (release build) ✅
- Uninstall: таблица/опции/крон стёрты ✅

## Лицензия / License

GPL-2.0-or-later (совместимо с WordPress).

💰 **[Купить Pro / Buy Pro — 2 990 ₽/год](https://yodsira.duckdns.org/buy/mail-watchdog)** — лицензия на 1 сайт, 12 месяцев обновлений.
