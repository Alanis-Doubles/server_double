<?php

declare(strict_types=1);

use Phinx\Config\FeatureFlags;
use Phinx\Migration\AbstractMigration;

final class DoubleRecuperacaoVideos extends AbstractMigration
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
        
        $this->output->writeln('<info>Tabela</info> double_recuperacao_video');

        $this->table('double_recuperacao_video')
            ->addColumn('recuperacao_mensagem_id', 'integer',  ['null' => false])
            ->addColumn('video', 'text')
            ->addColumn('created_at', 'datetime', ['null' => false,'default' => 'CURRENT_TIMESTAMP','update' => ''])
            ->addForeignKey('recuperacao_mensagem_id', 'double_recuperacao_mensagem')
            ->create();
    }
}
