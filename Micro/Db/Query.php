<?php
/**
 * Date: 12-May-16
 * Time: 15:11
 */

namespace Micro\Db;

/**
 * Class Query
 * @package Micro\Db
 */
class Query
{
    public $select = '*';
    public $join = '';
    public $where = [];
    public $group = '';
    public $order = '';
    public $limit = 0;

    /**
     * @param $field
     * @param $start
     * @param $end
     */
    public function between($field, $start, $end)
    {
        $this->where[] = [$field, '>=', $start];
        $this->where[] = [$field, '<=', $end];
    }
}