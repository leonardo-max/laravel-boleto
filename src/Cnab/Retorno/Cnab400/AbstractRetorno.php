<?php
namespace LaravelBoleto\Cnab\Retorno\Cnab400;

use LaravelBoleto\Util;
use \LaravelBoleto\Cnab\Retorno\AbstractRetorno as AbstractRetornoGeneric;
use LaravelBoleto\Contracts\Cnab\Retorno\Cnab400\Header as HeaderContract;
use LaravelBoleto\Contracts\Cnab\Retorno\Cnab400\Detalhe as DetalheContract;
use LaravelBoleto\Contracts\Cnab\Retorno\Cnab400\Trailer as TrailerContract;
use Illuminate\Support\Collection;

/**
 * Class AbstractRetorno
 *
 * @method  \LaravelBoleto\Cnab\Retorno\Cnab400\Detalhe getDetalhe($i)
 * @method  \LaravelBoleto\Cnab\Retorno\Cnab400\Header getHeader()
 * @method  \LaravelBoleto\Cnab\Retorno\Cnab400\Trailer getTrailer()
 * @method  \LaravelBoleto\Cnab\Retorno\Cnab400\Detalhe detalheAtual()
 * @package LaravelBoleto\Cnab\Retorno\Cnab400
 */
abstract class AbstractRetorno extends AbstractRetornoGeneric
{
    /**
     * @param String $file
     * @throws \Exception
     */
    public function __construct($file)
    {
        parent::__construct($file);

        $this->header = new Header();
        $this->trailer = new Trailer();
    }

    /**
     * @param array $header
     *
     * @return boolean
     */
    abstract protected function processarHeader(array $header);

    /**
     * @param array $detalhe
     *
     * @return boolean
     */
    abstract protected function processarDetalhe(array $detalhe);

    /**
     * @param array $trailer
     *
     * @return boolean
     */
    abstract protected function processarTrailer(array $trailer);

    /**
     * Incrementa o detalhe.
     */
    protected function incrementDetalhe()
    {
        $this->increment++;
        $this->detalhe[$this->increment] = new Detalhe();
    }

    /**
     * Processa o arquivo
     *
     * @return $this
     * @throws \Exception
     */
    public function processar()
    {
        if ($this->isProcessado()) {
            return $this;
        }

        if (method_exists($this, 'init')) {
            call_user_func([$this, 'init']);
        }

        foreach ($this->file as $linha) {
            $inicio = $this->rem(1, 1, $linha);

            if ($inicio == '0') {
                $this->processarHeader($linha);
            } elseif ($inicio == '9') {
                $this->processarTrailer($linha);
            } else {
                $this->incrementDetalhe();
                if ($this->processarDetalhe($linha) === false) {
                    unset($this->detalhe[$this->increment]);
                    $this->increment--;
                }
            }
        }
        if (method_exists($this, 'finalize')) {
            call_user_func([$this, 'finalize']);
        }

        return $this->setProcessado();
    }

    /**
     * Retorna o array.
     *
     * @return array
     */
    public function toArray()
    {
        $array = [
            'header' => $this->header->toArray(),
            'trailer' => $this->trailer->toArray(),
            'detalhes' => new Collection()
        ];
        foreach ($this->detalhe as $detalhe) {
            $array['detalhes']->push($detalhe->toArray());
        }
        return $array;
    }
}
