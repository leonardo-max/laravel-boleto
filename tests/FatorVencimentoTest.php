<?php

namespace LaravelBoleto\Tests;

use Carbon\Carbon;
use Exception;
use LaravelBoleto\Boleto\Banco as Boleto;
use LaravelBoleto\Pessoa;
use LaravelBoleto\Util;

/**
 * Fator de vencimento pela regra da FEBRABAN: de 1000 a 9999, contado desde
 * 07/10/1997; chegou a 9999 em 21/02/2025 e recomeçou em 1000 em 22/02/2025.
 *
 * Os valores esperados foram calculados fora da biblioteca (dias corridos da
 * data-base, módulo 10 e módulo 11 escritos à parte), não copiados da saída dela.
 */
class FatorVencimentoTest extends TestCase
{
    public function testFatorAntesDaVirada()
    {
        $this->assertSame(1000, Util::fatorVencimento('2000-07-03'));
        $this->assertSame(7019, Util::fatorVencimento('2016-12-25'));
        $this->assertSame(9000, Util::fatorVencimento('2022-05-29'));
        $this->assertSame(9743, Util::fatorVencimento('2024-06-10'));
    }

    public function testViradaDe22DeFevereiroDe2025()
    {
        $this->assertSame(9999, Util::fatorVencimento('2025-02-21'));
        $this->assertSame(1000, Util::fatorVencimento('2025-02-22'));
        $this->assertSame(1001, Util::fatorVencimento('2025-02-23'));
    }

    public function testFatorDepoisDaVirada()
    {
        $this->assertSame(1313, Util::fatorVencimento('2026-01-01'));
        $this->assertSame(1636, Util::fatorVencimento('2026-11-20'));
        $this->assertSame(1656, Util::fatorVencimento('2026-12-10'));
        $this->assertSame(9907, Util::fatorVencimento('2049-07-13'));
    }

    public function testFatorIgnoraHoraEFuso()
    {
        $this->assertSame(1636, Util::fatorVencimento(Carbon::create(2026, 11, 20, 23, 59, 59, 'America/Sao_Paulo')));
        $this->assertSame(1636, Util::fatorVencimento(Carbon::create(2026, 11, 20, 0, 0, 1, 'UTC')));
        $this->assertSame(9999, Util::fatorVencimento(Carbon::create(2025, 2, 21, 18, 30, 0, 'America/Sao_Paulo')));
        $this->assertSame('1656', (string) Util::fatorVencimento('10/12/2026', 'd/m/Y'));
    }

    public function testFatorAntesDe2000NaoExiste()
    {
        $this->expectException(Exception::class);
        Util::fatorVencimento('2000-07-02');
    }

    public function testFatorParaDataEscolheOCicloMaisProximoDaReferencia()
    {
        // O mesmo fator 1000 é 03/07/2000 e 22/02/2025.
        $this->assertSame('2000-07-03', Util::fatorVencimentoBack(1000, 'Y-m-d', '2001-01-01'));
        $this->assertSame('2025-02-22', Util::fatorVencimentoBack(1000, 'Y-m-d', '2025-01-10'));
        $this->assertSame('2025-02-22', Util::fatorVencimentoBack('1000', 'Y-m-d', Carbon::create(2026, 11, 5)));

        // 9999 perto da virada continua sendo 21/02/2025, não 2049.
        $this->assertSame('2025-02-21', Util::fatorVencimentoBack(9999, 'Y-m-d', '2025-03-01'));
        $this->assertSame('2024-06-10', Util::fatorVencimentoBack(9743, 'Y-m-d', '2025-03-01'));

        $this->assertSame('2026-11-20', Util::fatorVencimentoBack(1636, 'Y-m-d', '2026-11-05'));
        $this->assertSame('2026-12-10', Util::fatorVencimentoBack('1656', 'Y-m-d', '2026-11-05'));
        $this->assertSame('2016-12-25', Util::fatorVencimentoBack('7019', 'Y-m-d', '2016-01-01'));
        $this->assertSame('2049-07-13', Util::fatorVencimentoBack(9907, 'Y-m-d', '2049-01-01'));

        $this->assertInstanceOf(Carbon::class, Util::fatorVencimentoBack(1636, false, '2026-11-05'));
    }

    public function testFatorEDataSaoInversos()
    {
        foreach (['2000-07-03', '2016-12-25', '2024-06-10', '2025-02-21', '2025-02-22', '2026-01-01', '2026-12-10', '2030-05-17'] as $data) {
            $this->assertSame($data, Util::fatorVencimentoBack(Util::fatorVencimento($data), 'Y-m-d', $data), $data);
        }
    }

    public function testFatorZeroContinuaSendoADataBase()
    {
        $this->assertSame('1997-10-07', Util::fatorVencimentoBack('0000', 'Y-m-d', '2026-11-05'));
    }

    public function testItauAntesDaVirada()
    {
        $boleto = $this->itau('2024-06-10');

        $this->assertSame('34194974300000100411091234567811111222228000', $boleto->getCodigoBarras());
        $this->assertSame('34191091233456781111112222280005497430000010041', Util::onlyNumbers($boleto->getLinhaDigitavel()));
    }

    public function testItauEm2026()
    {
        $boleto = $this->itau('2026-11-20');

        $this->assertSame('34191163600000100411091234567811111222228000', $boleto->getCodigoBarras());
        $this->assertSame('34191091233456781111112222280005116360000010041', Util::onlyNumbers($boleto->getLinhaDigitavel()));

        $variaveis = Util::IPTE2Variveis($boleto->getLinhaDigitavel());
        $this->assertSame('1636', $variaveis['fator_vencimento']);
        $this->assertSame('2026-11-20', $variaveis['vencimento']->format('Y-m-d'));
    }

    /**
     * Todos os bancos: 44 dígitos no código de barras, 47 na linha digitável,
     * e os dígitos verificadores conferidos por conta própria.
     */
    public function testTodosOsBancosGeram44E47DigitosComDvCerto()
    {
        foreach (['2024-06-10', '2025-02-21', '2025-02-22', '2026-11-20', '2026-12-10'] as $data) {
            foreach ($this->boletosDeTodosOsBancos(Carbon::createFromFormat('Y-m-d', $data)->setTime(12, 0, 0)) as $banco => $boleto) {
                $barras = $boleto->getCodigoBarras();
                $linha = Util::onlyNumbers($boleto->getLinhaDigitavel());
                $contexto = "$banco $data";

                $this->assertMatchesRegularExpression('/^\d{44}$/', $barras, $contexto);
                $this->assertMatchesRegularExpression('/^\d{47}$/', $linha, $contexto);
                $this->assertSame(sprintf('%04d', Util::fatorVencimento($data)), substr($barras, 5, 4), $contexto);
                $this->assertSame($this->dvBarras(substr($barras, 0, 4) . substr($barras, 5)), (int) $barras[4], $contexto);
                $this->assertSame($barras, Util::IPTE2CodigoBarras($linha), $contexto);
                foreach ([[0, 9], [10, 10], [21, 10]] as $campo) {
                    $this->assertSame($this->modulo10(substr($linha, $campo[0], $campo[1])), (int) $linha[$campo[0] + $campo[1]], $contexto);
                }
            }
        }
    }

    private function itau($vencimento)
    {
        return new Boleto\Itau([
            'dataVencimento' => Carbon::createFromFormat('Y-m-d', $vencimento)->setTime(0, 0, 0),
            'valor' => 100.41,
            'numero' => 12345678,
            'numeroDocumento' => 1,
            'pagador' => $this->pessoa(),
            'beneficiario' => $this->pessoa(),
            'agencia' => 1111,
            'conta' => 22222,
            'carteira' => 109,
        ]);
    }

    private function boletosDeTodosOsBancos(Carbon $vencimento)
    {
        $comum = [
            'dataVencimento' => $vencimento,
            'valor' => 100.41,
            'numero' => 1,
            'numeroDocumento' => 1,
            'pagador' => $this->pessoa(),
            'beneficiario' => $this->pessoa(),
        ];

        return [
            'Itau' => new Boleto\Itau($comum + ['agencia' => 1111, 'conta' => 22222, 'carteira' => 112]),
            'Bb' => new Boleto\Bb($comum + ['agencia' => 1111, 'conta' => 22222, 'carteira' => 11, 'convenio' => 1234567]),
            'Bradesco' => new Boleto\Bradesco($comum + ['agencia' => 1111, 'conta' => 9999999, 'carteira' => '09']),
            'Caixa' => new Boleto\Caixa($comum + ['agencia' => 1111, 'conta' => 123456, 'carteira' => 'RG', 'codigoCliente' => 999999]),
            'Santander' => new Boleto\Santander($comum + ['agencia' => 1111, 'conta' => 99999999, 'carteira' => 101, 'codigoCliente' => 9999999]),
            'Sicredi' => new Boleto\Sicredi($comum + ['agencia' => 1111, 'conta' => 11111, 'carteira' => 1, 'byte' => 2, 'posto' => 11]),
            'Bancoob' => new Boleto\Bancoob($comum + ['agencia' => 1111, 'conta' => 22222, 'carteira' => 1, 'convenio' => 123123]),
            'Banrisul' => new Boleto\Banrisul($comum + ['agencia' => 1111, 'conta' => 22222, 'carteira' => 1, 'codigoCliente' => 2222222]),
            'Bnb' => new Boleto\Bnb($comum + ['agencia' => 1111, 'conta' => 22222, 'carteira' => 21]),
            'Hsbc' => new Boleto\Hsbc($comum + ['agencia' => 1111, 'conta' => 999999, 'contaDv' => 9, 'carteira' => 'CSB', 'range' => 12345]),
        ];
    }

    private function pessoa()
    {
        return new Pessoa([
            'nome' => 'ACME',
            'endereco' => 'Rua um, 123',
            'bairro' => 'Bairro',
            'cep' => '99999-999',
            'uf' => 'UF',
            'cidade' => 'CIDADE',
            'documento' => '99.999.999/9999-99',
        ]);
    }

    private function modulo10($numero)
    {
        $soma = 0;
        foreach (array_reverse(str_split($numero)) as $i => $digito) {
            $produto = (int) $digito * ($i % 2 === 0 ? 2 : 1);
            $soma += intdiv($produto, 10) + $produto % 10;
        }

        return (10 - $soma % 10) % 10;
    }

    private function dvBarras($numero)
    {
        $soma = 0;
        $peso = 2;
        foreach (array_reverse(str_split($numero)) as $digito) {
            $soma += (int) $digito * $peso;
            $peso = $peso < 9 ? $peso + 1 : 2;
        }
        $resto = 11 - $soma % 11;

        return in_array($resto, [0, 10, 11]) ? 1 : $resto;
    }
}
