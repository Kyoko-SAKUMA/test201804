<?php
/**
 * ツイート検索・添付画像保存設定
 */

// Twitter API設定
define('CONSUMER_KEY', '');
define('CONSUMER_KEY_SECRET', '');
define('ACCESS_TOKEN', '');
define('ACCESS_TOKEN_SECRET', '');

// 検索設定（テスト用のため固定）
define('SEARCH_KEYWORD', 'JustinBieber');
define('SEARCH_LIMIT', 10);

if (substr(PHP_OS, 0, 3) == 'WIN') {
	define('IS_WINDOWS', true);
	define('DIR_SEPARATE_STR', '\\');
} else {
	define('IS_WINDOWS', false);
	define('DIR_SEPARATE_STR', '/');
}
