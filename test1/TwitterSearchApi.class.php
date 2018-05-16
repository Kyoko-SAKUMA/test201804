<?php
/**
 * Twitter検索API用クラス
 */
class TwitterSearchApi extends TwitterApi {

	private const END_POINT = 'https://api.twitter.com/1.1/search/tweets.json';
	private const REQUEST_METHOD = 'GET';

	/**
	 * 検索実行
	 *
	 * @param array $searchParams 検索用パラメータ
	 * @return array 検索結果
	 */
	public static function search(array $searchParams): array
	{
		$result = self::_execApi(self::END_POINT, $searchParams, self::REQUEST_METHOD);
		return $result['statuses'] ?? [];
	}
}
