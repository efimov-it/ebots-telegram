=== EBots (Telegram Sender) ===
Contributors: efimov-it
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later

Minimal helper to send Telegram messages from WordPress.

== Usage ==

1) Активируй плагин.

2) Зарегистрируй бота (в functions.php темы, или в любом раннем хукe, например 'init'):

```php
add_action('init', function(){
    ebots_register('main', '1234567890:ABCDEF…'); // slug => token
});
```

Или:
```php
do_action('ebots_register', 'main', '1234567890:ABCDEF…');
```

3) Отправляй сообщение:
```php
$res = ebots_send('main', -1001234567890, "Привет из WordPress!");
if (!$res['ok']) error_log('EBots error: ' . $res['error']);
```

== Filters ==

- `ebots_send_parse_mode` — изменить parse_mode (по умолчанию `MarkdownV2`).
- `ebots_send_payload` — модифицировать payload перед отправкой.
- `ebots_send_endpoint` — заменить endpoint, если нужно проксирование.