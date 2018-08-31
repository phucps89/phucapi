<?php

namespace App\Services\Currency\Src;

/**
 * Created by PhpStorm.
 * Account: Phuc
 * Date: 2/14/2017
 * Time: 11:12 AM
 */
class CurrencyService
{
    protected $_formatter;

    public function __construct()
    {
        $formatter = new \NumberFormatter('en-US', \NumberFormatter::CURRENCY);
        $this->_formatter = $formatter;
    }

    /**
     * @param float $value
     *
     * @return string
     */
    public function formatUSD(float $value){
        return $this->_formatter->formatCurrency($value, 'USD');
    }
}