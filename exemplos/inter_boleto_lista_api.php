<?php

use LeonardoMax\LaravelBoleto\Boleto\Banco\Inter;

require 'autoload.php';

$beneficiario = new \LeonardoMax\LaravelBoleto\Pessoa(
    [
        'nome' => 'ACME',
        'endereco' => 'Rua um, 123',
        'cep' => '99999-999',
        'uf' => 'UF',
        'cidade' => 'CIDADE',
        'documento' => '99.999.999/9999-99',
    ]
);

$api = new LeonardoMax\LaravelBoleto\Api\Banco\Inter([
    'conta'            => '123456789',
    'certificado'      => realpath(__DIR__ . '/certs/') . DIRECTORY_SEPARATOR . 'cert.crt',
    'certificadoChave' => realpath(__DIR__ . '/certs/') . DIRECTORY_SEPARATOR . 'key.key',
]);

$retorno = $api->retrieveList();
$boletos = [];
if ($list = $retorno->body->content) {
    foreach ($list as $boleto) {
        $boletos[] = Inter::createFromAPI($boleto, [
            'conta'           => '123456789',
            'beneficiario' => $beneficiario,
        ]);
    }
}

$pdf = new LeonardoMax\LaravelBoleto\Boleto\Render\Pdf();
$pdf->addBoletos($boletos);
$pdf->gerarBoleto($pdf::OUTPUT_SAVE, __DIR__ . DIRECTORY_SEPARATOR . 'arquivos' . DIRECTORY_SEPARATOR . 'inter_lista.pdf');
