<?php

use yii\db\Migration;

/**
 * Handles the creation for table `daemon_job`.
 */
class m161006_091041_create_daemon_job_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('daemon_job', [
            'id' => $this->primaryKey(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('daemon_job');
    }
}
