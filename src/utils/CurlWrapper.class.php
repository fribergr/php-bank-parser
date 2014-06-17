<?php
class CurlWrapper
{
	protected $cookiejar;

	public function __construct()
	{
		$this->cookiejar = tempnam("/tmp","Cash");
	}

	public function __destruct()
	{
		unlink($this->cookiejar);
	}

	public function getData($url="",$ref="",$param=array())
	{
		$header[] = "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
		$header[] = "Content-Type: application/x-www-form-urlencoded";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Accept-Language:sv-SE,sv;q=0.8,en-US;q=0.6,en";
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		if ( count($param) > 0 )
		{
			$compp = "";
			foreach ( $param as $key => $val )
				$compp .= urlencode($key)."=".urlencode($val)."&";

			$compp = trim($compp,"&");
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $compp);
		}
		if ( strlen($ref) > 0 )
			curl_setopt($ch, CURLOPT_REFERER, $ref);

		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiejar);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiejar);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.114 Safari/537.36');

		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		return curl_exec($ch);
	}
}