<?php

namespace console\controllers;
namespace app\commands;

class WatcherDaemonController extends \vyants\daemon\controllers\WatcherDaemonController
{
    /**
     * @return array
     */
    protected function defineJobs()
    {
        sleep($this->sleep);
        //TODO: modify list, or get it from config, it does not matter
        $daemons = [
            ['className' => 'OneDaemonController', 'enabled' => true],
            ['className' => 'RegistrationDaemonController', 'enabled' => true],
        ];
        return $daemons;
    }
}