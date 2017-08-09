<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eelly\Mvc;

use Phalcon\Di;
use Phalcon\Mvc\Model as MvcModel;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;

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

    /**
     * 返回分页数组.
     *
     * @param $data Model查找结果集|如 $data = OauthModuleService::find();
     * @param int $limit 分页页数
     * @param int $page  当前页数
     *
     * @return array
     *
     * @author liangxinyi<liangxinyi@eelly.net>
     */
    public function pagination($data, int $limit = 10, int $page = 1): array
    {
        if (empty($data)) {
            return [];
        }
        $paginator = new PaginatorModel(
            [
                'data'  => $data,
                'limit' => $limit,
                'page'  => $page,
            ]
        );
        $page = $paginator->getPaginate();
        foreach ($page->items as $key=>$item) {
            $return['items'][$key] = $item->toArray();
        }
        $return['page'] = [
            'first'      => $page->first,
            'before'     => $page->before,
            'current'    => $page->current,
            'last'       => $page->last,
            'next'       => $page->next,
            'total_pages'=> $page->total_pages,
            'total_items'=> $page->total_items,
            'limit'      => $page->limit,
        ];

        return $return;
    }

    /**
     * 原生sql语句返回分页数组.
     *
     * @param string $sql   sql语句
     * @param int    $limit 分页页数
     * @param int    $page  当前页数
     *
     * @return array
     *
     * @author liangxinyi<liangxinyi@eelly.net>
     */
    public function paginationSql(string $sql, int $limit = 10, int $page = 1): array
    {
        $start = ($page - 1) * $limit;
        $count = count($this->getReadConnection()->fetchAll($sql));
        $sql .= " limit $start,$limit ";
        $data = $this->getReadConnection()->fetchAll($sql);
        if (empty($data)) {
            return [];
        }

        $total_pages = ceil($count / $limit);
        $return['items'] = $data;
        $return['page'] = [
            'first'      => 1,
            'before'     => 1,
            'current'    => $page,
            'last'       => $page > 1 ? $page : 1,
            'next'       => $total_pages > $page ? ($page + 1) : $page,
            'total_pages'=> ceil($count / $limit),
            'total_items'=> $count,
            'limit'      => $limit,
        ];

        return $return;
    }

    /**
     * 获取字段.
     *
     * @param string $field
     *
     * @return string
     * @requestExample(base)
     * @returnExample([role_id,role_name,default_permission,created_time,update_time])
     *
     * @author liangxinyi<liangxinyi@eelly.net>
     *
     * @since 2017-7-27
     */
    public static function getField(string $field = 'base'):string
    {
        return get_called_class()::FIELD_SCOPE[$field];
    }
}
