<?php
require_once("../base/Bank.class.php");
require_once("../utils/CurlWrapper.class.php");
require_once("../banks/Avanza.class.php");

$bank = new Avanza();
$accounts = [];
$username = "";
$password = "";

try {
        $bank->setCredentials($username, $password);
        $accounts = $bank->getAccounts();
} catch (Exception $e) {
        echo "Something broke: " . $e->getMessage() . "\n\n";
        die();
}

// List all accounts
foreach ($accounts as $account) {
        echo "Account " . $account['name'] . ", balance: " . $account['ownCapital'] . "\n";
}
