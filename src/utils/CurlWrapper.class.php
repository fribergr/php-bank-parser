<?php

class CurlWrapper
{
        protected $cookiejar;

        public function __construct()
        {
                $this->cookiejar = tempnam("/tmp", "Cash");
        }

        public function __destruct()
        {
                unlink($this->cookiejar);
        }

        public function getData($url = "", $ref = "", $param = [], $type = 'normal', $extra_headers = [])
        {
                $ch = curl_init($url);
                if ($type == 'normal') {
                        $header[] = "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
                        $header[] = "Content-Type: application/x-www-form-urlencoded";
                        $header[] = "Cache-Control: max-age=0";
                        $header[] = "Connection: keep-alive";
                        $header[] = "Accept-Language:sv-SE,sv;q=0.8,en-US;q=0.6,en";
                } else {
                        $header[] = "Content-Type: application/json";
                        $header[] = "Accept: */*";
                        $header = $header + $extra_headers;
                        curl_setopt($ch, CURLOPT_HEADER, 1);
                }

                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                if (count($param) > 0) {
                        if ($type == "normal") {
                                $compp = "";
                                foreach ($param as $key => $val) {
                                        $compp .= urlencode($key) . "=" . urlencode($val) . "&";
                                }

                                $compp = trim($compp, "&");
                                curl_setopt($ch, CURLOPT_POST, true);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, $compp);
                        } else {
                                curl_setopt($ch, CURLOPT_POST, true);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($param));
                                $header[] = "Content-Length: " . strlen(json_encode($param));
                        }
                }

                if (strlen($ref) > 0) {
                        curl_setopt($ch, CURLOPT_REFERER, $ref);
                }

                curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiejar);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiejar);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.114 Safari/537.36');

                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                if ($type == "normal") {
                        return curl_exec($ch);
                } else {
                        $response = curl_exec($ch);
                        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                        $header = substr($response, 0, $header_size);
                        $body = substr($response, $header_size);
                        return [$header, $body];
                }
        }
}