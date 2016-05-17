<?php
/**
 * Date: 12-May-16
 * Time: 15:16
 */

namespace Micro\Db\Helpers;

use Micro\Db\Query;

/**
 * Class RelationBase
 * @package Micro\Db\Helpers
 */
abstract class RelationBase
{
    /**
     * @var bool Fetch all or single record
     */
    public $fetchAll = true;

    /**
     * @param object $parent
     * @param string $table
     * @param string|array $field or an array with [connection_table, parent_primary_id, relation_primary_id]
     * @return Query
     */
    abstract public function getQuery($parent, $table, $field);
}