<?php
/**
 * TwitterAPIリクエスト用クラス
 */
class TwitterApi {

	// タイムアウト時間（秒）
	private const REQUEST_TIMEOUT = 5;

	/**
	 * API実行
	 *
	 * @param string $endPoint エンドポイント
	 * @param array $searchParams 検索用パラメータ
	 * @param string $method リクエストメソッド
	 * @return array 実行結果
	 * @throws Exception API実行失敗
	 */
	protected static function _execApi(string $endPoint, array $searchParams, string $method): array
	{
		// リクエストパラメータ生成
		$requestParamArray = self::_generateRequestParams($searchParams);
		$requestParamArray['oauth_signature'] = self::_generateSignature($endPoint, $requestParamArray, $method);

		// リクエストURL設定
		$requestUrl = $endPoint;
		if ($searchParams) {
			$requestUrl .= '?' . http_build_query($searchParams);
		}

		$curl = curl_init($requestUrl);
		curl_setopt_array($curl, [
			CURLOPT_CUSTOMREQUEST		=> $method,
			CURLOPT_SSL_VERIFYPEER	=> false,
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_TIMEOUT					=> self::REQUEST_TIMEOUT,
			CURLOPT_HTTPHEADER			=> [
				'Authorization: OAuth ' . http_build_query($requestParamArray, '', ','),
			],
		]);
		$curlResult		= curl_exec($curl);
		$curlInfo			= curl_getinfo($curl);
		$curlErrorNo	= curl_errno($curl);
		curl_close($curl);

		if (CURLE_OK != $curlErrorNo) {
			throw new Exception('[error] cURLの実行に失敗しました。エラーコード: ' . $curlErrorNo);
		}
		if ('200' != $curlInfo['http_code']) {
			throw new Exception('[error] APIの実行に失敗しました。HTTPステータスコード: ' . $curlInfo['http_code']);
		}

		return json_decode($curlResult, true);
	}

	/**
	 * リクエストパラメータ生成
	 *
	 * @param array 検索パラメータ
	 * @return array リクエストパラメータ
	 */
	private static function _generateRequestParams(array $searchParams): array
	{
		$requestParamArray = array_merge($searchParams, [
			'oauth_token' 						=> ACCESS_TOKEN,
			'oauth_consumer_key'			=> CONSUMER_KEY,
			'oauth_signature_method'	=> 'HMAC-SHA1',
			'oauth_timestamp'					=> time(),
			'oauth_nonce'							=> microtime(),
			'oauth_version'						=> '1.0',
		]);
		ksort($requestParamArray);
		return $requestParamArray;
	}

	/**
	 * 署名作成
	 *
	 * @param string $endPoint エンドポイント
	 * @param array $params リクエストパラメータ
	 * @param string $method リクエストメソッド
	 * @return string 署名
	 */
	private static function _generateSignature(string $endPoint, array $params, string $method = 'GET'): string
	{
		return base64_encode(
			hash_hmac(
				'sha1',
				rawurlencode($method) . '&' . rawurlencode($endPoint) . '&' . rawurlencode(str_replace(['+', '%7E'], ['%20', '~'], http_build_query($params, '', '&'))),
				rawurlencode(CONSUMER_KEY_SECRET) . '&' . rawurlencode(ACCESS_TOKEN_SECRET),
				true
			)
		);
	}
}
