<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class DoubleRecuperacao extends AbstractMigration
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
        
        $this->output->writeln('<info>Tabela</info> double_recuperacao_mensagem');

        $this->table('double_recuperacao_mensagem')
            ->addColumn('status', 'enum', ['values' => ['NOVO','DEMO','AGUARDANDO_PAGAMENTO','ATIVO','INATIVO','EXPIRADO']])
            ->addColumn('ordem', 'integer')
            ->addColumn('horas', 'integer')
            ->addColumn('mensagem', 'text')
            ->addColumn('created_at', 'datetime', ['null' => false,'default' => 'CURRENT_TIMESTAMP','update' => ''])
            ->addColumn('updated_at', 'datetime', ['null' => true,'default' => null,'update' => 'CURRENT_TIMESTAMP'])
            ->create();

        $this->output->writeln('<info>Tabela</info> double_recuperacao_imagem');

        $this->table('double_recuperacao_imagem')
            ->addColumn('recuperacao_mensagem_id', 'integer',  ['null' => false])
            ->addColumn('imagem', 'text')
            ->addColumn('created_at', 'datetime', ['null' => false,'default' => 'CURRENT_TIMESTAMP','update' => ''])
            ->addForeignKey('recuperacao_mensagem_id', 'double_recuperacao_mensagem')
            ->create();

        $this->output->writeln('<info>Tabela</info> double_recuperacao_usuario');

        $this->table('double_recuperacao_usuario')
            ->addColumn('recuperacao_mensagem_id', 'integer',  ['null' => false])
            ->addColumn('usuario_id', 'integer', ['null' => false])
            ->addColumn('created_at', 'datetime', ['null' => false,'default' => 'CURRENT_TIMESTAMP','update' => ''])
            ->addForeignKey('usuario_id', 'double_usuario')
            ->addForeignKey('recuperacao_mensagem_id', 'double_recuperacao_mensagem')
            ->create();

        $this->output->writeln('<info>Tabela</info> double_usuario');

        $this->table('double_usuario')
            ->addColumn('data_envio_recuperacao', 'datetime')
            ->save();
    }
}
