<?php

use yii\db\Schema;
use yii\db\Migration;

class m160124_105657_v1_0_2 extends Migration
{
    public function up()
    {
        $this->addColumn('{{%template}}', 'layout', Schema::TYPE_STRING . ' NOT NULL');
    }

    public function down()
    {
        $this->dropColumn('{{%template}}', 'layout');
    }
}
