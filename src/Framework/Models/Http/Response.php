<?php

namespace Counos\CounosPay\Framework\Models\Http;


use Counos\CounosPay\Framework\Foundations\Model;

/**
 * Class Response
 * @package Counos\CounosPay\Models\Http
 * @property array headers
 * @property string body
 * @property int http_code
 * @property string error
 */
class Response extends Model
{
    public function getJsonBody()
    {
        $_out = json_decode($this->body, false);
        return $_out;
    }
}