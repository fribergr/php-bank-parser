<?php
class NordeaBank extends Bank
{
	public function setCredentials($ssn, $pin)
	{
		if ( strlen($ssn) !== 10 )
			throw new Exception("Social security number for this bank needs to be in format: YYmmddxxxx");

		if ( strlen($pin) !== 4 )
			throw new Exception("PIN for this bank needs to be in the format: xxxx");
		parent::setCredentials($ssn,$pin);
	}

	public function getAccounts()
	{
		parent::getAccounts();
		$base_url = "https://mobil.seb.se";
		$login_url = $base_url . "/nauth2/Authentication/Auth?SEB_Referer=/801/m4e";
		$postfields = "A3=4&A1=".$this->ssn."&A2=".$this->pin;
		$useragent = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.4 (KHTML, like Gecko)  Chrome/22.0.1229.94 Safari/537.4";

		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $login_url);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($c, CURLOPT_USERAGENT, $useragent);
		curl_setopt($c, CURLOPT_COOKIEJAR, "/tmp/sebcookie");
		curl_setopt($c, CURLOPT_COOKIEFILE, "/tmp/sebcookie");
		curl_setopt($c, CURLOPT_POSTFIELDS, $postfields);
		curl_setopt($c, CURLOPT_POST, true);
		curl_setopt($c, CURLOPT_COOKIESESSION, true);
		$data = curl_exec($c);

		$match = '">([a-zA-Z0-9 ]*)</a></td>|<td class="numeric">([0-9., ]*)</td>';
		$match = str_replace("/","\/",$match);
		$match = "/$match/";

		preg_match_all($match,$data,$match);

		$match[1] = array_filter($match[1]);
		$match[2] = array_filter($match[2]);

		$data = $match[1]+$match[2];
		ksort($data);

		$nyarr = array();

		for ( $i = 0; $i < count($data); $i+=3 )
		{
			$disposable = str_replace('.', '', $data[$i+2]);
			$disposable = str_replace(',', '.', $disposable);
			$balance = str_replace('.', '', $data[$i+2]);
			$balance = str_replace(',', '.', $balance);
			$nyarr[$data[$i]]['balance'] = $balance;
			$nyarr[$data[$i]]['disposable'] = $disposable;
		}
		//Returns array with accountname as key with balance and disposable as subkeys.
		$this->accounts = $nyarr;
		return $this->accounts;
	}
}