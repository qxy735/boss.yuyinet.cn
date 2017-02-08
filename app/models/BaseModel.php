<?php

use Phalcon\Mvc\Model;

class BaseModel extends Model
{
    /**
     * 初始化数据模型
     */
    public function initialize()
    {
        self::setup(array(
            'events' => false,
            'columnRenaming' => false,
            'notNullValidations' => false,
        ));
    }

    /**
     * 检查数据库插入操作时是否触发 Duplicate entry 限制
     *
     * @param \Exception $e
     *
     * @return bool
     */
    public static function isSQLStateDuplicateEntry(\Exception $e)
    {
        $result = substr($e->getMessage(), 0, 15) == 'SQLSTATE[23000]';

        return $result;
    }

    /**
     * 检查数据库查询操作是否表不存在
     *
     * @param \Exception $e
     *
     * @return bool
     */
    public static function isSQLStateDatabaseNotExist(\Exception $e)
    {
        $result = substr($e->getMessage(), 0, 15) == 'SQLSTATE[42S02]';

        return $result;
    }

    /**
     * 检查数据库查询操作是否字段不存在
     *
     * @param \Exception $e
     *
     * @return bool
     */
    public static function isSQLStateTableFieldNotExist(\Exception $e)
    {
        $result = substr($e->getMessage(), 0, 15) == 'SQLSTATE[42S22]';

        return $result;
    }
}