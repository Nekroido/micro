<?php
/**
 * Date: 12-May-16
 * Time: 15:19
 */

namespace Micro\Db\Relations;

use Micro\Db\Helpers\RelationBase;
use Micro\Db\Query;

class ManyToMany extends RelationBase
{
    /**
     * @param object $parent
     * @param string $table
     * @param array $connection [table_connector, this_id, other_id]
     * @return Query
     */
    public function getQuery($parent, $table, $connection)
    {
        $query = new Query();
        $query->select = 't.*';
        $query->join = 'INNER JOIN ' . $connection[0] . ' ON ' . $parent->table . '.id = ' . $connection[1];
        $query->join .= 'INNER JOIN ' . $table . ' ON t.id = ' . $connection[2];
        $query->where[] = [$connection[0] . '.' . $connection[1], '=', $parent->{$parent->primaryKey}];

        return $query;
    }
}