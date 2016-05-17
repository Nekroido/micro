<?php
/**
 * Date: 12-May-16
 * Time: 15:00
 */

namespace Micro;

use Micro\Db\DbWorker;

/**
 * Class Micro
 * @package Micro
 */
class Micro
{
    /**
     * @var array
     */
    public $config;

    /**
     * @var DbWorker
     */
    public $db;

    /**
     * @var Micro
     */
    public static $app;

    /**
     * Base constructor.
     * @param array $config
     */
    public function __construct($config)
    {
        Micro::$app = $this;

        $this->config = $config;

        $this->db = new DbWorker(
            $this->config['db']['connectionString'],
            $this->config['db']['username'],
            $this->config['db']['password']
        );
    }

    public function start()
    {}
}