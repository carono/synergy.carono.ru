<?php

use carono\yii2migrate\Migration;

class m260507_215754_lms_video extends Migration
{
    public function newTables()
    {
        return [
            '{{%lms_discipline}}' => [
                'id' => $this->primaryKey(),
                'lms_id' => $this->string(64)->notNull(),
                'semester' => $this->integer()->notNull(),
                'title' => $this->string(255)->notNull(),
                'slug' => $this->string(255)->notNull(),
                'created_at' => $this->dateTime(),
                'updated_at' => $this->dateTime(),
            ],
            '{{%lms_video}}' => [
                'id' => $this->primaryKey(),
                'discipline_id' => $this->foreignKey('{{%lms_discipline}}'),
                'lms_resource_id' => $this->string(64)->notNull(),
                'code' => $this->string(64),
                'title' => $this->string(255)->notNull(),
                'status' => $this->string(32)->notNull()->defaultValue('downloaded'),
                'locked_reason' => $this->string(255),
                'required_minutes' => $this->integer(),
                'watched_minutes' => $this->integer(),
                'video_url' => $this->text(),
                'file_path' => $this->string(500),
                'file_size' => $this->bigInteger(),
                'downloaded_at' => $this->dateTime(),
                'created_at' => $this->dateTime(),
                'updated_at' => $this->dateTime(),
            ],
        ];
    }

    public function newColumns()
    {
        return [];
    }

    public function newIndex()
    {
        return [
            '{{%lms_discipline}}' => [
                $this->index(['lms_id'], true),
                $this->index(['semester']),
            ],
            '{{%lms_video}}' => [
                $this->index(['discipline_id', 'lms_resource_id'], true),
                $this->index(['status']),
            ],
        ];
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
