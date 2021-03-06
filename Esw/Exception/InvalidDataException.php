<?php
/**
 * Created by PhpStorm.
 * User: gk
 * Date: 2019/5/17
 * Time: 22:33
 */

namespace Esw\Exception;

use Exception;

/**
 * 数据解析错误 一般为客户端数据错误
 * Class InvalidDataException
 * @package Esw\Exception\InvalidDataException
 */
class InvalidDataException extends Exception
{
    public function __construct($message = "", $code = 500, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}