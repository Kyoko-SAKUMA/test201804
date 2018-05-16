<?php
/**
 * ツイート検索・添付画像保存（リツイートを除く）
 *
 * 検索条件等はsetting.phpにて設定
 * このファイルと同じディレクトリに「image_201805010000」のようなディレクトリを作成し、そこに画像を保存
 */
require_once('setting.php');
require_once('TwitterApi.class.php');
require_once('TwitterGetStatusApi.class.php');
require_once('TwitterSearchApi.class.php');

echoLog('[start] ツイート画像の保存を開始します。');

try {
	$savedCount = 0;
	$searchParams = [
		'q'						=> SEARCH_KEYWORD . ' filter:images exclude:retweets',
		'count'				=> SEARCH_LIMIT,
		'result_type'	=> 'recent',
		'tweet_mode'	=> 'extended',
	];
	$maxId = '';
	$imageDir = __DIR__ . DIR_SEPARATE_STR . 'image_' . date('YmdHis');

	do {
		// 使用状況チェック
		echoLog('[info] 検索APIの使用状況をチェックします。');
		$statusList = TwitterGetStatusApi::getStatus([
			'resources' => 'search',
		]);
		if (empty($statusList['search']['/search/tweets']['remaining'])) {
			$errMsg = '[error] 検索APIの使用可能回数が残っていません。';
			if (!empty($statusList['search']['/search/tweets']['reset'])) {
				$errMsg .= '使用可能回数リセット日時：' . date('Y-m-d H:i:s', $statusList['search']['/search/tweets']['reset']);
			}
			throw new Exception($errMsg);
		}
		echoLog('[info] 使用可能回数：' . $statusList['search']['/search/tweets']['remaining'] . '回');

		// 検索実行
		echoLog('[info] ツイートの検索を実行します。');
		$tweetList = TwitterSearchApi::search($searchParams);
		$tweetCount = count($tweetList);
		echoLog('[info] 検索結果：' . $tweetCount . '件');
		if (!$tweetCount) {
			break;
		}

		// 画像保存用ディレクトリ作成
		if (!is_dir($imageDir)) {
			if (!@mkdir($imageDir, 0755)) {
				throw new Exception('[error] ディレクトリの作成に失敗しました。');
			}
			echoLog('[success] ディレクトリを作成しました。パス: ' . $imageDir);
		}

		foreach ($tweetList as $tweetData) {
			if (empty($tweetData['entities']['media'])) {
				echoLog('[error] 画像データが存在しません。ID: ' . $tweetData['id_str']);
				continue;
			}
			
			// 1ツイート1画像のみ保存
			foreach ($tweetData['entities']['media'] as $mediaData) {
				$maxId = $tweetData['id_str'];
				if ('photo' != $mediaData['type']) {
					echoLog('[error] ファイル種別が正しくありません。URL: ' . $mediaData['media_url']);
					continue;
				}
				
				// ファイルの中身取得
				$imgData = @file_get_contents($mediaData['media_url'], false);
				if (false === $imgData) {
					echoLog('[error] 画像を取得できませんでした。URL: ' . $mediaData['media_url']);
					continue;
				}
				
				// ファイル名設定（拡張子はオリジナルのまま）
				$fileName = $tweetData['id_str'] . '.' . substr($mediaData['media_url'], strrpos($mediaData['media_url'], '.') + 1);

				// 画像保存
				if (false === @file_put_contents($imageDir . DIR_SEPARATE_STR . $fileName, $imgData)) {
					echoLog('[error] 画像を保存できませんでした。ファイル名: ' . $fileName);
					continue;
				}
				echoLog('[success] 画像を保存しました。ファイル名: ' . $fileName);
				++$savedCount;
				
				if (SEARCH_LIMIT == $savedCount) {
					break 3;
				}
			}
		}
		
		$searchParams['max_id'] = decreaseTweetId($maxId);
		echoLog('[info] 画像を' . SEARCH_LIMIT . '件取得・保存できなかったため、再度検索を行います。');
	} while (true);
} catch (Exception $e) {
	echoLog($e->getMessage());
	exit(1);
}

echoLog('[end] ツイート画像の保存が完了しました。');



/**
 * ログ出力
 *
 * @param string $logText 出力するテキスト
 */
function echoLog(string $logText)
{
	echo date('Y-m-d H:i:s') . '｜' . $logText . PHP_EOL;
}

/**
 * IDを1減算（桁数が大きいため、文字列の置換処理を利用）
 *
 * @param string $idStr ID
 * @return string 減算後のID
 */
function decreaseTweetId(string $idStr): string
{
	$idStrLen = strlen($idStr);
	for ($i = $idStrLen - 1; $i >= 0; $i--) {
		if ($idStr[$i] > 0) {
			$idStr[$i] = $idStr[$i] - 1;
			break;
		}
		if (!$i) {
			return '0';
		} 
		$idStr[$i] = 9;
	}
	return $idStr;
}