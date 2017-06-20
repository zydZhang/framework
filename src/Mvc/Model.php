<?php

declare(strict_types=1);
/*
 * PHP version 7.1
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (https://www.eelly.com)
 * @link      https://api.eelly.com
 * @license   衣联网版权所有
 */

namespace Eelly\Mvc;

use Phalcon\Di;
use Phalcon\Mvc\Model as MvcModel;

/**
 * @author hehui<hehui@eelly.net>
 *
 * @method findFirst  User::findFirst(["conditions" => "user_id >= 148086", "order" => "user_id asc "])  //返回单条记录
 * @method find       User::find(["conditions" => "user_name = ?1","bind" => [1 => 'molimoq'] ])
 * @method find       User::find(["conditions" => "user_id IN ({user_id:array})","bind" => ['user_id' => [148086, 175798889]] ])  //返回满足条件的多条记录
 * @method count      User::count("user_name = 'molimoq'")  //返回满足条件的个数
 * @method sum        User::sum(["column" => "created_time", "conditions" => "user_id = ?0", "bind" => [0 => 148086]])   //返回总和
 * @method average    User::average(["column" => "created_time", "conditions" => "user_id = ?0", "bind" => [0 => 148086]])  //返回平均值
 * @method maximum    User::maximum(["column" => "user_id", "conditions" => "user_id > ?0", "bind" => [0 => 148086]])  //返回最大值
 * @method minimum    User::minimum(["column" => "user_id", "conditions" => "user_id > ?0", "bind" => [0 => 148086]])  //返回最小值
 */
abstract class Model extends MvcModel
{
    public function initialize(): void
    {
        $this->setWriteConnectionService('dbMaster');
        $this->setReadConnectionService('dbSlave');
    }

    /**
     * @return \Eelly\Mvc\Model\Query\Builder
     */
    public static function createBuilder()
    {
        return Di::getDefault()->getShared('modelsManager')->createBuilder()->from(static::class);
    }
}
