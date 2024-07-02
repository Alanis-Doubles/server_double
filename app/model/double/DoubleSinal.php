<?php

use Adianti\Database\TRecord;

class DoubleSinal extends DoubleRecord
{
    const TABLENAME  = 'double_sinal';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';

    use RecordTrait;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        $this->loadAttributes('double');
    }

    public static function buscarSinal($ant, $inicio, $plataforma_id, $call_status){
        $list = [];
        $status = '';
        do {
            sleep(1);
            $sinais = TUtils::openFakeConnection('double', function () use ($plataforma_id, $inicio) {
                return self::select()
                    ->where('plataforma_id', '=', $plataforma_id)
                    ->where('created_at', '>=', $inicio)
                    ->orderBy('created_at', 'desc')
                    ->take(15)
                    ->load();
            }); 

            $list = [];
            $last = [];
            foreach ($sinais as $key => $sinal) {
                $list[] = [
                    'numero' => $sinal->numero, 
                    'cor' => $sinal->cor, 
                    'created_at' => $sinal->created_at
                ];
            }
            $status = $call_status();
        } while ($list == $ant AND $status == 'EXECUTANDO');
        
        if ($status != 'EXECUTANDO')
            throw new Exception("Ocorreu um erro interno. [" . $status . "]");

        return $list;
    }

    public static function buscarUltimoSinal($ant, $inicio, $plataforma_id, $call_status){
        $list = [];
        $status = '';
        $ref_ant = (isset($ant[0])) ? $ant[0]['id_referencia'] : '';
        do {
            sleep(1);
            $ultimo_sinal = TUtils::openFakeConnection('double', function () use ($plataforma_id, $inicio) {
                return self::where('plataforma_id', '=', $plataforma_id)
                    ->last();
            }); 

            $list = [];
            $list[] = [
                'numero' => $ultimo_sinal->numero, 
                'cor' => $ultimo_sinal->cor, 
                'created_at' => $ultimo_sinal->created_at,
                'id_referencia' => $ultimo_sinal->id_referencia
            ];
            $status = $call_status();

        } while ($list[0]['id_referencia'] === $ref_ant AND $status == 'EXECUTANDO');
        
        if ($status != 'EXECUTANDO')
            throw new Exception("Ocorreu um erro interno. [" . $status . "]");

        return $list;
    }
}
