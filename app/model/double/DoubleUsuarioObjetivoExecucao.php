<?php

use Adianti\Database\TRecord;

class DoubleUsuarioObjetivoExecucao extends DoubleRecord
{
    const TABLENAME  = 'double_usuario_objetivo_execucao';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';

    use RecordTrait;
    // use SystemChangeLogTrait;

    private $usuario_objetivo_obj;
    private $usuario_obj;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        $this->loadAttributes('double');
    }

    public function atualizar_progresso($cron = false)
    {
        $retorno = false;

        // DoubleErros::registrar(1, 'DoubleUsuarioObjetivoExecucao', 'atualizar_progresso 2', $this->status);

        if ($this->status == 'EXECUTANDO')
        {
            $retorno = TUtils::openConnection('double', function() use ($cron) {
                $retorno = false;

                $objetivo = $this->usuario_objetivo;
                $usuario  = $this->usuario;
                
                $this->valor_lucro_prejuizo = TUtils::openFakeConnection('double', function() use ($usuario) {
                    $valor = DoubleUsuarioHistorico::where('usuario_id', '=', $usuario->id)
                        // ->where("CONVERT_TZ(created_at, '+00:00', '-03:00')", '>=', $this->inicio_execucao)
                        ->where('created_at', '>=', $this->inicio_execucao)
                        ->sumBy('valor', 'total');

                    return $valor ?? 0;
                });

                // DoubleErros::registrar(1, 'DoubleUsuarioObjetivoExecucao', 'atualizar_progresso 1', $usuario->id, "{$this->valor_lucro_prejuizo}  -{$this->valor_stop_loss} - {$this->valor_stop_win}");

                $ocorreu_stop_loss = -round($this->valor_stop_loss, 2) >= round($this->valor_lucro_prejuizo, 2);
                $ocorreu_stop_win = round($this->valor_stop_win, 2) <= round($this->valor_lucro_prejuizo, 2);

                if ($ocorreu_stop_win OR $ocorreu_stop_loss)
                {
                    $this->status = 'FINALIZADO';
                    $this->fim_execucao = (new DateTime())->format('Y-m-d H:i:s');
                    $tipo_periodicidade = ['HORAS' => 'HOURS', 'MINUTOS' => 'MINUTES'][$this->usuario_objetivo->tipo_periodicidade];
                    $prox = strtotime("+ {$this->usuario_objetivo->valor_periodicidade} {$tipo_periodicidade}");
                    $d_prox = new DateTime();
                    $d_prox->setTimestamp($prox);
                    $this->proxima_execucao = $d_prox->format('Y-m-d H:i:00');

                    $usuario->robo_status = 'PARADO';
                    $usuario->save();

                    if (!$cron)
                    {
                        $telegram = $usuario->canal->telegram;
                        $telegram->sendMessage($usuario->chat_id, 'Execução do objetivo encerrada');
                        if ($ocorreu_stop_win)
                            $telegram->sendMessage($usuario->chat_id, 'Parabéns... Mais um objetivo conquistado.');
                        else
                            $telegram->sendMessage($usuario->chat_id, 'Não foi desta vez, não desanime vamos buscar a vitória.');

                        if ($this->execucao < $objetivo->total_execucoes)
                            $telegram->sendMessage($usuario->chat_id, "Faremos uma nova execução às {$d_prox->format('d/m/Y H:i:00')}");
                        else {
                            $objetivo->usuario_objetivo->status = 'PARADO';
                            $objetivo->usuario_objetivo->save();
                        }
                    }

                    $retorno = true;
                }
                
                $this->save();

                $banca = $this->valor_banca + $this->valor_lucro_prejuizo;
                $valor_minimo = $objetivo->protecao_branco == 'Y' ? $usuario->plataforma->valor_minimo_protecao : $usuario->plataforma->valor_minimo;

                $execucoes = $this->usuario_objetivo->execucoes;
                for ($i=$this->execucao; $i < $this->usuario_objetivo->total_execucoes; $i++) { 
                    $execucao = $execucoes[$i];

                    // $execucao->execucao = $i + 1;
                    $execucao->status = 'AGUARDANDO';
                    $execucao->valor_banca = $banca;
                    $execucao->valor_entrada = round($banca * ($this->usuario_objetivo->percentual_entrada / 100) , 2);
                    if ($valor_minimo > $execucao->valor_entrada)
                        $execucao->valor_entrada = $valor_minimo;
                    $execucao->valor_stop_win = round($banca * ($this->usuario_objetivo->percentual_stop_win / 100) , 2);
                    $execucao->valor_stop_loss= round($banca * ($this->usuario_objetivo->percentual_stop_loss / 100) , 2);
                    $execucao->save();

                    $banca += $execucao->valor_stop_win;
                }
                
                return $retorno;
            });
        }

        return $retorno;
    }

    public function get_usuario()
    {
        if (!$this->usuario_obj)
            $this->usuario_obj = $this->usuario_objetivo->usuario;

        return $this->usuario_obj;
    }

    public function get_usuario_objetivo()
    {
        if (!$this->usuario_obj_objetivo)
            $this->usuario_obj_objetivo = TUtils::openConnection('double', function (){
                return new DoubleUsuarioObjetivo($this->usuario_objetivo_id, false);
            });

        return $this->usuario_obj_objetivo;
    }
}
