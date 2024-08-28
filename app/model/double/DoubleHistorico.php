<?php

use Adianti\Database\TRecord;

class DoubleHistorico extends DoubleRecord
{
    const TABLENAME  = 'double_historico';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';

    use RecordTrait;

    private $obj_estrategia;
    private $obj_canal;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        $this->loadAttributes('double');
    }

    public static function buscarHistorico($ant, $inicio, $plataforma_id, $canal_id, $call_status, $usuario_id = null){
        $list = [];
        $status = '';
        do {
            sleep(1);
            $historico = TUtils::openFakeConnection('double', function() use ($plataforma_id, $canal_id, $inicio, $usuario_id){
                return self::select()
                    ->where('plataforma_id', '=', $plataforma_id)
                    ->where('canal_id', '=', $canal_id)
                    ->where('created_at', '>=', $inicio)
                    ->where('usuario_id', ($usuario_id ? '=' : 'is'), $usuario_id)
                    ->last();
            });

            if ($historico) {
                $list = [];
                    $estrategia = $historico->estrategia;
                    if ($estrategia)
                        $estrategia->resultado = $historico->cor;
                    $list['id'] = $historico->id;
                    $list['tipo'] = $historico->tipo;
                    if ($estrategia)
                        $list['estrategia'] = $estrategia->id;
                    if ($historico->cor)
                        $list['cor'] = $historico->cor;
                    if ($historico->informacao)
                        $list['informacao'] = $historico->informacao;
                    //$list['created_at'] = $historico->created_at;                           
            }
            $status = $call_status();
        } while ($list == $ant AND $status == 'EXECUTANDO');
        
        if ($status != 'EXECUTANDO')
            return [];

        return $list;
    }

    public function get_estrategia(){
        if (!$this->obj_estrategia and $this->estrategia_id) {
            $this->obj_estrategia = TUtils::openFakeConnection('double', function() {
                return new DoubleEstrategia($this->estrategia_id);
            });
        }
        return $this->obj_estrategia;
    }

    public function get_canal(){
        if (!$this->obj_canal and $this->canal_id) {
            $this->obj_canal = TUtils::openFakeConnection('double', function() {
                return new DoubleCanal($this->canal_id);
            });
        }
        return $this->obj_canal;
    }
}
