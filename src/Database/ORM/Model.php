<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2021/3/9
 */

namespace Refink\Database\ORM;


use Refink\Database\Pool\MySQLPool;

class Model
{
    protected $table;

    protected $primaryKey = 'id';

    /**
     * connection name
     * @var string
     */
    protected $name = 'default';

    const SORT_ASC = 'ASC';

    const SORT_DESC = 'DESC';

    const OPERATOR_IN = 'in';
    const OPERATOR_NOT_IN = 'not in';
    const OPERATOR_BETWEEN = 'between';
    const OPERATOR_LIKE = 'like';

    private $where;
    private $orderBy;
    private $limit;

    private $bindValues = [];

    private $columns = '*';

    /**
     * @var \PDO
     */
    private $pdo;

    public function __construct()
    {
        $this->pdo = MySQLPool::getConn($this->name);
    }

    /**
     * get one record by where conditions
     */
    public function get()
    {
        return $this->fetch();
    }

    /**
     * get all record by where conditions
     * @return array|mixed
     * @throws \Exception
     */
    public function getAll()
    {
        return $this->fetch(true);
    }

    /**
     * find one record by primary key
     * @param $id
     */
    public function find($id)
    {
        $this->where([$this->primaryKey => $id]);
        return $this->fetch();

    }

    private function fetch($all = false)
    {
        $stmt = $this->pdo->prepare($this->buildQuery());
        foreach ($this->bindValues as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $this->destroyQuery();
        if ($all) {
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    private function buildQuery()
    {
        $sql = "select {$this->columns} from `{$this->table}` where 1=1 {$this->where} {$this->orderBy} {$this->limit}";
        return trim($sql);
    }

    /**
     * destroy query condition
     */
    private function destroyQuery()
    {
        $this->columns = '*';
        $this->where = '';
        $this->orderBy = '';
        $this->limit = '';
        $this->bindValues = [];
    }

    /**
     * @param mixed $conditions
     * @return $this
     */
    public function where(...$conditions)
    {
        if (empty($conditions)) {
            throw new \Exception("ORM where conditions can not be empty!");
        }
        $argNum = count($conditions);
        if (is_array($conditions[0])) {
            //kv pair
            foreach ($conditions[0] as $k => $v) {
                $this->where .= " and `{$k}` = :{$k}";
                $this->bindValues[":$k"] = $v;
            }
            return $this;
        }

        switch ($argNum) {
            case 2:
                $k = $conditions[0];
                $v = $conditions[1];
                $this->where .= " and `{$k}` = :{$k}";
                $this->bindValues[":$k"] = $v;
                break;
            case 3:
                $operator = $conditions[1];
                $k = $conditions[0];
                $v = $conditions[2];
                switch ($operator) {
                    case '=':
                    case '>':
                    case '>=':
                    case '<':
                    case '<=':
                    case '<>':
                    case '!=':
                    case self::OPERATOR_LIKE:
                        $this->where .= " and `{$k}` {$operator} :{$k}";
                        $this->bindValues[":$k"] = $v;
                        break;
                    case self::OPERATOR_IN:
                    case self::OPERATOR_NOT_IN:
                        $set = '';
                        foreach ($v as $i => $val) {
                            $placeholder = ":{$k}_" . $operator . "_$i";
                            $set .= $placeholder . ',';
                            $this->bindValues[$placeholder] = $val;
                        }
                        $set = rtrim($set, ",");
                        $this->where .= " and `{$k}` {$operator} ({$set})";
                        break;
                    case self::OPERATOR_BETWEEN:
                        $this->where .= " and (`{$k}` between :{$k}_min and :{$k}_max )";
                        $this->bindValues[":{$k}_min"] = current($v);
                        $this->bindValues[":{$k}_max"] = end($v);
                        break;
                    default:
                        throw new \Exception("ORM where condition operator {$operator} not supported");
                }
                break;
            default:
                throw new \Exception("ORM where condition arguments num exceed 3!");
        }

        return $this;
    }

    /**
     * @param string $column
     * @param string $sort
     * @return static
     */
    public function orderBy(string $column, $sort = self::SORT_ASC)
    {

    }

    /**
     * set query columns
     * @param mixed ...$args
     * @return $this
     */
    public function columns(...$args)
    {
        foreach ($args as $arg) {
            $lowerColumn = strtolower($arg);
            if (strpos($lowerColumn, ' as ') !== false) {
                list($col, $as) = explode('as', $arg, 2);
                $col = trim($col);
                $as = trim($as);
                $this->columns .= "`{$col}` as `{$as}`, ";
                continue;
            }
            $this->columns .= "`{$arg}`, ";
        }
        $this->columns = rtrim($this->columns, ", ");
        return $this;
    }


    /**
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function limit(int $limit, int $offset = 0)
    {

    }

    /**
     * @param array $kv update data
     */
    public function update(array $kv)
    {


    }

    /**
     * @param array $kv
     * @return string
     * @throws \Exception
     */
    public function insert(array $kv)
    {
        if (empty($kv)) {
            throw new \Exception('orm can not insert empty data!');
        }
        $sql = "insert into `{$this->table}` set ";
        $bindValues = [];
        foreach ($kv as $k => $v) {
            $sql .= "`{$k}` = :{$k}, ";
            $bindValues[":{$k}"] = $v;
        }
        $sql = rtrim($sql, ", ");
        $stmt = $this->pdo->prepare($sql);
        foreach ($bindValues as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $this->pdo->lastInsertId();
    }

    public function delete(array $where)
    {

    }
}