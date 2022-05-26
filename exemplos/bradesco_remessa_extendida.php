<?php
require 'autoload.php';
$beneficiario = new \LeonardoMax\LaravelBoleto\Pessoa(
    [
        'nome'      => 'ACME',
        'endereco'  => 'Rua um, 123',
        'cep'       => '99999-999',
        'uf'        => 'UF',
        'cidade'    => 'CIDADE',
        'documento' => '99.999.999/9999-99',
    ]
);

$pagador = new \LeonardoMax\LaravelBoleto\Pessoa(
    [
        'nome'      => 'Cliente',
        'endereco'  => 'Rua um, 123',
        'bairro'    => 'Bairro',
        'cep'       => '99999-999',
        'uf'        => 'UF',
        'cidade'    => 'CIDADE',
        'documento' => '999.999.999-99',
    ]
);

$boleto = new LeonardoMax\LaravelBoleto\Boleto\Banco\Bradesco(
    [
        'logo'                   => realpath(__DIR__ . '/../logos/') . DIRECTORY_SEPARATOR . '237.png',
        'dataVencimento'         => new \Carbon\Carbon(),
        'valor'                  => 100,
        'multa'                  => false,
        'juros'                  => false,
        'numero'                 => 1,
        'diasBaixaAutomatica'    => 2,
        'numeroDocumento'        => 1,
        'pagador'                => $pagador,
        'beneficiario'           => $beneficiario,
        'carteira'               => '09',
        'agencia'                => 1111,
        'conta'                  => 9999999,
        'descricaoDemonstrativo' => ['demonstrativo 1', 'demonstrativo 2', 'demonstrativo 3'],
        'instrucoes'             => ['instrucao 1', 'instrucao 2', 'instrucao 3'],
        'aceite'                 => 'S',
        'especieDoc'             => 'DM',
        'chaveNfe'               => '12345678901234567890123456789012345678901234'
    ]
);

$remessa = new \LeonardoMax\LaravelBoleto\Cnab\Remessa\Cnab400\Banco\Bradesco(
    [
        'idRemessa'     => 1,
        'agencia'       => 1111,
        'carteira'      => '09',
        'conta'         => 99999999,
        'contaDv'       => 9,
        'codigoCliente' => '12345678901234567890',
        'beneficiario'  => $beneficiario,
    ]
);
$remessa->addBoleto($boleto);

echo $remessa->save(__DIR__ . DIRECTORY_SEPARATOR . 'arquivos' . DIRECTORY_SEPARATOR . 'bradesco_ext.txt');
