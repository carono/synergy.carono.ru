<?php

use yii\db\Migration;

class m260508_000003_lms_video_transcript_longtext extends Migration
{
    public function safeUp()
    {
        $this->alterColumn('{{%lms_video}}', 'transcript_raw', 'LONGTEXT NULL DEFAULT NULL');
        $this->alterColumn('{{%lms_video}}', 'transcript_clean', 'LONGTEXT NULL DEFAULT NULL');
        $this->alterColumn('{{%lms_video}}', 'transcript_summary', 'LONGTEXT NULL DEFAULT NULL');
    }

    public function safeDown()
    {
        $this->alterColumn('{{%lms_video}}', 'transcript_raw', 'TEXT NULL DEFAULT NULL');
        $this->alterColumn('{{%lms_video}}', 'transcript_clean', 'TEXT NULL DEFAULT NULL');
        $this->alterColumn('{{%lms_video}}', 'transcript_summary', 'TEXT NULL DEFAULT NULL');
    }
}
