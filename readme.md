# EBots (Telegram Sender)
Contributors: @efimov-it\
Requires at least: 5.8\
Tested up to: 6.6\
Requires PHP: 8.0\

Minimal helper to send Telegram messages from WordPress.

## Usage

1) Activate the plugin.

2) Register the bot (in your theme’s functions.php, or any early hook such as 'init'):

```php
add_action('init', function(){
    ebots_register('main', '1234567890:ABCDEF…'); // slug => token
});
```

Or:
```php
do_action('ebots_register', 'main', '1234567890:ABCDEF…');
```

3) Send a message:
```php
$res = ebots_send('main', -123456789012345, "Hello from WordPress!");
if (!$res['ok']) error_log('EBots error: ' . $res['error']);
```

## Filters

- `ebots_send_parse_mode` — change the parse_mode (default: `MarkdownV2`).
- `ebots_send_payload` — modify the payload before sending.
- `ebots_send_endpoint` — override the endpoint if you need to proxy.