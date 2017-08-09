<?php

declare(strict_types=1);
/*
 * PHP version 7.1
 *
 * @copyright Copyright (c) 2012-2017 EELLY Inc. (https://www.eelly.com)
 * @link      https://api.eelly.com
 * @license   衣联网版权所有
 */
namespace Eelly\Validation\Store;

use Eelly\Validation\Validation;
use Store\Model\Mysql\Store;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Uniqueness;
use Phalcon\Validation\Validator\Regex;
use Eelly\SDK\Store\Exception\StoreException;
use Eelly\Exception\LogicException;

/**
 * Store模块数据校验
 *
 * @author wangjiang<wangjiang@eelly.net>
 * @since 2017-08-09
 */
class StoreValidation extends Validation
{
    /**
     * 校验规则
     *
     * @var array
     */
    protected $rules = [
        'require' => PresenceOf::class,
        'unique' => Uniqueness::class,
        'regex' => Regex::class,
    ];

    /**
     * 数据校验
       $rules = [
            'storeName' => [
                ['type' => 'require', 'message' => '店铺名称不能为空'],
                ['type' => 'unique', 'message' => '店铺名已存在', 'option' => ['model' => new Store(), 'attribute' => 'store_name']]
            ],
            'consignee,gbCode,zipCode,address,gcId,gpvIds,glId' => [
                ['type' => 'require', 'message' => '联系人不能为空,地址编码不能为空,邮编不能为空,详细地址不能为空,主营类型不能为空,风格类目不能为空,店铺档次不能为空', 'option' => []]
            ],
            'mobile' => [
                ['type' => 'regex', 'message' => '手机号格式不正确', 'option' => ['pattern' => '/^(13[0-9]|14[5|7]|15[0|1|2|3|5|6|7|8|9]|18[0|1|2|3|5|6|7|8|9])\d{8}$/']]
            ],
        ];
       $this->validateData($rules, $data)
     *
     * @param array $rules 校验规则
     * @param array $data 校验的数据源
     * @param bool $cancelOnFail true 验证规则失败后其余验证不会被执行,默认为false
     * @throws LogicException
     * @throws StoreException
     */
    public function validateData(array $rules, array $data, bool $cancelOnFail = false): void
    {
        $data = array_filter($data);
        foreach($rules as $field => $ruleInfo){
            false !== strpos($field, ',') && $field = explode(',', $field);
            $validators = [];
            foreach($ruleInfo as $rule){
                if(!array_key_exists($rule['type'], $this->rules)){
                    throw new LogicException(StoreException::PARAMETER_ERROR);
                }

                $options = [];
                isset($rule['message']) && $options['message'] = false !== strpos($rule['message'], ',') ? array_combine($field, explode(',', $rule['message'])) : $rule['message'];
                $options = array_merge($options, $rule['option'] ?? []);
                true === $cancelOnFail && $options['cancelOnFail'] = true;
                $validators[] = new $this->rules[$rule['type']]($options);
            }
            $this->rules($field, $validators);
        }

        $messages = $this->validate($data);
        if(count($messages)){
            foreach($messages as $message){
                throw new StoreException($message->getMessage());
            }
        }
    }
}