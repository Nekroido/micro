<?php
/**
 * Date: 12-May-16
 * Time: 15:18
 */

namespace Micro\Db\Relations;

use Micro\Db\Helpers\RelationBase;
use Micro\Db\Query;

/**
 * Class HasMany
 * @package Micro\Db\Relations
 */
class HasMany extends RelationBase
{
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
        $query->join = 'INNER JOIN ' . $parent->table . ' ON ' . $parent->table . '.id = t.' . $field;
        $query->where[] = [$parent->table . '.id', '=', $parent->id];

        return $query;
    }
}