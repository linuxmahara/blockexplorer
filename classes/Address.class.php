<?php
class Address {

    static public $version = "00";

    static public function hash160ToAddress($hash160, $version = NULL)
    {
        if($version === NULL)
            $version = self::$version;

        $hash160 = $version . $hash160;
        $check = pack("H*" , $hash160);
        $check = hash("sha256", hash("sha256", $check, true));
        $check = substr($check, 0, 8);
        $hash160 = strtoupper($hash160.$check);
        return encodeBase58($hash160);
    }

    static public function check($addr)
    {
        if(!preg_match("/^[1-9A-HJ-NP-Za-km-z]{25,36}$/", $addr))
            return false;

        $addr = decodeBase58($addr);
        if(strlen($addr) != 50)
            return false;

        $version = substr($addr, 0, 2);
        if(hexdec($version)>hexdec(self::$version))
            return false;

        $check = substr($addr, 0, strlen($addr)-8);
        $check = pack("H*" , $check);
        $check = strtoupper(hash("sha256", hash("sha256", $check, true)));
        $check = substr($check, 0, 8);
        return $check == substr($addr, strlen($addr)-8);
    }

    static public function toHash160($addr)
    {
        $addr = decodeBase58($addr);
        $addr = substr($addr, 2, strlen($addr)-10);
        return $addr;
    }
}
?>