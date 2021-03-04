<?php


namespace App\App;


use App\Models\Database\AppDatabase;
use App\Models\Database\SchemaDatabase;
use App\Models\User\User;
use RecursiveDirectoryIterator;
use SplFileInfo;
use system\update\AvailableUpdate;
use System\Update\Updater;

class AppUpdater extends Updater
{
    private ?User $user;

    public function __construct()
    {
        $this->user = App::get()->user;
        $tmp = ROOTPATH . DIRECTORY_SEPARATOR . "writable" . DIRECTORY_SEPARATOR . "update";
        $dst = ROOTPATH;
        parent::__construct('https://raw.githubusercontent.com/jacobsen9026/AD-Accounts-Manager/master/update/update.json', $tmp, $dst, App::$version);
        $this->logger->info("Creating updater");
        $this->setCheckSSL(false);
    }

    public function update($simulation = true, $deleteDownload = false)
    {
        if ($this->isUpdateAvailable()) {
            if (parent::update($simulation, $deleteDownload)) {
                if ($this->updateConfigSchema($simulation)) {
                    return true;
                }
                return false;
            }
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isUpdateAvailable(): ?bool
    {
        if (App::inDebugMode()) {
            $this->latestUpdate = $this->getLatestUpdateFromURL();

            return true;
        }
        if ($this->user->getUsername() !== 'demo') {
            $this->logger->info("isUpdateAvailable called");
            //$this->connectToUpdateServer();


            if (time() - AppDatabase::getLastUpdateCheck() > $this->updateCheckInterval) {
                $this->latestUpdate = $this->getLatestUpdateFromURL();
            } else {
                $latestUpdate = unserialize(AppDatabase::getLatestAvailableVersion());
                if ($latestUpdate !== false) {
                    $this->latestUpdate = $latestUpdate;
                }
            }
            return $this->latestUpdate->version > $this->currentVersion;
        }
        return false;

    }

    /**
     * @inheritDoc
     * @throws \System\App\AppException
     */
    public function getLatestUpdateFromURL()
    {
        $return = parent::getLatestUpdateFromURL(); // TODO: Change the autogenerated stub
        /**
         * Update the database last update check time
         */
        AppDatabase::setLastUpdateCheck(time());
        /**
         * Update the database latest version
         */
        AppDatabase::setLatestAvailableVersion(serialize($this->latestUpdate));
        return $return;
    }

    private function updateConfigSchema(bool $simulation)
    {
        if ($simulation) {
            $this->logger->debug('Simulate Config Schema Update');

        } else {
            $this->logger->debug('Run Config Schema Update');
        }


        $dir = new RecursiveDirectoryIterator (APPPATH . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'updates');
        /**
         * @var SplFileInfo $file
         */
        foreach (new \RecursiveIteratorIterator($dir) as $file) {
            if ($file->getBasename() !== "." && $file->getBasename() !== ".." && $file->getExtension() === 'sql') {
                if (SchemaDatabase::getSchemaVersion() < str_replace(["v", ".sql"], "", $file->getBasename())) {
                    if ($simulation) {
                        $this->logger->info("Simulate running sql update script: " . $file->getBasename());
                    } else {
                        /**
                         * Use Database Class to run the sql script
                         */
                    }
                } else {
                    $this->logger->debug("Database schema version is higher than the update file: " . $file->getBasename());
                }
            }


        }

    }


}