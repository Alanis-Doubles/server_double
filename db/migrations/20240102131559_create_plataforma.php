<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class CreatePlataforma extends AbstractMigration
{
    public function change(): void
    {
        FeatureFlags::setFlagsFromConfig(['unsigned_primary_keys' => false]);

        $this->output->writeln('<info>Tabela</info> double_plataforma');

        $this->table('double_plataforma')
            ->addColumn('nome', 'string', ['null' => false])
            ->addColumn('idioma', 'enum', ['values' => ['ptBR', 'es', 'en'], 'default' => 'ptBR', 'null' => false])
            ->addColumn('ativo', 'enum', ['values' => ['Y', 'N'], 'default' => 'Y', 'null' => false])
            ->addColumn('tipo_sinais', 'enum', ['values' => ['GERA', 'NAO_GERA', 'PROPAGA_OUTRO'], 'default' => 'GERA', 'null' => false])
            ->addColumn('usuarios_canal', 'enum', ['values' => ['Y', 'N'], 'default' => 'N', 'null' => false])
            ->addColumn('status_sinais', 'enum', ['values' => ['PARADO', 'INICIANDO', 'EXECUTANDO', 'PARANDO'], 'default' => 'PARADO', 'null' => false])
            ->addColumn('inicio_sinais', 'datetime', ['null' => true])
            ->addColumn('valor_minimo', 'double')
            ->addColumn('telegram_token', 'string')
            ->addColumn('ambiente', 'enum', ['values' => ['HOMOLOGACAO', 'PRODUCAO'], 'default' => 'HOMOLOGACAO', 'null' => false])
            ->addColumn('url_double', 'string')
            ->addColumn('url_cadastro', 'string')
            ->addColumn('url_tutorial', 'string')
            ->addColumn('url_suporte', 'string')
            ->addColumn('created_at', 'datetime', ['null' => false,'default' => 'CURRENT_TIMESTAMP','update' => ''])
            ->addColumn('updated_at', 'datetime', ['null' => true,'default' => null,'update' => 'CURRENT_TIMESTAMP'])
            ->create();

        $this->output->writeln('<info>Tabela</info> double_configuracao');

        $this->table('double_configuracao')
            ->addColumn('nome', 'string')
            ->addColumn('valor', 'string')
            ->addColumn('created_at', 'datetime', ['null' => false,'default' => 'CURRENT_TIMESTAMP','update' => ''])
            ->addColumn('updated_at', 'datetime', ['null' => true,'default' => null,'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['nome'])
            ->create();

        $this->output->writeln('<info>Tabela</info> double_canal');

        $this->table('double_canal')
            ->addColumn('plataforma_id', 'integer', ['null' => false])
            ->addColumn('nome', 'string')
            ->addColumn('protecoes', 'integer')
            ->addColumn('channel_id', 'biginteger')
            ->addColumn('ativo', 'enum', ['values' => ['Y', 'N'], 'default' => 'Y', 'null' => false])
            ->addColumn('exibir_projecao', 'enum', ['values' => ['Y', 'N'], 'default' => 'N', 'null' => false])
            ->addColumn('status_sinais', 'enum', ['values' => ['PARADO', 'INICIANDO', 'EXECUTANDO', 'PARANDO'], 'default' => 'PARADO', 'null' => false])
            ->addColumn('inicio_sinais', 'datetime', ['null' => true])
            ->addColumn('created_at', 'datetime', ['null' => false,'default' => 'CURRENT_TIMESTAMP','update' => ''])
            ->addColumn('updated_at', 'datetime', ['null' => true,'default' => null,'update' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('plataforma_id', 'double_plataforma')
            ->addIndex(['plataforma_id', 'ativo'])
            ->create();

        $this->output->writeln('<info>Tabela</info> double_estrategia');

        $this->table('double_estrategia')
            ->addColumn('canal_id', 'integer', ['null' => false])
            ->addColumn('nome', 'string')
            ->addColumn('regra', 'string')
            ->addColumn('resultado', 'string')
            ->addColumn('usuario_id', 'integer')
            ->addColumn('ativo', 'enum', ['values' => ['Y', 'N'], 'default' => 'Y', 'null' => false])
            ->addColumn('tipo', 'enum', ['values' => ['COR', 'SOMA', 'NUMERO'], 'default' => 'COR', 'null' => false])
            ->addColumn('created_at', 'datetime', ['null' => false,'default' => 'CURRENT_TIMESTAMP','update' => ''])
            ->addColumn('updated_at', 'datetime', ['null' => true,'default' => null,'update' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('canal_id', 'double_canal')
            ->addIndex(['canal_id', 'ativo'])
            ->create();

        $this->output->writeln('<info>Tabela</info> double_sinal');

        $this->table('double_sinal')
            ->addColumn('plataforma_id', 'integer', ['null' => false])
            ->addColumn('numero', 'integer')
            ->addColumn('cor', 'string')
            ->addColumn('created_at', 'datetime', ['null' => false,'default' => 'CURRENT_TIMESTAMP','update' => ''])
            ->addForeignKey('plataforma_id', 'double_plataforma')
            ->addIndex(['plataforma_id'])
            ->create();

        $this->output->writeln('<info>Tabela</info> double_historico');

        $this->table('double_historico')
            ->addColumn('plataforma_id', 'integer', ['null' => false])
            ->addColumn('cor', 'string')
            ->addColumn('tipo', 'enum', ['values' => ['ENTRADA', 'WIN', 'LOSS', 'GALE', 'POSSIVEL']])
            ->addColumn('estrategia_id', 'integer')
            ->addColumn('canal_id', 'integer')
            ->addColumn('created_at', 'datetime', ['null' => false,'default' => 'CURRENT_TIMESTAMP','update' => ''])
            ->addForeignKey('plataforma_id', 'double_plataforma')
            ->addForeignKey('estrategia_id', 'double_estrategia')
            ->addForeignKey('canal_id', 'double_canal')
            ->addIndex(['plataforma_id', 'created_at'])
            ->create();

        $this->output->writeln('<info>Tabela</info> double_erros');

        $this->table('double_erros')
            ->addColumn('plataforma_id', 'integer', ['null' => false])
            ->addColumn('classe', 'string')
            ->addColumn('metodo', 'string')
            ->addColumn('erro', 'text')
            ->addColumn('detalhe', 'string')
            ->addColumn('created_at', 'datetime', ['null' => false,'default' => 'CURRENT_TIMESTAMP','update' => ''])
            ->addForeignKey('plataforma_id', 'double_plataforma')
            ->addIndex(['plataforma_id', 'created_at'])
            ->create();

        $this->output->writeln('<info>Tabela</info> double_usuario');

        $this->table('double_usuario')
            ->addColumn('plataforma_id', 'integer', ['null' => false])
            ->addColumn('chat_id', 'biginteger')
            ->addColumn('canal_id', 'integer')
            ->addColumn('status', 'enum', ['values' => ['NOVO','DEMO','AGUARDANDO_PAGAMENTO','ATIVO','INATIVO','EXPIRADO'], 'default' => 'NOVO', 'null' => false])
            ->addColumn('robo_iniciar', 'enum', ['values' => ['Y','N'], 'default' => 'N', 'null' => false])
            ->addColumn('robo_iniciar_apos_loss', 'enum', ['values' => ['Y','N'], 'default' => 'N', 'null' => false])
            ->addColumn('robo_processando_jogada', 'enum', ['values' => ['Y','N'], 'default' => 'N', 'null' => false])
            ->addColumn('robo_status', 'enum', ['values' => ['PARADO','INICIANDO', 'EXECUTANDO', 'PARANDO'], 'default' => 'PARADO', 'null' => false])
            ->addColumn('robo_inicio', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('robo_sequencia', 'integer')
            ->addColumn('recuperacao_status', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('recuperacao_data_envio', 'datetime')
            ->addColumn('valor', 'double', ['default' => 0, 'null' => false])
            ->addColumn('protecao', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('stop_win', 'double', ['default' => 0, 'null' => false])
            ->addColumn('stop_loss', 'double', ['default' => 0, 'null' => false])
            ->addColumn('ultimo_saldo', 'double', ['default' => 0, 'null' => false])
            ->addColumn('ciclo', 'enum', ['values' => ['Y','N','A','B'], 'default' => 'N', 'null' => false])
            ->addColumn('demo_jogadas', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('demo_inicio', 'datetime')
            ->addColumn('data_expiracao', 'date')
            ->addColumn('token_acesso', 'string')
            ->addColumn('token_plataforma', 'string')
            ->addColumn('token_expiracao', 'datetime')
            ->addColumn('created_at', 'datetime', ['null' => false,'default' => 'CURRENT_TIMESTAMP','update' => ''])
            ->addColumn('updated_at', 'datetime', ['null' => true,'default' => null,'update' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('plataforma_id', 'double_plataforma')
            ->addForeignKey('canal_id', 'double_canal')
            ->addIndex(['chat_id', 'plataforma_id', 'canal_id'], ['unique' => true])
            ->addIndex(['chat_id', 'plataforma_id', 'robo_iniciar'])
            ->addIndex(['chat_id', 'plataforma_id', 'status'])
            ->create();

        $this->output->writeln('<info>Tabela</info> double_usuario_historico');

        $this->table('double_usuario_historico')
            ->addColumn('usuario_id', 'integer', ['null' => false])
            ->addColumn('sequencia', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('valor', 'double', ['default' => 0, 'null' => false])
            ->addColumn('created_at', 'datetime', ['null' => false,'default' => 'CURRENT_TIMESTAMP','update' => ''])
            ->addColumn('updated_at', 'datetime', ['null' => true,'default' => null,'update' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('usuario_id', 'double_usuario')
            ->create();

        $this->output->writeln('<info>Tabela</info> double_pagamento_historico');

        $table = $this->table('double_pagamento_historico')
            ->addColumn('plataforma_pagamento', 'enum', ['values' => ['PIX', 'LASTLINK'], 'default' => 'PIX', 'null' => false])
            ->addColumn('tipo', 'enum', ['values' => ['MENSAL','TRIMESTRAL','SEMESTRAL','ANUAL'], 'default' => 'MENSAL', 'null' => false])
            ->addColumn('tipo_entrada', 'enum', ['values' => ['MANUAL','AUTOMATICA'], 'default' => 'MANUAL', 'null' => false])
            ->addColumn('tipo_evento', 'enum', ['values' => ['PAGAMENTO','CANCELAMENTO','EXPIRACAO'], 'default' => 'PAGAMENTO', 'null' => false])
            ->addColumn('valor', 'double', ['default' => 0, 'null' => false])
            ->addColumn('plataforma_pagamento_id', 'string')
            ->addColumn('produto', 'string', ['null' => false])
            ->addColumn('email', 'string', ['null' => false])
            ->addColumn('payload', 'text')
            ->addColumn('created_at', 'datetime', ['null' => false,'default' => 'CURRENT_TIMESTAMP','update' => ''])
            ->addColumn('usuario_id', 'integer')
            ->addForeignKey('usuario_id', 'double_usuario')
            ->addIndex(['usuario_id'])
            ->addIndex(['plataforma_pagamento','email'])
            ->addIndex(['email'])
            ->create();


        if ($this->isMigratingUp()) {
            $this->output->writeln('<info>Tabela</info> double_usuario_historico [changePrimaryKey]');

            $this->table('double_usuario_historico')
                ->changePrimaryKey(['id', 'usuario_id', 'sequencia'])
                ->update();
        }
    }  
}
