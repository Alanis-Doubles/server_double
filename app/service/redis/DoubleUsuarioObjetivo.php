<?php

use Adianti\Database\TRecord;

class DoubleUsuarioObjetivo extends DoubleRecord
{
    const TABLENAME  = 'double_usuario_objetivo';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';

    use RecordTrait;

    private $execucoes_obj;
    private $usuario_obj;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        $this->loadAttributes('double');
    }

    public function store()
    {
        if (!$this->usuario_id) {
            $chat_id = TSession::getValue('usercustomcode');
            $usuario = DoubleUsuario::where('canal_id', '=', $this->search_canal_id)
                ->where('chat_id', '=', $chat_id)
                ->where('deleted_at', 'is', null)
                ->first();

            $this->usuario_id = $usuario->id;    
        } else {
            $usuario = $this->usuario;
        }
        unset($this->search_canal_id);

        // if ($this->status !== 'EXECUTANDO')
        //     DoubleUsuarioObjetivo::where('usuario_id', '=', $usuario->id)->delete();

        if (isset($this->execucoes_em_execucao)) {
            $em_execucao = true;
            unset($this->execucoes_em_execucao);
        }
        else
            $em_execucao = DoubleUsuarioObjetivoExecucao::where('usuario_objetivo_id', '=', $this->id)
                ->where('status', '=', 'EXECUCAO')
                ->first() !== null;

        parent::store();

        if (!$em_execucao)
        {
            $usuario->modo_treinamento = $this->modo_treinamento;
            $banca = $usuario->plataforma->service->saldo($usuario);
            $valor_minimo = $this->protecao_branco == 'Y' ? $usuario->plataforma->valor_minimo_protecao : $usuario->plataforma->valor_minimo;
            // DoubleErros::registrar('1', 'DoubleUsuarioObjetivo', 'store', $valor_minimo, "{$this->protecao_branco} ? {$usuario->plataforma->valor_minimo_protecao} : {$usuario->plataforma->valor_minimo}");
            DoubleUsuarioObjetivoExecucao::where('usuario_objetivo_id', '=', $this->id)->delete();
            for ($i=0; $i < $this->total_execucoes; $i++) { 
                $execucao = new DoubleUsuarioObjetivoExecucao();
                $execucao->usuario_objetivo_id = $this->id;
                $execucao->execucao = $i + 1;
                $execucao->status = 'AGUARDANDO';
                $execucao->valor_banca = $banca;
                $execucao->valor_entrada = round($banca * ($this->percentual_entrada / 100) , 2);
                if ($valor_minimo > $execucao->valor_entrada)
                    $execucao->valor_entrada = $valor_minimo;
                $execucao->valor_stop_win = round($banca * ($this->percentual_stop_win / 100) , 2);
                $execucao->valor_stop_loss= round($banca * ($this->percentual_stop_loss / 100) , 2);
                $execucao->save();

                $banca += $execucao->valor_stop_win;
            }
        }
    }

    public function get_usuario()
    {
        if (!$this->usuario_obj)
            $this->usuario_obj = TUtils::openConnection('double', function (){
                return new DoubleUsuario($this->usuario_id, false);
            });

        return $this->usuario_obj;
    }

    public function get_execucoes()
    {
        if (!$this->execucoes_obj)
            $this->execucoes_obj = TUtils::openConnection('double', function (){
                return $this->loadComposite('DoubleUsuarioObjetivoExecucao', 'usuario_objetivo_id', NULL, 'execucao');
            });

        return $this->execucoes_obj;
    }

    public function parar()
    {
        $this->status = 'PARADO';
        $this->save();

        DoubleUsuarioObjetivoExecucao::where('usuario_objetivo_id', '=', $this->id)
            ->set('status', 'PARADO')
            ->update();
    }

    public function atualizar_progresso()
    {
        $retorno = false;

        $execucoes = $this->execucoes;
        if ($execucoes)
            foreach ($execucoes as $execucao) {
                if ($execucao->status == 'EXECUTANDO')
                {
                    $retorno = $execucao->atualizar_progresso();
                    break;
                }
            }

        return $retorno;
    }

    public function get_progresso()
    {
        $retorno = '';

        $execucoes = $this->execucoes;
        if ($execucoes)
            foreach ($execucoes as $execucao) {
                if ($execucao->status == 'EXECUTANDO')
                {
                    $retorno = "🎯 Objetivo {$execucao->execucao} de {$this->total_execucoes}";
                    break;
                }
            }

        return $retorno;
    }
}
