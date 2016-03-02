<?php

use yii\db\Migration;

class m160127_192942_v1_2_0 extends Migration
{
    public function up()
    {
        $this->addColumn('{{%category}}', 'template_id', $this->integer()->notNull());

        // foreign key for block->namespace
        $this->createIndex('idx-extension-namespace', '{{%extension}}', 'namespace', true);
        $this->addForeignKey('fk-block-extension', '{{%block}}', 'namespace', '{{%extension}}', 'namespace', 'CASCADE', 'CASCADE');

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        // config table
        $this->createTable('{{%config}}', [
            'id' => $this->string()->notNull(),
            'value' => $this->text()->notNull(),
            'section' => $this->string()->notNull(),
            'PRIMARY KEY(id, section)'
        ], $tableOptions);
    }

    public function down()
    {
        $this->dropColumn('{{%category}}', 'template_id');
        $this->dropTable('{{%config}}');
    }
}
