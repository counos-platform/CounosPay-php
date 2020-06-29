<?php
/**
 * Created by PhpStorm.
 * User: Mojtaba
 * Date: 12/11/2019
 * Time: 5:42 PM
 */

namespace Counos\CounosPay\Models;

use Counos\CounosPay\Framework\Foundations\Collection;


/**
 * Class Ticker
 * @package Counos\CounosPay\Models
 */
class Tickers extends Collection
{
    public function findBySymbol($value)
    {
        $ticker = $this->filter(static function ($ticker) use ($value) {
            /**
             * @var Ticker $ticker
             */
            if (strtolower($ticker->keyword) === strtolower($value))
            {
                return $ticker;
            }
        });

        $ticker = is_array($ticker) && count($ticker) > 0 ? array_values($ticker)[0] : new Ticker();

        return $ticker;
    }
}