<?php

use yii\db\Migration;

class m260508_000002_lms_video_transcript extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%lms_video}}', 'transcript_raw', $this->text()->null()->after('file_size'));
        $this->addColumn('{{%lms_video}}', 'transcript_clean', $this->text()->null()->after('transcript_raw'));
        $this->addColumn('{{%lms_video}}', 'transcript_summary', $this->text()->null()->after('transcript_clean'));
        $this->addColumn('{{%lms_video}}', 'transcribed_at', $this->dateTime()->null()->after('transcript_summary'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%lms_video}}', 'transcribed_at');
        $this->dropColumn('{{%lms_video}}', 'transcript_summary');
        $this->dropColumn('{{%lms_video}}', 'transcript_clean');
        $this->dropColumn('{{%lms_video}}', 'transcript_raw');
    }
}
