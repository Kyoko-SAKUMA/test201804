<?php
/**
 * Twitter使用状況取得API用クラス
 */
class TwitterGetStatusApi extends TwitterApi {

	private const END_POINT = 'https://api.twitter.com/1.1/application/rate_limit_status.json';
	private const REQUEST_METHOD = 'GET';

	/**
	 * 使用状況取得
	 *
	 * @param array $searchParams 検索用パラメータ
	 * @return array 使用状況
	 */
	public static function getStatus(array $searchParams): array
	{
		$result = self::_execApi(self::END_POINT, $searchParams, self::REQUEST_METHOD);
		return $result['resources'] ?? [];
	}
}
