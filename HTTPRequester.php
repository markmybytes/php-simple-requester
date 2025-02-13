<?php

namespace App\Classes;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class HTTPRequester{

	private $response = null;

	/**
	 * generating URL with query parameters
	 * 
	 * @return string encoded URL
	 */
	public static function buildUrl(string $url, array $parameter){
		return $url . http_build_query($parameter);
	}

	public function __construct(string $baseUrl, array $header = null){
		$this->baseUrl = $baseUrl;
		$this->header = $header;

		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		if (!is_null($header)){ curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header); }
	}

	public function __destruct(){
		curl_close($this->curl);
	}

	private function establish(){
		$response = curl_exec($this->curl);
		if($response === false){
            $errmsg = curl_error($this->curl);
            throw new BadRequestHttpException($errmsg);
		}

		$this->response = $response;
		return $response;
	}

	/**
	 * set CurlHandle options  
	 * see: https://www.php.net/manual/en/function.curl-setopt.php
	 * 
	 * @return bool true on success or false on failure.
	 */
	public function setOption(array $options){
		$ok = true;
		foreach ($options as $opt => $val){
			$success = curl_setopt($this->curl, $opt, $val);

			if (!$success && $ok){ $ok = false; }
		}
		return $ok;
	}
	
	/**
	 * make a HTTP POST requset
	 * 
	 * @param array $payload POST request body
	 * @return string response
	 */
	public function get(array $parameter = null){
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($this->curl, CURLOPT_URL, 
			is_null($parameter) ? $this->baseUrl : $this->baseUrl . '?' . http_build_query($parameter));
		return $this->establish();
	}

	/**
	 * make a HTTP POST requset
	 * 
	 * @param array $payload POST request body
	 * @return string response
	 */
	public function post(array $payload = null){
		curl_setopt($this->curl, CURLOPT_URL, $this->baseUrl);
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
		if (!is_null($payload)){
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($payload));
		}
		return $this->establish();
	}

	/**
	 * get reponse body of latest request
	 * 
	 * @return string|null
	 */
	public function getResponse(){
		return $this->response;
	}

	/**
	 * get reponse body of latest request
	 * 
	 * @return int
	 */
	public function getStatusCode(): int{
		return curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
	}

	public function getAllCurlInfo(){
		return curl_getinfo($this->curl);
	}
}

?>