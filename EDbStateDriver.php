<?php

/**
 * This application component extends CDbConnection to be able to save
 * and restore current db states for testing usage.
 *
 * put this in place of CDbConnection in your application
 *
 * Currently only works with sqlite.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 */
class EDbStateDriver extends CDbConnection
{
    private $_baseDb = null;

    /**
     * Initializes the component.
     * This method is required by {@link IApplicationComponent} and is invoked by application
     * when the CDbConnection is used as an application component.
     * If you override this method, make sure to call the parent implementation
     * so that the component can be marked as initialized.
     *
     * @throws CException
     */
    public function init()
    {
        parent::init();
        if (!in_array($this->driverName, array('sqlite', 'sqlite2'))) {
            throw new CException('EDbStateDriver only supports sqlite database backend.');
        }
        $this->_baseDb = $this->getCurrentDb();
    }

    /**
     * @throws CException
     * @return string the path where db files are stored
     */
    public function getDbStoragePath()
    {
        if (($pos=strpos($this->connectionString, ':'))!==false) {
            return dirname(strtolower(substr($this->connectionString, $pos+1)));
        }
        throw new CException('Db storage path is not available.');
    }

    /**
     * sets the path where db files are stored
     */
    public function setDbStoragePath($path)
    {
        $this->connectionString = $this->driverName . ':' .
                                  ((substr($path, -1, 1) == '/') ? $path : $path . '/') .
                                  $this->getCurrentDb();
    }

    /**
     * @throws CException
     * @return string filename of currently used db
     */
    public function getCurrentDb()
    {
        if (($pos=strpos($this->connectionString, ':'))!==false) {
            return basename(strtolower(substr($this->connectionString, $pos+1)));
        }
        throw new CException('Db storage path is not available.');
    }

    /**
     * sets the filename of currently used db
     */
    protected function setCurrentDb($filename)
    {
        $this->connectionString = $this->driverName . ':' . $this->dbStoragePath . $filename;
    }

    /**
     * saves the current db state under a unique key
     *
     * @return string the unique state key
     */
    public function saveState()
    {
        $key = $this->generateUniqueKey();
        copy(
            $this->getDbStoragePath() . '/' . $this->getCurrentDb(),
            $this->getDbStoragePath() . '/' . $key . '.db'
        );
        return $key;
    }

    /**
     * loads a saved db state by its unique key
     *
     * @return void
     */
    public function loadState($key)
    {
        $this->close();
        copy(
            $this->getDbStoragePath() . '/' . $key . '.db',
            $this->getDbStoragePath() . '/' . $this->_baseDb
        );
        $this->setCurrentDb($this->_baseDb);
    }

    /**
     * creates a new empty db and applies db migrations current db will be deleted
     *
     * @return void
     */
    public function resetState($migrateTo = false, $migrationModules=null)
    {
        $this->close();
        unlink($this->getDbStoragePath() . '/' . $this->getCurrentDb());
        $this->setCurrentDb($this->_baseDb);
        if ($migrateTo !== false) {
            // @todo: run migrations here
        }
    }

    /**
     * generates unique key wich is not existing in current db data directory
     * @return string
     */
    protected function generateUniqueKey()
    {
        $makeKey = function() {
            return uniqid('dbstate_', true);
        };
        $key = $makeKey();
        while (file_exists($this->getDbStoragePath() . '/' . $key . '.db')) {
            $key = $makeKey();
        }
        return $key;
    }
}