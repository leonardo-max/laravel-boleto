<?php
namespace LeonardoMax\LaravelBoleto\Contracts\Boleto;

use LeonardoMax\LaravelBoleto\Boleto\AbstractBoleto;

interface BoletoAPI extends Boleto
{
    /**
     * Return boleto as a Array.
     *
     * @return array
     */
    public function toArrayAPI();

    /**
     * @param $boleto
     * @param $appends
     *
     * @return AbstractBoleto
     */
    public static function createFromAPI($boleto, $appends);
}

