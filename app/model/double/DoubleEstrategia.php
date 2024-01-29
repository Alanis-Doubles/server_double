<?php

use Adianti\Database\TRecord;

class DoubleEstrategia extends DoubleRecord
{
    const TABLENAME  = 'double_estrategia';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max';

    use RecordTrait;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        $this->loadAttributes('double');
    }

    public function validar($sinais, IDoublePlataforma $plataforma)
    {
        switch ($this->tipo) {
            case 'COR':
                $resultado = $this->validarCor($sinais);
                break;
            case 'NUMERO':
                $resultado = $this->validarNumero($sinais);
                break;
            case 'SOMA':
                $resultado = $this->validarSoma($sinais, $plataforma);
                break;

            default:
                $resultado = false;
                break;
        }

        return $resultado;
    }

    public function validarCor($sinais)
    {
        $arrRegra = explode(' - ', $this->regra);
        array_pop($arrRegra);
        $total = count($arrRegra);
        $strRegra = implode(' - ', $arrRegra);
        $valor = '';
        if (count($sinais) >= $total) {
            for ($i = 0; $i < $total; $i++) {
                $valor = $sinais[$i]['cor'] . ($i > 0 ? ' - ' : '') . $valor;
            }
        }
        return $strRegra == $valor;
    }

    public function validarNumero($sinais)
    {
        return $this->regra == $sinais[0]['numero'];
    }

    public function validarSoma($sinais, IDoublePlataforma $plataforma)
    {
        $resultado = false;
        if (count($sinais) >= 3) {
            $possuiBranco = false;
            for ($i = 0; $i < 3; $i++) {
                $possuiBranco = $sinais[$i]['numero'] == 0;
                if ($possuiBranco)
                    return false;
            }

            $sum_value = ($sinais[2]['numero'] + $sinais[1]['numero']) - $sinais[0]['numero'];
            if ($sum_value > count($plataforma->cores()) or $sum_value <= 0)
                return false;

            $this->resultado = $plataforma->cores()[$sum_value];
            $resultado = true;
        }
        return $resultado;
    }

    public function aguardarProximoSinal()
    {
        if ($this->tipo == 'COR')
            return true;
        else
            return false;
    }

    public function validarProximoSinal($sinais)
    {
        if ($this->tipo == 'COR') {
            $arrRegra = explode(' - ', $this->regra);
            $strRegra = array_pop($arrRegra);
            $valor =  $sinais[0]['cor'];
            return $strRegra == $valor;
        } else
            return true;
    }

    public function processarRetorno($sinais)
    {
        return $sinais[0]['cor'] == $this->resultado;
    }
}
