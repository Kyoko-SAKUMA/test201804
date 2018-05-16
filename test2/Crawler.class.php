<?php
/**
 * クローリング実行用クラス
 */
class Crawler
{
	private $_baseUrl = '';
	private $_baseScheme = '';
	private $_baseDomain = '';
	private $_urlList = [];

	/**
	 * 初期設定
	 *
	 * @param string $baseUrl 起点URL
	 * @throws Exception 起点URLが不正
	 */ 
	public function __construct(string $baseUrl)
	{
		$parseResult = parse_url($baseUrl);
		if (!isset($parseResult['scheme'], $parseResult['host'])) {
			throw new Exception('[error] 起点URLが正しくありません。');
		}
		$header = @get_headers($baseUrl);
		if (empty($header[0]) || !preg_match('/^HTTP\/.*\s+200\s/i', $header[0])) {
			throw new Exception('[error] 起点URLが存在しません。');
		}
		
		$this->_baseUrl = $baseUrl;
		$this->_baseScheme = $parseResult['scheme'];
		$this->_baseDomain = $parseResult['host'];
		$this->_urlList[] = $baseUrl;
	}

	/**
	 * クローリング処理開始
	 *
	 * @throws Exception 起点URLが未設定
	 */
	public function startCrawling()
	{
		if (!$this->_baseUrl) {
			throw new Exception('[error] 起点URLが設定されていません。');
		}
		$this->_crawl($this->_baseUrl);		
	}

	/**
	 * 再帰的なクローリング処理
	 *
	 * @param string $url URL
	 */
	private function _crawl(string $url)
	{
		$xpath = $this->_generateXPath($url);
		if (false === $xpath) {
			// XPathオブジェクトを取得できない場合は除外
			return false;
		}

		// URLとタイトルを出力
		foreach ($xpath->query('//title') as $elm) {
			echo $url . '　' . $xpath->evaluate('normalize-space()', $elm) . PHP_EOL;
			break;
		}
		
		// ページ内リンクごとに同じ処理を実行
		foreach ($xpath->query('//a[@href != "" and not(starts-with(@href, "#"))]') as $elm) {
			$linkUrl = $xpath->evaluate('string(@href)', $elm);
			$parseResult = parse_url($linkUrl);
			if (!isset($parseResult['host'])) {
				$linkUrl = $this->_baseScheme . '://' . $this->_baseDomain . $linkUrl;
			}
			$domain = $parseResult['host'] ?? $this->_baseDomain;
			if ($this->_baseDomain != $domain || in_array($linkUrl, $this->_urlList, true)) {
				// 外部サイトおよびクローリング済のURLは除外
				continue;
			}
			$this->_urlList[] = $linkUrl;
			$this->_crawl($linkUrl);
		}
	}
	
	/**
	 * XPathオブジェクト生成
	 *
	 * @param string $url URL
	 * @return DOMXPath|bool XPathオブジェクト（取得できない場合false）
	 */
	private function _generateXPath(string $url)
	{
		$html = @file_get_contents($url, false);
		if (false === $html) {
			return false;
		}

		$dom = new DOMDocument();
		if (false === @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'))) {
			return false;
		}

		return new DOMXPath($dom);
	}
}