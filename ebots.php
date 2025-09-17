<?php
/**
 * Plugin Name: EBots (Telegram Sender)
 * Description: Минималистичный helper для отправки сообщений в Telegram. Функции: ebots_register($slug, $token), ebots_send($bot_slug, $chat_id, $content).
 * Version: 0.1.0
 * Requires at least: 5.8
 * Requires PHP: 8.0
 * Author: efimov-it
 */

if (!defined('ABSPATH')) { exit; }

define('ebots_VERSION', '0.1.0');
define('ebots_OPT_KEY', 'ebots_bots');

/**
 * Internal: get stored bots map.
 * @return array<string,string> slug => token
 */
function ebots_get_bots(): array {
    $bots = get_option(ebots_OPT_KEY, []);
    if (!is_array($bots)) { $bots = []; }
    return $bots;
}

/**
 * Internal: persist bots map.
 */
function ebots_set_bots(array $bots): void {
    update_option(ebots_OPT_KEY, $bots, false);
}

/**
 * Регистрация бота: сохраняет токен под слагом.
 * Можно вызывать напрямую или через do_action('ebots_register', $slug, $token).
 *
 * @param string $slug
 * @param string $token
 * @return bool
 */
function ebots_register(string $slug, string $token): bool {
    $slug = sanitize_key($slug);
    $token = trim($token);
    if ($slug === '' || $token === '') return false;

    $bots = ebots_get_bots();
    $bots[$slug] = $token;
    ebots_set_bots($bots);
    return true;
}

/**
 * Hook: do_action('ebots_register', $slug, $token) — удобный альтернативный способ регистрации.
 */
add_action('ebots_register', function($slug, $token){
    ebots_register((string)$slug, (string)$token);
}, 10, 2);

/**
 * Простейшее экранирование под MarkdownV2 (по умолчанию Telegram).
 * Если parse_mode сменят на HTML — экранирование не применяется.
 */
function ebots_escape_md_v2(string $s): string {
    return preg_replace_callback(
        '/([\\\\_*\[\]()~`>#+\-=|{}.!])/',
        function($m){ return '\\' . $m[1]; },
        $s
    );
}

/**
 * Отправка сообщения.
 * По умолчанию используется parse_mode MarkdownV2 и отключен превью ссылок.
 * Поведение можно скорректировать через фильтры:
 *   - ebots_send_parse_mode ($mode, $bot_slug, $chat_id, $content)
 *   - ebots_send_payload ($payload, $bot_slug, $chat_id, $content)
 *   - ebots_send_endpoint ($url, $bot_slug)
 *
 * @param string $bot_slug
 * @param int $chat_id
 * @param string $content
 * @return array{ok:bool, error?:string, response?:mixed}
 */
function ebots_send(string $bot_slug, int $chat_id, string $content): array {
    $bots = ebots_get_bots();
    if (empty($bots[$bot_slug])) {
        return ['ok'=>false, 'error'=>'Bot not registered: ' . $bot_slug];
    }
    $token = $bots[$bot_slug];

    $parse_mode = apply_filters('ebots_send_parse_mode', 'MarkdownV2', $bot_slug, $chat_id, $content);
    $text = $content;

    $url = sprintf('https://api.telegram.org/bot%s/sendMessage', $token);
    $url = apply_filters('ebots_send_endpoint', $url, $bot_slug);

    $payload = [
        'chat_id' => (string)$chat_id,
        'text' => $text,
        'parse_mode' => $parse_mode,
        'disable_web_page_preview' => true,
    ];
    $payload = apply_filters('ebots_send_payload', $payload, $bot_slug, $chat_id, $content);

    $args = [
        'timeout' => 15,
        'headers' => ['Accept'=>'application/json'],
        'body'    => $payload,
    ];
    $resp = wp_remote_post($url, $args);
    
    if (is_wp_error($resp)) {
        return ['ok'=>false, 'error'=>$resp->get_error_message()];
    }
    $code = wp_remote_retrieve_response_code($resp);
    $body = wp_remote_retrieve_body($resp);
    $json = json_decode($body, true);

    if ($code !== 200 || !is_array($json) || empty($json['ok'])) {
        $msg = is_array($json) && isset($json['description']) ? $json['description'] : ('HTTP ' . $code . ' ' . $body);
        return ['ok'=>false, 'error'=>$msg, 'response'=>$body];
    }
    return ['ok'=>true, 'response'=>$json];
}

function ebots_send_to_topic(string $bot_slug, int $chat_id, int $topic_id, string $content): array {
    $f = function($payload) use ($topic_id) {
        $payload['message_thread_id'] = $topic_id;
        return $payload;
    };
    add_filter('ebots_send_payload', $f, 10, 1);
    $res = ebots_send($bot_slug, $chat_id, $content);
    remove_filter('ebots_send_payload', $f, 10);
    return $res;
}