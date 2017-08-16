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
 * Class Model.
 */
abstract class Model extends MvcModel
{
    public function initialize(): void
    {
        $this->setWriteConnectionService('dbMaster');
        $this->setReadConnectionService('dbSlave');
        $this->skipAttributes([
            'update_time',
        ]);
    }

    /**
     * create builder.
     *
     * @param mixed $models
     *
     * @return \Eelly\Mvc\Model\Query\Builder
     */
    public static function createBuilder($models = null)
    {
        if (null === $models) {
            $models = static::class;
        }

        return Di::getDefault()->getShared('modelsManager')->createBuilder()->from($models);
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
    public static function getField(string $field = 'base'): string
    {
        return get_called_class()::FIELD_SCOPE[$field];
    }

    /**
     * 结果集字段映射(支持多维数组,支持转换为对象).
     *
     * Example:
     * $data = [
     *     'a' => '1',
     *     'b' => '2',
     *     'c' => '3',
     * ];
     * $columnMap = [
     *     'a' => ['column' => 'AAA', 'type' => 'int'],
     *     'b' => ['column' => 'BBB', 'type' => 'bool'],
     *     'c' => ['column' => 'CCC',]
     * ];
     * $this->getResultByColumnMap($data, $columnMap);
     * Result:
     * [
     *     'AAA' => 1,
     *     'BBB' => true,
     *     'CCC' => '3',
     * ]
     *
     * @param array  $data          需转换的数据
     * @param array  $columnMap     字段映射关系
     * @param string $hydrationMode 转换数据模式 array或类名
     *
     * @return array
     *
     * @author wangjiang<wangjiang@eelly.net>
     *
     * @since 2017-08-14
     */
    public function getResultByColumnMap(array $data, array $columnMap, string $hydrationMode = 'array')
    {
        if (empty($data) || empty($columnMap)) {
            return [];
        }

        if ('array' == $hydrationMode) {
            $hydration = [];
        } else {
            if (!class_exists($hydrationMode)) {
                throw new \Phalcon\Exception($hydrationMode.'类型加载失败');
            }
            $hydration = new $hydrationMode();
        }

        if(count($data) == count($data, COUNT_RECURSIVE)){
            foreach($data as $key => $val){
                if(!isset($columnMap[$key]['column'])){
                    throw new \Phalcon\Exception($key . '不存在映射关系');
                }

                isset($columnMap[$key]['type']) && settype($val, $columnMap[$key]['type']);
                $key = $columnMap[$key]['column'];
                if('array' == $hydrationMode){
                    $hydration[$key] = $val;
                } else {
                    $hydration->$key = $val;
                }
            }

            return $hydration;
        } else {
            $result = [];
            foreach ($data as $recursiveData) {
                $result[] = $this->getResultByColumnMap($recursiveData, $columnMap, $hydrationMode);
            }
        }

        return $result;
    }
}
