<?php
declare(strict_types = 1);

namespace App\Libs;

class CPanelClient
{
	private $curl;	
	private string $host;
	private string $username;
	private string $password;
	
	private int $version;
	
	function __construct(string $host, string $username, string $password, int $version = 2)
	{
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
		$this->version = $version;
		
		$this->curl = curl_init();
		
		curl_setopt_array($this->curl, array(
			CURLOPT_SSL_VERIFYPEER => false, 	// Allow self-signed certs
			CURLOPT_SSL_VERIFYHOST => false,	// Allow certs that do not match the hostname
			CURLOPT_HEADER => false,			// Exclude headers from output
			CURLOPT_RETURNTRANSFER => true,		// Return contents
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 10
			));
	}
	
	function __destruct()
	{
		curl_close($this->curl);
	}
	
	function send(string $module, string $function, array $params = []) : array
	{
		$queryParams = http_build_query($params);
		$query = "/json-api/cpanel?cpanel_jsonapi_apiversion={$this->version}&cpanel_jsonapi_module={$module}&cpanel_jsonapi_func={$function}&{$queryParams}";
		$header[0] = 'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password);

		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($this->curl, CURLOPT_URL, $this->host . $query);
		
		$response = curl_exec($this->curl);
		
		$json = [];
		
		if ($response)
		{
			$json = json_decode($response, true);
			$json = $json['cpanelresult'];
		}	
				
		return $json;
	}
}
