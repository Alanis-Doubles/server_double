<?php

use Adianti\Database\TRecord;

class DoubleHistorico extends DoubleRecord
{
    const TABLENAME  = 'double_historico';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max';

    use RecordTrait;

    private $obj_estrategia;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        $this->loadAttributes('double');
    }

    public static function buscarHistorico($ant, $inicio, $plataforma_id, $call_status){
        $list = [];
        $status = '';
        do {
            sleep(1);
            try {
                $historico = TUtils::openFakeConnection('double', function() use ($plataforma_id, $inicio){
                    return self::select()
                        ->where('plataforma_id', '=', $plataforma_id)
                        ->where('created_at', '>=', $inicio)
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
                        $list['created_at'] = $historico->created_at;                           
                }
                $status = $call_status();
            } catch (\Throwable $e) {
                // $service = null;
                // $mensagem = $e->getMessage();
                // TUtils::openConnection('double');;
                // $error = new DoubleErros();
                // $error->classe = 'DoubleHistorico';
                // $error->metodo = 'buscarHistorico';
                // $error->erro = $mensagem;
                // $error->plataforma_id = $data->plataforma->id;
                // $error->save();
                // TTransaction::close();
            } catch (Exception $e) {
                // $service = null;
                // $mensagem = $e->getMessage();
                // TUtils::openConnection('double');;
                // $error = new DoubleErros();
                // $error->classe = 'DoubleHistorico';
                // $error->metodo = 'buscarHistorico';
                // $error->erro = $mensagem;
                // $error->plataforma_id = $data->plataforma->id;
                // $error->save();
                // TTransaction::close();
            }
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
}
