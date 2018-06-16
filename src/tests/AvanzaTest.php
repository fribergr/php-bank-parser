<?php
require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . "/../base/Bank.class.php";
require_once __DIR__ . "/../utils/CurlWrapper.class.php";
require_once __DIR__ . "/../utils/OTP.class.php";
require_once __DIR__ . "/../banks/Avanza.class.php";

$bank = new Avanza();
$accounts = [];
$username = "";
$password = "";
$otpsecret = "";

try {
        $bank->setCredentials($username, $password);
        $bank->setOtpSecret($otpsecret);
        $accounts = $bank->getAccounts();
} catch (Exception $e) {
        echo "Something broke: " . $e->getMessage() . "\n\n";
        die();
}

// List all accounts
foreach ($accounts as $account) {
        echo "Account " . $account['name'] . ", balance: " . $account['ownCapital'] . "\n";
}
