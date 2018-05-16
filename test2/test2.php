<?php
/**
 * クローラー
 *
 * 起点URLはsetting.phpにて設定
 */
require_once('setting.php');
require_once('Crawler.class.php');

echoLog('[start] クローリングを開始します。');

try {
	$crawler = new Crawler(TARGET_URL);
	$crawler->startCrawling();
} catch (Exception $e) {
	echoLog($e->getMessage());
	exit(1);
}

echoLog('[end] クローリングが完了しました。');



/**
 * ログ出力
 *
 * @param string $logText 出力するテキスト
 */
function echoLog(string $logText)
{
	echo date('Y-m-d H:i:s') . '｜' . $logText . PHP_EOL;
}
