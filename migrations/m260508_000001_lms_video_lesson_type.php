<?php

use yii\db\Migration;

class m260508_000001_lms_video_lesson_type extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%lms_video}}', 'lesson_type', $this->string(32)->null()->after('lms_resource_id'));
        $this->alterColumn('{{%lms_video}}', 'status', $this->string(32)->notNull()->defaultValue('pending'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%lms_video}}', 'lesson_type');
    }
}
