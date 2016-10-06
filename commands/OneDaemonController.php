<?php

namespace console\controllers;
namespace app\commands;

use Yii;
use \vyants\daemon\DaemonController;
use app\models\DaemonJob;

class OneDaemonController extends DaemonController
{
    /**
     * Get new jobs
     * @return array
     */
    protected function defineJobs()
    {
        sleep($this->sleep);
        return DaemonJob::findAll(['status'=>DaemonJob::STATUS_NEW]);
    }

    /**
     * Run task
     * @return void
     */
    protected function doJob($job)
    {
        Yii::$app->consoleRunner->run($job->task.' '.$job->arguments.' '.getmypid().' '.$job->id);
    }
}