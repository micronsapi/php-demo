<?php


class Loggle
{

    /**
     * @param string $msg
     * @param string $prefix
     */
    public function set(string $msg,string $prefix="INFO"){
        echo "[{$prefix}]  $msg".PHP_EOL;
    }

}