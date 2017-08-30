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

namespace Eelly\Validation\Validator;

use Phalcon\Validation\Validator;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Exception;
use Phalcon\Validation\Message;

class OperatorValidator extends Validator
{
    /**
     * 支持的运算符 eq ne gt gte lt lte
     * usage:
     * $validator->add('field', new OperatorValidator([
            'message' => 'field不等于0',
            'operator' => ['eq', 0],
        ]));
     *
     * {@inheritDoc}
     * @see \Phalcon\Validation\Validator::validate()
     */
    public function validate(Validation $validation, $attribute): bool
    {
        $validationValue = (int)$validation->getValue($attribute);
        $operatorArr = $this->getOption('operator');
        if(!is_array($operatorArr) || 2 > count($operatorArr)){
            throw new Exception('operator type error');
        }

        list($operator, $value) = $operatorArr;
        $value = (int)$value;
        $validationResult = true;

        switch ($operator){
            case 'eq':
                $validationResult = $validationValue == $value;
                break;
            case 'ne':
                $validationResult = $validationValue <> $value;
                break;
            case 'gt':
                $validationResult = $validationValue > $value;
                break;
            case 'gte':
                $validationResult = $validationValue >= $value;
                break;
            case 'lt':
                $validationResult = $validationValue < $value;
                break;
            case 'lte':
                $validationResult = $validationValue <= $value;
                break;
            default:
                throw new Exception('not found operator');
        }

        if(!$validationResult){
            $message = $this->prepareMessage($validation, $attribute, 'operator');
            $message = strtr($message, [':field:' => $attribute]);
            $validation->appendMessage(new Message($message, $attribute, 'operator'));
        }

        return $validationResult;
    }
}