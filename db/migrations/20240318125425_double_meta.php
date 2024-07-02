<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class DoubleMeta extends AbstractMigration
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
        
        $this->output->writeln('<info>Tabela</info> double_usuario');

        $this->table('double_usuario')
            ->addColumn('metas', 'enum', ['values' => ['Y', 'N'], 'default' => 'N', 'null' => false])
            ->save();

        $this->output->writeln('<info>Tabela</info> double_usuario_meta');

        $this->table('double_usuario_meta')
            ->addColumn('usuario_id', 'integer')
            ->addColumn('tipo_entrada', 'enum', ['values' => ['PERCENTUAL', 'FIXO'], 'default' => 'PERCENTUAL', 'null' => false])
            ->addColumn('valor_entrada', 'double', ['default' => 0, 'null' => false])
            ->addColumn('valor_real_entrada', 'double', ['default' => 0, 'null' => false])
            ->addColumn('tipo_objetivo', 'enum', ['values' => ['PERCENTUAL', 'FIXO'], 'default' => 'PERCENTUAL', 'null' => false])
            ->addColumn('valor_objetivo', 'double', ['default' => 0, 'null' => false])
            ->addColumn('valor_real_objetivo', 'double', ['default' => 0, 'null' => false])
            ->addColumn('tipo_periodicidade', 'enum', ['values' => ['HORAS', 'MINUTOS'], 'default' => 'HORAS', 'null' => false])
            ->addColumn('valor_periodicidade', 'double', ['default' => 0, 'null' => false])
            ->addColumn('ultimo_saldo', 'double', ['default' => 0, 'null' => false])
            ->addColumn('inicio_execucao', 'datetime')
            ->addColumn('proxima_execucao', 'datetime')
            ->addColumn('created_at', 'datetime', ['null' => false,'default' => 'CURRENT_TIMESTAMP','update' => ''])
            ->addColumn('updated_at', 'datetime', ['null' => true,'default' => null,'update' => 'CURRENT_TIMESTAMP'])
            ->create();
    }
}
