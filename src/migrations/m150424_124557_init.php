<?php

use yii\db\Schema;
use yii\db\Migration;

class m150424_124557_init extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        // block table
        $this->createTable('{{%block}}', [
            'id' => Schema::TYPE_PK,
            'extension_id' => Schema::TYPE_INTEGER . ' NOT NULL',
            'title' => Schema::TYPE_STRING . ' NOT NULL',
            'content' => Schema::TYPE_TEXT . ' NOT NULL',
            'namespace' => Schema::TYPE_STRING . ' NOT NULL',
            'show_title' => Schema::TYPE_SMALLINT . ' NOT NULL',
            'state' => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 1',
            'scope' => Schema::TYPE_STRING . ' NOT NULL',
        ], $tableOptions);
        // menu table
        $this->createTable('{{%menu}}', [
            'id' => Schema::TYPE_PK,
            'title' => Schema::TYPE_STRING . ' NOT NULL',
            'alias' => Schema::TYPE_STRING . ' NOT NULL',
            'route' => Schema::TYPE_STRING . ' NOT NULL',
            'state' => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 1',
            'tree' => Schema::TYPE_INTEGER . ' NOT NULL',
            'lft' => Schema::TYPE_INTEGER . ' NOT NULL',
            'rgt' => Schema::TYPE_INTEGER . ' NOT NULL',
            'depth' => Schema::TYPE_INTEGER . ' NOT NULL',
            'is_default' => Schema::TYPE_SMALLINT . ' NOT NULL',
            'meta_title' => Schema::TYPE_STRING . ' NOT NULL',
            'meta_description' => Schema::TYPE_STRING . ' NOT NULL',
            'meta_keywords' => Schema::TYPE_STRING . ' NOT NULL',
        ], $tableOptions);
        // category table
        $this->createTable('{{%category}}', [
            'id' => Schema::TYPE_PK,
            'module' => Schema::TYPE_STRING . ' NOT NULL',
            'title' => Schema::TYPE_STRING . ' NOT NULL',
            'content' => Schema::TYPE_TEXT . ' NOT NULL',
            'state' => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 1',
            'tree' => Schema::TYPE_INTEGER . ' NOT NULL',
            'lft' => Schema::TYPE_INTEGER . ' NOT NULL',
            'rgt' => Schema::TYPE_INTEGER . ' NOT NULL',
            'depth' => Schema::TYPE_INTEGER . ' NOT NULL',
            'created_at' => Schema::TYPE_INTEGER . ' NOT NULL',
            'updated_at' => Schema::TYPE_INTEGER . ' NOT NULL',
            'alias' => Schema::TYPE_STRING . ' NOT NULL',
            'meta_title' => Schema::TYPE_STRING . ' NOT NULL',
            'meta_description' => Schema::TYPE_STRING . ' NOT NULL',
            'meta_keywords' => Schema::TYPE_STRING . ' NOT NULL',
        ], $tableOptions);
        // template table
        $this->createTable('{{%template}}', [
            'id' => Schema::TYPE_PK,
            'title' => Schema::TYPE_STRING . ' NOT NULL',
            'positions' => Schema::TYPE_STRING . ' NOT NULL',
            'is_default' => Schema::TYPE_SMALLINT . ' NOT NULL',
        ], $tableOptions);
        // extension table
        $this->createTable('{{%extension}}', [
            'id' => Schema::TYPE_PK,
            'name' => Schema::TYPE_STRING . ' NOT NULL',
            'type' => Schema::TYPE_STRING . ' NOT NULL',
            'namespace' => Schema::TYPE_STRING . ' NOT NULL',
            'description' => Schema::TYPE_TEXT . ' NOT NULL',
            'state' => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 1',
        ], $tableOptions);

        // insert a default template into template table
        $this->insert('{{%template}}', [
            'id' => 1,
            'title' => 'Default',
            'positions' => '',
            'is_default' => 1,
        ]);
    }

    public function down()
    {
        $this->dropTable('{{%block}}');
        $this->dropTable('{{%menu}}');
        $this->dropTable('{{%category}}');
        $this->dropTable('{{%template}}');
        $this->dropTable('{{%extension}}');
    }
}
