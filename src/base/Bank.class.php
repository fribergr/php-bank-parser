<?php

abstract class Bank
{
        protected $curlwrapper;
        protected $ssn;
        protected $pin;
        protected $accounts;
        protected $accounttransactions;

        public function setCredentials($ssn, $pin)
        {
                $this->ssn = $ssn;
                $this->pin = $pin;
                $this->curlwrapper = new CurlWrapper();
                // Should probably test pin here, or get all data here?
        }

        public function getAccounts()
        {
                // Have we already parsed bank? If so, return values.
                if (count($this->accounts) !== 0) {
                        return $this->accounts;
                }

                if (!$this->ssn || !$this->pin) {
                        throw new Exception("You need to run setCredentials(\$ssn, \$pin) before getAccounts()");
                }
        }

        public function getTransactions()
        {
                if (count($this->accounttransactions) !== 0) {
                        return $this->accounttransactions;
                }

                if (count($this->accounts) === 0) {
                        throw new Exception("There's no account information stored. getAccounts must be done first.");
                }
        }
}
