<?php

namespace console\controllers;
namespace app\commands;

use Yii;
use \vyants\daemon\DaemonController;
use app\models\DaemonJob;

class RegistrationDaemonController extends DaemonController
{

    /**
     * Получаем список айдишников товаров для обновления от 1с сервера
     * @return array
     */
    protected function defineJobs()
    {
        sleep(10);
        try {
            $registered_items = Yii::$app->web1c->GetRegisteredProducts(['APIKey'=>Yii::app()->params['1cApiKey']]);
        } catch (Exception $e) {
            Yii::error($e, __METHOD__);
        }
        $items = [];
        if ($registered_items->return->success and isset($registered_items->return->ProductResultRow) and is_array($registered_items->return->ProductResultRow)) {
            foreach ($registered_items->return->ProductResultRow as $r) {
                $items[] = $r->uuid;
            }
        } elseif ($registered_items->return->success and isset($registered_items->return->ProductResultRow) and is_object($registered_items->return->ProductResultRow)) {
            $items[] = $registered_items->return->ProductResultRow->uuid;
        }
        return array_chunk($items, 99);
    }

    /**
     * Создаем задание для демона со списком айдишников для синхронизации и отменяем регистрацию на сервере 1с
     * @return jobtype
     */
    protected function doJob($job)
    {
        $jobModel = new DaemonJob();
        $jobModel->task = 'sync/products';
        $jobModel->arguments = implode(',', $job);
        if ($jobModel->save()) {
            try {
                $unregistered_items_result = Yii::$app->web1c->UnregisterProducts([
                    'APIKey'=>Yii::app()->params['1cApiKey'], 
                    'ArrayProducts'=>$job
                ]);
            } catch (Exception $e) {
                Yii::error($e, __METHOD__);
            }
            return true;
        }
        return false;
    }
}