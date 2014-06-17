<?php
class NordeaBank extends Bank
{
	// Base constants for application
	const BASE_URL = "https://internetbanken.privat.nordea.se/nsp/";
	const LOGIN_URL = "https://internetbanken.privat.nordea.se/nsp/login";

	// Regexps for different purposes throughout the application
	const REG_PINLOG = '/login([^\"]*)">([^<]*)inloggning<\/a>/i'; // Get link to "Personlig inloggning"
	const REG_ACCOUNT = '/<a href="([^"]*)">([^<]*)<\/a>[^<]*<\/td>[^<]*<td>[^<]*<\/td>[^<]*<td[^>]*>([^<]*)<\/td>/'; // Getting accounts from the overview
	const REG_MAIN = '/<li[^>]*><a[^"]*"(?P<link>.*?)">.*?Vardags[^<]*<\/a><\/li>/'; // Find "Vardagsärenden"-link for "first-page".
	const REG_TRANS = '/<tr[^>]*>[^<]*<td[^>]*>.*?<\/td>[^<]*<td[^>]*>(?P<date>[^<]*)<\/td>[^<]*<td[^>]*>(?P<what>.*?)<\/td>[^<]*<td[^>]*>(?P<category>[^<]*)<\/td>[^<]*<td[^>]*>(?P<cost>[^<]*)<\/td>[^<]*<td[^>]*>(?P<balance>[^<]*)<\/td>[^<]*<td[^>]*>[^<]*<\/td>[^<]*<\/tr>/'; // Don't shoot me...

	protected $homeurl; // Used for reference when querying transactions for different accounts.

	public function setCredentials($ssn, $pin)
	{
		// Nordea requires social security number in exactly 12 numbers.
		if ( strlen($ssn) !== 12 )
			throw new Exception("Social security number for this bank needs to be in format: YYYYmmddxxxx");

		// Nordea only supports PIN in 4 numbers.
		if ( strlen($pin) !== 4 )
			throw new Exception("PIN for this bank needs to be in the format: xxxx");

		parent::setCredentials($ssn,$pin); // Call to base funcationality from Bank-class.
	}

	public function getAccounts()
	{
		parent::getAccounts();
		// Assume OK, lets fetch data.

		// Get login page (with dynamic variables)
		$res = $this->curlwrapper->getData(self::LOGIN_URL);
		$regexp = preg_match_all(self::REG_PINLOG,$res, $matches);
		$lurl = self::BASE_URL . "login" . $matches[1][0]; // Found

		// Get "Light Login" (uid+pin)
		$res = $this->curlwrapper->getData($lurl,self::LOGIN_URL);

		// Find login-form and it's input fields (needed later)
		$form_startpos = strpos($res,"<form ");
		$test = substr($res,$form_startpos);
		$form_endpos = strpos($test,"</form");
		$test = substr($test,0,$form_endpos);

		// This should most probably be cleaned up. Not really nice looking code, but it does the trick.
		$t = explode("<input",$test);
		$datam = array();
		foreach ( $t as $rad )
		{
			$tt = explode(" ",$rad);
			foreach ( $tt as $parmval )
			{
				$ttt = explode("=",$parmval);
				if ( count($ttt) === 2 )
				{
					if ( $ttt[0] == "name" )
						$name = trim($ttt[1],'"');
					if ( $ttt[0] == "value" )
						$datam[$name] = trim($ttt[1],'"');
				}
			}
		}
		$datam['JAVASCRIPT_DETECTED'] = "true";
		$datam['commonlogin$loginLight'] = "Logga in";
		$datam['userid'] = $this->ssn;
		$datam['pin'] = $this->pin;

		// $datam should now contain everything we need for a login attempt, try it.
		$res = $this->curlwrapper->getData(self::LOGIN_URL,$lurl,$datam);

		// Normally this would parse the front-page table with account balance into a php-array
		preg_match_all(self::REG_ACCOUNT,$res,$matches);
		$data = array_combine(array_map("utf8_encode",$matches[2]),array_map("trim",$matches[3]));
		$links = $matches[1];
		$i = 0;
		$arr = array();
		foreach ( $data as $account => $balance )
		{
			$i++;
			$balance = trim(str_replace(".","",$balance)); // Remove punctuation (ex: 1.400,00 for 1400,00 SEK)
			$balance = trim(str_replace(",",".",$balance)); // Replace separation from , to . (ex: 1400,00 to 1400.00)

			$arr[$account]['balance'] = $balance;
			$arr[$account]['id'] = $i; // Might not be needed, yet.
		}
		$this->accounts = $arr;
		preg_match(self::REG_MAIN,$res,$match); 
		$this->homeurl = self::BASE_URL. $match['link']; // Get the URL that will be used for next query (in transactions).
		return $this->accounts;
	}

	private function getAccountLink($accountname)
	{
		$res = $this->curlwrapper->getData($this->homeurl,self::LOGIN_URL);

		preg_match(self::REG_MAIN,$res,$match);
		$this->homeurl = self::BASE_URL . $match['link'];

		// Use the same regexp for finding accounts, loop through it to find correct account link.
		preg_match_all(self::REG_ACCOUNT,$res,$matches);
		$data = array_combine(array_map("utf8_encode",$matches[2]),array_map("trim",$matches[3]));
		$links = $matches[1];
		$i = 0;
		foreach ( $data as $account => $balance )
		{
			if ($accountname === $account)
			{
				return self::BASE_URL . $links[$i];
			}
			$i++;
		}
	}

	public function getTransactions()
	{
		parent::getTransactions();
		foreach ( $this->accounts as $account => $data)
		{
			$link = $this->getAccountLink($account);
			$trans = $this->curlwrapper->getdata($link,self::LOGIN_URL);

			preg_match(self::REG_MAIN,$trans,$match);
			$this->homeurl = self::BASE_URL . $match['link'];

			// To make my life easier when using regexp, just do it on the actual table containing data.
			$trans = substr($trans,stripos($trans,"Header.end"));
			$trans = substr($trans,0,stripos($trans,"</table>"));
			$trans = str_replace("\r\n","",$trans);
			$trans = str_replace("\n","",$trans);

			preg_match_all(self::REG_TRANS,$trans,$matches); // Extract the data using magic.

			$date = array_map('trim',$matches['date']);
			$cost = array_map('trim',$matches['cost']);
			$what = array_map('trim',$matches['what']);
			$cat = array_map('trim',$matches['category']);
			$bal = array_map('trim',$matches['balance']);

			foreach ( $date as $k => $m )
			{
				$what_cur = $what[$k];
				if ( stripos($what_cur,"<a href") !== false )
				{
					// If the transaction is final, it is a link. Otherwise it would just be the name.
					preg_match("/<a[^>]*>(?P<name>.*?)<\/a>/",$what_cur,$ma);
					$what_cur = $ma['name'];
				}
				$this->accounttransactions[$account][] = array(
					"date"=>$m,
					"what"=>$what_cur,
					"balance"=>$bal[$k],
					"cost"=>$cost[$k],
					"category"=>$cat[$k]
				);
			}
		}
		return $this->accounttransactions;
	}
}