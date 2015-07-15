<?php
require_once("../base/Bank.class.php");
require_once("../utils/CurlWrapper.class.php");
require_once("../banks/NordeaBank.class.php");

$bank = new NordeaBank();
$accounts = array();
$ssn = ""; // Pnum
$pin = ""; // PIN

try {
	$bank->setCredentials($ssn,$pin);
	$accounts = $bank->getAccounts();
	$transactions = $bank->getTransactions();
} catch (Exception $e) {
	echo "Something broke: " . $e->getMessage()."\n\n";
	die();
}

// List all accounts
foreach ($accounts as $namn => $data) {
	echo "Account " . $namn . ", balance: " . $data['balance'] . "\n";
	echo "----------------------------------------\n";

	if (!isset($transactions[$namn])) {
		echo "No transactions found on this account.\n\n";
		continue;
	}

	foreach ($transactions[$namn] as $trans) {
		echo $trans['date']."\t".$trans['what']."\t".$trans['cost']."\t".$trans['balance']."\n";
	}

	echo "----------------------------------------\n";
	echo "\n";
}
