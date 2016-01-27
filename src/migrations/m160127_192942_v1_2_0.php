<?php

use yii\db\Schema;
use yii\db\Migration;

class m160127_192942_v1_2_0 extends Migration
{
    public function up()
    {
        $this->addColumn('{{%category}}', 'template_id', Schema::TYPE_INTEGER . ' NOT NULL');
    }

    public function down()
    {
        $this->dropColumn('{{%category}}', 'template_id');
    }
}
