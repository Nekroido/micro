<?php
/**
 * Date: 12-May-16
 * Time: 15:17
 */

namespace Micro\Db\Relations;

use Micro\Db\Helpers\RelationBase;
use Micro\Db\Query;

/**
 * Class BelongsTo
 * @package Micro\Db\Relations
 */
class BelongsTo extends RelationBase
{
    public $fetchAll = false;

    /**
     * @param object $parent
     * @param string $table
     * @param array|string $field
     * @return Query
     */
    public function getQuery($parent, $table, $field)
    {
        $query = new Query();
        $query->select = 't.*';
        $query->join = 'INNER JOIN ' . $parent->table . ' ON ' . $parent->table . '.' . $field . ' = t.id';
        $query->where[] = ['t.id', '=', $parent->$field];

        return $query;
    }
}