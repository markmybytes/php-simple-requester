<?php

namespace HTTPRequester;

class HTTPRequester {

	// ----- HTTP Method Constants -----
	const GET = "GET";
	const POST = "POST";
	const PATCH = "PATCH";
	const DELETE = "DELETE";
	const PUT = "PUT";
	const HEAD = "HEAD";
	
	// ----- Instance Variables -----

	public $response;
	public $responsHeaders;

	protected $url;
	protected $parameters;
	protected $outHeaders;
	protected $payload;

	private $curl;
	private $requested = false;
	private $frozen = false;

	public static function init(string $url) {
		return new HTTPRequester($url);
	}

	public function __construct(string $url) {
		$this->url = $url;
		$this->parameters = $this->outHeaders = $this->payload = $this->responsHeaders = [];

		$this->curl = curl_init($url);
		$this->setOption(CURLOPT_RETURNTRANSFER, true);
		$this->setOption(CURLOPT_HEADERFUNCTION, function($curl, $header) {
			$len = strlen($header);
			$header = explode(':', $header, 2);
			if (count($header) < 2) {
				// ignore invalid headers
				return $len;
			}
			$this->responsHeaders[strtolower(trim($header[0]))][] = trim($header[1]);
			return $len;
		});
	}

	public function __destruct() {
		curl_close($this->curl);
	}
	
	// ----------------------------------------
	//					Setter
	// ----------------------------------------

	/**
	 * set request query parameters
	 * a `?` will be append to the end of the URL
	 * 
	 * @param array $parameters query parameter(s) `['key' => value]`
	 */
	public function withQuery(array $parameters) {
		return $this->setOption(CURLOPT_URL, $this->url."?".http_build_query($parameters));
	}

	/**
	 * set request header
	 * 
	 * @param array $header
	 */
	public function setHeaders(array $header){
		$this->outHeaders = $header;
		$this->setOption(CURLOPT_HTTPHEADER, $header);
		return $this;
	}

	/**
	 * set request payload/body
	 * JSON-encoded `application/json`
	 * 
	 * @param array $payload
	 */
	public function withJson(array $payload) {
		$this->payload = $payload;
		$this->setOption(CURLOPT_POSTFIELDS, json_encode($payload));
		return $this;
	}

	/**
	 * set request payload/body
	 * URL-encoded `application/x-www-form-urlencoded`
	 * 
	 * @param array $payload
	 */
	public function withForm(array $payload) {
		$this->payload = $payload;
		$this->setOption(CURLOPT_POSTFIELDS, http_build_query($payload));
		return $this;
	}

	/**
	 * set request payload/body with no encoding
	 */
	public function withRawPayload($payload) {
		$this->payload = $payload;
		$this->setOption(CURLOPT_POSTFIELDS, $payload);
		return $this;
	}

	/**
	 * set CurlHandle option
	 * see: https://www.php.net/manual/en/function.curl-setopt.php
	 * 
	 * @param array $options curl option
	 * @throws \RuntimeException indicate failure of setting the option
	 */
	public function setOption(int $option, $value) {
		if (curl_setopt($this->curl, $option, $value) === false){
			throw new \RuntimeException('failed to set option');
		}
		return $this;
	}

	/**
	 * set multiple CurlHandle options at once
	 * see: https://www.php.net/manual/en/function.curl-setopt.php
	 * 
	 * @param array $options curl options
	 * @throws \RuntimeException indicate failure of setting options
	 */
	public function setOptions(array $options) {
		if (curl_setopt_array($this->curl, $options) === false){
			throw new \RuntimeException('failed to set option');
		}
		return $this;
	}

	/**
	 * freeze the instance after making the first request
	 * (cannot make further request)
	 */
	public function frozen() {
		$this->frozen = true;
		return $this;
	}

	// ----------------------------------------
	//					Request
	// ----------------------------------------

	public function request(string $method) {
		if ($this->requested && $this->frozen) {
			throw new \BadMethodCallException("frozen, 
				cannot use current instance to make request again.");
		}
		else if (!isset($this->curl)){
			throw new \BadMethodCallException("please call HTTPRequester::init() first");
		}
		else if (!isset($this->curl)){
			throw new \BadMethodCallException("request URL is not set");
		}

		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
		$this->response = curl_exec($this->curl);
		$this->requested = true;
		return $this;
	}

	/**
	 * make a HTTP GET requset  
	 */
	public function get() {
		return $this->request(self::GET);
	}

	/**
	 * make a HTTP POST requset
	 */
	public function post() {
		return $this->request(self::POST);
	}

	/**
	 * make a HTTP PUT requset
	 */
	public function put() {
		return $this->request(self::PUT);
	}
	
	/**
	 * make a HTTP DELETE requset  
	 */
	public function delete() {
		return $this->request(self::DELETE);
	}

	/**
	 * make a HTTP HEAD requset  
	 */
	public function head() {
		return $this->request(self::HEAD);
	}

	// ----------------------------------------
	//					Getter
	// ----------------------------------------

	/**
	 * get the reponse body of last request
	 * 
	 * @return string|null
	 */
	public function response() {
		return $this->response ?? null;
	}

	/**
	 * decodes the json response
	 *
	 * @param bool|null $associative When `true` , JSON objects will be returned as associative `array`s; when `false` , JSON objects will be returned as `object`s. When `null` , JSON objects will be returned as associative `array`s or `object`s depending on whether `JSON_OBJECT_AS_ARRAY` is set in the `flags`.
	 * @param int|null $depth Maximum nesting depth of the structure being decoded. The value must be greater than `0`, and less than or equal to `2147483647`.
	 * @param int|null $flags Bitmask of `JSON_BIGINT_AS_STRING` , `JSON_INVALID_UTF8_IGNORE` , `JSON_INVALID_UTF8_SUBSTITUTE` , `JSON_OBJECT_AS_ARRAY` , `JSON_THROW_ON_ERROR` . The behaviour of these constants is described on the JSON constants page.
	 * @return mixed Returns the value encoded in `json` in appropriate PHP type. Values `true`, `false` and `null` are returned as `true` , `false` and `null` respectively. `null` is returned if the `json` cannot be decoded or if the encoded data is deeper than the nesting limit.
	 */
	public function jsonResponse($associative = null, $depth = 512, $flags = 0) {
		return json_decode($this->response() ?? [], $associative, $depth, $flags);
	}

	/**
	 * decodes the form response
	 */
	public function formResponse() {
		$arr = [];
		parse_str($this->response() ?? "", $arr);
		return $arr;
	}

	/**
	 * get the infomations of the requests
	 * 
	 * see: https://www.php.net/manual/en/function.curl-getinfo.php
	 * 
	 * @param mixed $info `curl_getinfo` supported option
	 * @return mixed if no `$info` is supplied, return an array containing all info
	 */
	public function requestInfo($info = null) {
		if ($info === null) {
			return curl_getinfo($this->curl);
		}
		return curl_getinfo($this->curl, $info);
	}

	/**
	 * get HTTP response status code code
	 * 
	 * @return int
	 */
	public function statusCode() {
		return $this->requestInfo(CURLINFO_HTTP_CODE);
	}

	/**
	 * @return bool `true` if the status code is not 2xx
	 */
	public function failed() {
		return !$this->success();
	}

	/**
	 * @return bool `true` if the status code is 2xx
	 */
	public function success() {
		return 200 <= $this->statusCode() && $this->statusCode() < 300;
	}

	/**
	 * @return bool `true` if the status code is 4xx
	 */
	public function clientError() {
		return 400 <= $this->statusCode() && $this->statusCode() < 500;
	}

	/**
	 * @return bool `true` if the status code is 5xx
	 */
	public function serverError() {
		return 500 <= $this->statusCode() && $this->statusCode() < 600;
	}

	/**
	 * get the response headers
	 * 
	 * @return array<string>
	 */
	public function header() {
		return $this->responsHeaders;
	}

	/**
	 * get effective URL
	 * 
	 * @return string
	 */
	public function url() {
		return $this->requestInfo(CURLINFO_EFFECTIVE_URL);
	}

	/**
	 * get IP address of the most recent connection
	 * 
	 * @return string
	 */
	public function remoteIp() {
		return $this->requestInfo(CURLINFO_PRIMARY_IP);
	}

	/**
	 * get destination port of the most recent connection
	 * 
	 * @return string
	 */
	public function remotePort() {
		return $this->requestInfo(CURLINFO_PRIMARY_PORT);
	}

	/**
	 * `Content-Type` of the requested document. `NULL` indicates server did not send valid `Content-Type:` header
	 */
	public function contentType() {
		return $this->requestInfo(CURLINFO_CONTENT_TYPE);
	}

	public function dump() {
		return [
			'outgoing' => [
				'url' => [
					'original' => $this->url,
					'last' => $this->url()
				],
				'query' => $this->parameters,
				'header' => $this->outHeaders,
				'payload' => $this->payload
			],
			'incoming' => [
				'response' => $this->response ?? null,
				'header' => $this->responsHeaders ?? null,
				'requestInfo' => $this->requestInfo(),
			]
		];
	}

}

?>