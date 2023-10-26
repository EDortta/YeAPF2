<?php
declare(strict_types=1);
namespace YeAPF;

class Result extends KeyData
{
    function __construct($ret_code=200) {
        $this->return = $ret_code;
    }
}
