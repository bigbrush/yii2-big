<?php

use yii\db\Migration;

class m160127_192942_v1_2_0 extends Migration
{
    public function up()
    {
        $this->addColumn('{{%category}}', 'template_id', $this->integer()->notNull());

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

        $this->batchInsert('{{%config}}', ['id', 'value', 'section'], [
            ['appName', 'Big Cms', 'cms'],
            ['systemEmail', 'noreply@noreply.com', 'cms'],
        ]);
    }

    public function down()
    {
        $this->dropColumn('{{%category}}', 'template_id');
        $this->dropTable('{{%config}}');
    }
}
