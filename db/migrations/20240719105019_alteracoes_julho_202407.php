<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class AlteracoesJulho202407 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        FeatureFlags::setFlagsFromConfig(['unsigned_primary_keys' => false]);

        $this->output->writeln('<info>Tabela</info> double_usuario_objetivo');
        $this->table('double_usuario_objetivo')
            ->addColumn('usuario_id', 'integer')
            ->addColumn('percentual_entrada', 'double', ['default' => 0, 'null' => false])
            ->addColumn('percentual_stop_win', 'double', ['default' => 0, 'null' => false])
            ->addColumn('percentual_stop_loss', 'double', ['default' => 0, 'null' => false])
            ->addColumn('protecoes', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('protecao_branco', 'enum', ['values' => ['Y','N'], 'default' => 'N', 'null' => false])
            ->addColumn('modo_treinamento', 'enum', ['values' => ['Y','N'], 'default' => 'Y', 'null' => false])
            ->addColumn('tipo_periodicidade', 'enum', ['values' => ['HORAS', 'MINUTOS'], 'default' => 'HORAS', 'null' => false])
            ->addColumn('valor_periodicidade', 'double', ['default' => 0, 'null' => false])
            ->addColumn('total_execucoes', 'integer')
            ->addColumn('status', 'enum', ['values' => ['PARADO','EXECUTANDO'], 'default' => 'PARADO', 'null' => false])
            ->addForeignKey('usuario_id', 'double_usuario', 'id', ['delete' => 'CASCADE'])
            ->create();

        $this->output->writeln('<info>Tabela</info> double_usuario_objetivo_execucao');
        $this->table('double_usuario_objetivo_execucao')
            ->addColumn('usuario_objetivo_id', 'integer')
            ->addColumn('execucao', 'integer')
            ->addColumn('status', 'enum', ['values' => ['AGUARDANDO','EXECUTANDO','FINALIZADO','PARADO'], 'default' => 'AGUARDANDO', 'null' => false])
            ->addColumn('valor_banca', 'double', ['default' => 0, 'null' => false])
            ->addColumn('valor_entrada', 'double', ['default' => 0, 'null' => false])
            ->addColumn('valor_stop_win', 'double', ['default' => 0, 'null' => false])
            ->addColumn('valor_stop_loss', 'double', ['default' => 0, 'null' => false])
            ->addColumn('inicio_execucao', 'datetime')
            ->addColumn('fim_execucao', 'datetime')
            ->addColumn('valor_lucro_prejuizo', 'double', ['default' => 0, 'null' => false])
            ->addColumn('proxima_execucao', 'datetime')
            ->addForeignKey('usuario_objetivo_id', 'double_usuario_objetivo', 'id', ['delete' => 'CASCADE'])
            ->create();
    }
}
