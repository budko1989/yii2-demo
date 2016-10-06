<?php

namespace app\models;

use Yii;
use yii\db\Expression;

/**
 * This is the model class for table "{{%daemon_job}}".
 *
 * @property integer $id
 * @property string $task
 * @property string $arguments
 * @property string $status
 * @property string $progress
 * @property integer $daemonId
 * @property string $result
 * @property string $errors
 * @property string $created
 * @property string $started
 * @property string $finished
 */
class DaemonJob extends \yii\db\ActiveRecord
{

    const STATUS_NEW = 'new';
    const STATUS_RUNNING = 'running';
    const STATUS_FINISHED = 'finished';
    const STATUS_FAILED = 'failed';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%yii2_daemon_job}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['task'], 'required'],
            ['status', 'default', 'value' => self::STATUS_NEW],
            [['arguments', 'result', 'errors'], 'string'],
            [['daemonId'], 'integer'],
            [['created', 'started', 'finished'], 'safe'],
            [['task'], 'string', 'max' => 255],
            [['status', 'progress'], 'string', 'max' => 30]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'task' => 'Task',
            'arguments' => 'Arguments',
            'status' => 'Status',
            'progress' => 'Progress',
            'daemonId' => 'Daemon ID',
            'result' => 'Result',
            'errors' => 'Errors',
            'created' => 'Created',
            'started' => 'Started',
            'finished' => 'Finished',
        ];
    }

    /**
     * @inheritdoc
     * @return DaemonJobQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new DaemonJobQuery(get_called_class());
    }

    public function start($pid)
    {
        $this->status = self::STATUS_RUNNING;
        $this->started = new Expression('NOW()');
        $this->daemonId = $pid;
        return $this->save();
        // $this->updateStatus(self::STATUS_RUNNING);
    }

    public function finish($result = '')
    {
        $this->status = self::STATUS_FINISHED;
        $this->result = $result;
        $this->finished = new Expression('NOW()');
        $this->save();
    }

    public function failed($errors = 'undefined error')
    {
        $this->status = self::STATUS_FAILED;
        // $this->result = "false";
        $this->finished = new Expression('NOW()');
        $this->errors = $errors;
        $this->save();
    }
}
