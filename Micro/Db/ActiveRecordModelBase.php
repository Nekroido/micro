<?php
/**
 * Date: 12-May-16
 * Time: 15:10
 */

namespace Micro\Db;

use Micro\Base\ModelBase;
use Micro\Db\Helpers\RelationBase;
use Micro\Helpers\Log;
use Micro\Micro;

/**
 * Class ActiveRecordModelBase
 * @package Micro\Db
 */
abstract class ActiveRecordModelBase extends ModelBase
{
    /**
     * @var string
     */
    public $primaryKey = 'id';

    /**
     * @var string
     */
    protected $table;

    /**
     * @var array
     */
    protected $scopes = [];

    /**
     * @var array
     */
    protected $relations = [];

    /**
     * @var bool
     */
    public $isNewRecord = true;

    /**
     * @var array
     */
    private $selectedScopes = [];

    /**
     * For a one-time column information fetching
     * @var array
     */
    private $tableColumns = [];

    const HAS_MANY = 'HasMany';
    const BELONGS_TO = 'BelongsTo';
    const MANY_TO_MANY = 'ManyToMany';

    const ACTION_SAVE = 0;
    const ACTION_UPDATE = 1;

    /**
     * @param string $propertyName
     * @return ActiveRecordModelBase|mixed|null|static|static[]
     */
    final public function __get($propertyName)
    {
        if (parent::__isset($propertyName))
            return parent::__get($propertyName);
        else if (isset($this->relations[$propertyName]))
            return $this->related($propertyName);
        else {
            $trace = debug_backtrace();
            trigger_error(
                'Undefined property: ' . $propertyName .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line'],
                E_USER_NOTICE);

            return null;
        }
    }

    /**
     * @param string $propertyName
     * @return bool
     */
    final public function __isset($propertyName)
    {
        return parent::__isset($propertyName) || isset($this->relations[$propertyName]);
    }

    /**
     * Select a defined scope
     * @param $scope
     * @return $this
     */
    public final function scope($scope)
    {
        if (array_key_exists($scope, $this->scopes)) {
            $this->selectedScopes[] = $scope;
        }

        return $this;
    }

    /**
     * Get related records
     * @param string $relationName
     * @return static|static[]
     */
    private function related($relationName)
    {
        if (array_key_exists($relationName, $this->relations)) {
            /** @var ActiveRecordModelBase $modelClass */
            $modelClass = $this->relations[$relationName][1];
            $relationClass = '\\Micro\\Db\\Relations\\' . $this->relations[$relationName][0];

            /** @var RelationBase $relation */
            $relation = new $relationClass();

            $query = $relation->getQuery(
                $this,
                $modelClass::model()->table,
                $this->relations[$relationName][2]
            );

            return $modelClass::model()->query($query, $relation->fetchAll);
        }

        return null;
    }

    /**
     * Returns the latest entry in a table
     * @param array $params
     * @return static|null
     */
    public final function last($params = [])
    {
        $query = new Query();
        $query->where = $this->buildWhereStatement($params);
        $query->order = 't.' . $this->primaryKey . ' DESC';

        return $this->query($query, false);
    }

    /**
     * @param array $params
     * @return static
     */
    public final function first($params = [])
    {
        return $this->find($params, false);
    }

    /**
     * @param array $params
     * @param bool|true $fetchAll
     * @return static[]|static
     */
    public final function find($params = [], $fetchAll = true)
    {
        $query = new Query();
        $query->where = $this->buildWhereStatement($params);

        return $this->query($query, $fetchAll);
    }

    /**
     * @param Query $query
     * @param bool|true $fetchAll
     * @return static[]|static
     * @throws \Exception
     */
    public final function query(Query $query, $fetchAll = true)
    {
        $params = [];
        $sql = 'SELECT ';

        if ($fetchAll == false) {
            $query->limit = 1;
        }

        /** Fields */
        if (is_array($query->select) && count($query->select)) {
            $sql .= implode(', ', $query->select);
        } else if (strlen($query->select)) {
            $sql .= $query->select;
        } else {
            $sql .= '*';
        }

        $sql .= ' FROM ' . $this->table . ' AS t ';

        if (strlen($query->join))
            $sql .= $query->join;

        if (count($query->where) || count($this->selectedScopes)) {
            $where = [];
            foreach ($query->where as $item) {
                $where[] = $item[0] . ' ' . $item[1] . ' ?';
                $params[] = $item[2];
            }

            foreach ($this->selectedScopes as $item) {
                $where[] = $this->scopes[$item];
            }

            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        if (strlen($query->group))
            $sql .= ' GROUP BY ' . $query->group;

        if (strlen($query->order))
            $sql .= ' ORDER BY ' . $query->order;

        if ($query->limit > 0)
            $sql .= ' LIMIT ' . $query->limit . ';';

        $statement = Micro::$app->db->prepare($sql);

        if (Micro::$app->config['db']['verbose'] == true) {
            Log::write('SQL: ' . $sql . (count($params) ? ' Params: ' . join('; ', $params) . '.' : ''), Log::TYPE_VERBOSE);
        }

        try {
            $statement->execute($params);
        } catch (\Exception $e) {
            print 'Error: ' . $e . "<br>\n";
            print 'SQL: ' . $sql . "<br>\n";

            Log::write($e, Log::TYPE_ERROR);

            throw $e;
        }

        if ($fetchAll) {
            $results = $statement->fetchAll(\PDO::FETCH_CLASS, get_called_class());
            array_walk($results, function (&$item) {
                $item->isNewRecord = false;
            });

            return $results;
        } else {
            $result = $statement->fetchObject(get_called_class());
            if ($result != null) {
                $result->isNewRecord = false;
            }

            return $result;
        }
    }

    /**
     * @return static
     */
    public final function save()
    {
        if ($this->isNewRecord == false)
            return $this->update();

        return $this->commit([], self::ACTION_SAVE);
    }

    /**
     * @param array $fields
     * @return static
     */
    public final function update($fields = [])
    {
        if ($this->isNewRecord == true)
            return $this->save();

        return $this->commit($fields, self::ACTION_UPDATE);
    }

    /**
     * @param array $fields
     * @param int $action
     * @return static
     */
    private function commit($fields = [], $action = self::ACTION_SAVE)
    {
        $sqlPrefix = $action == self::ACTION_SAVE ? 'INSERT INTO ' : 'UPDATE ';
        $sqlSuffix = $action == self::ACTION_SAVE ? '' : ' WHERE id = ' . $this->{$this->primaryKey} . ' LIMIT 1';

        $this->beforeSave();

        $columns = $this->getColumns();
        $fields = is_array($fields) && count($fields)
            ? $fields
            : (!is_array($fields) && strlen($fields) ? [$fields] : $columns);

        $placeholders = [];
        $values = [];
        foreach ($fields as $field) {
            $placeholders[] = $field . ' = ?';
            $values[] = $this->$field;
        }
        $sql = $sqlPrefix . $this->table . ' SET ' . implode(', ', $placeholders) . $sqlSuffix;

        $statement = Micro::$app->db->prepare($sql);
        $statement->execute($values);

        if ($action == self::ACTION_SAVE) {
            $this->{$this->primaryKey} = Micro::$app->db->lastInsertId();
            $this->isNewRecord = false;
        }

        $this->afterSave();

        return $this;
    }

    /**
     * @param $params
     * @return array
     */
    private function buildWhereStatement($params)
    {
        $where = [];

        if (count($params)) {
            foreach ($params as $field => $value) {
                $where[] = [$field, '=', $value];
            }
        }

        return $where;
    }

    /**
     * @return array
     */
    private function getColumns()
    {
        if (count($this->tableColumns) == 0) {
            $statement = Micro::$app->db->prepare('DESCRIBE ' . $this->table);
            $statement->execute();

            $columns = $statement->fetchAll(\PDO::FETCH_COLUMN);

            if (($key = array_search($this->primaryKey, $columns)) !== false) {
                unset($columns[$key]);
            }

            $this->tableColumns = $columns;
        }

        return $this->tableColumns;
    }

    /**
     * Is called before saving data to a database
     */
    protected function beforeSave()
    {
    }

    /**
     * Is called right after saving data to a database
     */
    protected function afterSave()
    {
    }
}