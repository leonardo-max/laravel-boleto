<?php

namespace LeonardoMax\LaravelBoleto\Tests\Retorno;

use LeonardoMax\LaravelBoleto\Cnab\Retorno\Cnab400\Detalhe;
use LeonardoMax\LaravelBoleto\Tests\TestCase;
use Exception;
use Illuminate\Support\Collection;

class FactoryTest extends TestCase
{
    public function testCriarEmBranco(){
        $this->expectException(Exception::class);
        $retorno = \LeonardoMax\LaravelBoleto\Cnab\Retorno\Factory::make('');
        $retorno->processar();
    }

    public function testCriarComRemessa(){
        $this->expectException(Exception::class);
        $retorno = \LeonardoMax\LaravelBoleto\Cnab\Retorno\Factory::make(__DIR__ . '/files/cnab400/remessa.txt');
        $retorno->processar();
    }

    public function testCriarComPathQueNaoExiste(){
        $this->expectException(Exception::class);
        $retorno = \LeonardoMax\LaravelBoleto\Cnab\Retorno\Factory::make(__DIR__ . '/files/cnab400/naoexiste.txt');
        $retorno->processar();
    }

    public function testCriarComRetornoBancoNaoExiste(){
        $this->expectException(Exception::class);
        $retorno = \LeonardoMax\LaravelBoleto\Cnab\Retorno\Factory::make(__DIR__ . '/files/cnab400/retorno_banco_fake.ret');
        $retorno->processar();
    }

    public function testCriarComFile()
    {
        $retorno = \LeonardoMax\LaravelBoleto\Cnab\Retorno\Factory::make(__DIR__ . '/files/cnab400/bradesco.ret');
        $retorno->processar();
        $this->assertTrue(true);
    }

    public function testCriarComString()
    {
        $retorno = \LeonardoMax\LaravelBoleto\Cnab\Retorno\Factory::make(file_get_contents(__DIR__ . '/files/cnab400/bradesco.ret'));
        $retorno->processar();
        $this->assertTrue(true);
    }
}
