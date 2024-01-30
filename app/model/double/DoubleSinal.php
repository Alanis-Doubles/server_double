<?php

use Adianti\Database\TRecord;

class DoubleSinal extends DoubleRecord
{
    const TABLENAME  = 'double_sinal';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max';

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
            try {
                $sinais = TUtils::openFakeConnection('double', function () use ($plataforma_id, $inicio) {
                    return self::select()
                        ->where('plataforma_id', '=', $plataforma_id)
                        ->where('created_at', '>=', $inicio)
                        ->orderBy('created_at', 'desc')
                        ->take(5)
                        ->load();
                }); 

                // $dataInicio = strtotime($inicio);
                $list = [];
                foreach ($sinais as $key => $sinal) {
                    $data = strtotime($sinal->created_at);
                    // if ($data > $dataInicio) {
                        // $estrategia = $sinal->estrategia;
                        // if ($estrategia)
                        //     $estrategia->resultado = $sinal->cor;
                        $list[] = [
                            'numero' => $sinal->numero, 
                            'cor' => $sinal->cor, 
                            'created_at' => $sinal->created_at
                        ];
                    // }
                }
                $status = $call_status();
            } catch (\Throwable $e) {
                // $service = null;
                // $mensagem = $e->getMessage();
                // TUtils::openConnection('double');;
                // $error = new DoubleErros();
                // $error->classe = 'DoubleSinais';
                // $error->metodo = 'buscarSinal';
                // $error->erro = $mensagem;
                // $error->plataforma_id = $data->plataforma->id;
                // $error->save();
                // TTransaction::close();
            } catch (Exception $e) {
                // $service = null;
                // $mensagem = $e->getMessage();
                // TUtils::openConnection('double');;
                // $error = new DoubleErros();
                // $error->classe = 'DoubleSinais';
                // $error->metodo = 'buscarSinal';
                // $error->erro = $mensagem;
                // $error->plataforma_id = $data->plataforma->id;
                // $error->save();
                // TTransaction::close();
            }
        } while ($list == $ant AND $status == 'EXECUTANDO');
        
        if ($status != 'EXECUTANDO')
            throw new Exception("Ocorreu um erro interno. [" . $status . "]");

        return $list;
    }
}
