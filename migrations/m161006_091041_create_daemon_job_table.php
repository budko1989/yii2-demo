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
            'task' => $this->string(255),
            'arguments' => $this->text(),
            'status' => $this->string(30),
            'progress' => $this->string(30),
            'daemonId' => $this->integer(20),
            'result' => $this->text(),
            'errors' => $this->text(),
            'created' => $this->timestamp("CURRENT_TIMESTAMP"),
            'started' => $this->timestamp(),
            'finished' => $this->timestamp(),
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
