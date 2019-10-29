<?php
/*
    DeployConfig

    Contains settings specific to a single deployment target

    Settings are stored in the database, against an id
*/

namespace WP2Static;

class DeployConfig {

    public $id;
    public $settings;

    /**
     * DeployConfig constructor
     *
     * @param mixed $id id of DeployConfig record in database
     */
    public function __construct( int $id = null ) {
        if ( ! $id ) {
            // create new DeployConfig in DB
            $new_id = $this->createNewInDB();

            // set id to DB lastinsert
            $this->id = $new_id;

            return $this->id;
        }

        $this->id = $id;

        return $this->id;
    }

    /**
     * DeployConfig create DB record
     *
     */
    private function createNewInDB() : int {
        $new_id = 666;

        return $new_id;
    }

    /**
     * Load settings from DB
     *
     */
    public function loadSettingsFromDB() : int {
        // load settings
        $this->settings = [
            'deployment_method' => 'test',
        ];

        return $this->settings;
    }
}

