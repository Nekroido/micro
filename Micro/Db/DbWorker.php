<?php
/**
 * Date: 12-May-16
 * Time: 15:03
 */

namespace Micro\Db;

use Micro\Helpers\Log;

/**
 * Class DbWorker
 * @package Micro\Db
 */
class DbWorker extends \PDO
{
    /**
     * DbWorker constructor.
     * @param $dsn
     * @param null $user
     * @param null $password
     * @param null $options
     */
    public function __construct($dsn, $user = null, $password = null, $options = null)
    {
        try {
            parent::__construct($dsn, $user, $password, null);

            $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $this->exec("set names utf8");
        } catch (\PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
            Log::write('Connection failed: ' . $e->getMessage(), Log::TYPE_ERROR);
            exit;
        }
    }
}