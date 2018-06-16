<?php
use OTPHP\TOTP;

class OTP
{
    public function getOtp($otpsecret) {
        $otp = new TOTP('General', $otpsecret);
        return $otp->now();
    }
}