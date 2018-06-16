<?php

class Avanza extends Bank
{
        const BASE_URL = "https://www.avanza.se";
        const LOGIN_URL = "https://www.avanza.se/_api/authentication/sessions/usercredentials";
        const OVERVIEW_URL = "https://www.avanza.se/_mobile/account/overview";
        const OTP_URL = 'https://www.avanza.se/_api/authentication/sessions/totp';

        private $otpsecret = null;

        public function setCredentials($ssn, $pin)
        {
                parent::setCredentials($ssn, $pin);
        }

        public function setOtpSecret($otpsecret) {
                $this->otpsecret = $otpsecret;
        }

        private function getHeaderAssocArray($header)
        {
                $header_array = [];
                $header_p = preg_replace('/\n /i', " ", $header);
                $header_p = preg_replace('/\n\s/i', "", $header_p);
                $header_p = explode("\n", $header_p);
                foreach ($header_p as $rad) {
                        $h = substr($rad, 0, strpos($rad, ":"));
                        $d = substr($rad, strpos($rad, ":") + 1);
                        $header_array[strtolower($h)] = trim($d);
                }
                return $header_array;
        }


        public function getAccounts()
        {
                parent::getAccounts();
                $params = [
                        'maxInactiveMinutes' => 30,
                        'password' => $this->pin,
                        'username' => $this->ssn,
                ];
                list($headers, $data) = $this->curlwrapper->getData(self::LOGIN_URL, "", $params, 'json');
                $dataarr = json_decode($data, true);

                if (isset($dataarr['twoFactorLogin']) && isset($dataarr['twoFactorLogin']['transactionId'])) {
                        $transactionid = $dataarr['twoFactorLogin']['transactionId'];
                        $params = [
                                'method' => 'TOTP',
                                'totpCode' => $this->otp->getOtp($this->otpsecret),
                        ];
                        $extra_headers = [
                                'Cookie' => 'AZAMFATRANSACTION=' . $transactionid
                        ];
                        list($headers, $data) = $this->curlwrapper->getData(self::OTP_URL, "", $params, 'json', $extra_headers);
                } else {
                        throw new Exception("Login with TOTP doesn't work");
                }

                $dataarr = json_decode($data, true);
                if (isset($dataarr['authenticationSession'])) {
                        $authsession = $dataarr['authenticationSession'];
                        $pushsubid = $dataarr['pushSubscriptionId'];
                        $customerid = $dataarr['customerId'];
                        $headers = $this->getHeaderAssocArray($headers);
                        $securitytoken = $headers['x-securitytoken'];

                } else {
                        throw new Exception("Shit went sideways, fast");
                }

                $addonheaders = [
                        'X-AuthenticationSession' => $authsession,
                        'X-SecurityToken' => $securitytoken,
                ];
                list($headers, $data) = $this->curlwrapper->getData(self::OVERVIEW_URL, "", [], 'json', $addonheaders);
                $data = json_decode($data, true);
                return $data['accounts'];
        }
}