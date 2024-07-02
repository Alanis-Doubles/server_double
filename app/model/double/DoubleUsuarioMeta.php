<?php

use Adianti\Database\TRecord;

class DoubleUsuarioMeta extends DoubleRecord
{
    const TABLENAME  = 'double_usuario_meta';
    const PRIMARYKEY = 'id';
        const IDPOLICY   = 'serial';

    use RecordTrait;

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        $this->loadAttributes('double');
    }

    public function atualizar($usuario)
    {
        $plataforma = $usuario->plataforma;

        $banca = $plataforma->service->saldo($usuario);
            
        // if ($plataforma->ambiente == 'HOMOLOGACAO' and $usuario->robo_status == 'EXECUTANDO') {
        if ($usuario->modo_treinamento == 'Y' and $usuario->robo_status == 'EXECUTANDO') {
            $lucro = TUtils::openFakeConnection('double', function() use($usuario) {
                return DoubleUsuarioHistorico::where('usuario_id', '=', $usuario->id)
                    ->where('created_at', '>=', $usuario->robo_inicio)
                    ->sumBy('valor', 'total');
            });
            $banca = $banca + $lucro;
        }

        $this->ultimo_saldo = $banca;
        if ($this->tipo_entrada == 'PERCENTUAL')
            $this->valor_real_entrada = round($banca * ($this->valor_entrada / 100), 2);
        if ($this->tipo_objetivo == 'PERCENTUAL')
            $this->valor_real_objetivo = round($banca * ($this->valor_objetivo / 100), 2);
        $this->saveInTransaction();
    }
}
