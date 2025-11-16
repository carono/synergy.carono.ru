<?php

use carono\yii2migrate\Migration;

class m251116_134712_init extends Migration
{
    public function newTables()
    {
        return [
            '{{%user}}' => [
                'id' => $this->primaryKey(),
                'username' => $this->string(),
                'password_hash' => $this->string(),
                'deleted_at' => $this->dateTime()
            ],
            '{{%course}}' => [
                'id' => $this->primaryKey(),
                'name' => $this->string(),
                'pos' => $this->integer()->notNull()->defaultValue(10),
                'module' => $this->string(),
                'created_at' => $this->dateTime(),
                'deleted_at' => $this->dateTime(),
            ],
            '{{%semester}}' => [
                'id' => $this->primaryKey(),
                'name' => $this->string(),
                'course_id' => $this->foreignKey('{{%course}}'),
                'controller' => $this->string(),
                'start_at' => $this->date(),
                'end_at' => $this->date(),
                'pos' => $this->integer()->notNull()->defaultValue(10),
                'created_at' => $this->dateTime(),
                'deleted_at' => $this->dateTime(),
            ],
            '{{%task}}' => [
                'id' => $this->primaryKey(),
                'name' => $this->string(),
                'semester_id' => $this->foreignKey('{{%semester}}'),
                'description' => $this->text(),
                'action' => $this->string(),
                'deleted_at' => $this->dateTime(),
                'comment' => $this->text(),
            ],
            '{{%source}}' => [
                'id' => $this->primaryKey(),
                'name' => $this->string(),
                'description' => $this->text(),
                'view' => $this->string(),
                'pos' => $this->integer()->notNull()->defaultValue(10),
                'task_id' => $this->foreignKey('{{%task}}'),
                'class' => $this->string(),
                'method' => $this->string(),
                'file' => $this->string(),
                'deleted_at' => $this->dateTime(),
            ],
            '{{%comment}}' => [
                'id' => $this->primaryKey(),
                'source_id' => $this->foreignKey('{{%source}}'),
                'user_id' => $this->foreignKey('{{%user}}'),
                'message' => $this->text(),
                'created_at' => $this->dateTime(),
                'updated_at' => $this->dateTime(),
                'deleted_at' => $this->dateTime()
            ]
        ];
    }

    public function newColumns()
    {
        return [];
    }

    public function newIndex()
    {
        return [];
    }

    public function safeUp()
    {
        $this->upNewTables();
        $this->upNewColumns();
        $this->upNewIndex();
    }

    public function safeDown()
    {
        $this->downNewIndex();
        $this->downNewColumns();
        $this->downNewTables();
    }
}
