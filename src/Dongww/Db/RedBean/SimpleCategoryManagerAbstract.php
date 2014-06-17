<?php
/**
 * User: dongww
 * Date: 14-4-21
 * Time: 下午3:46
 */

namespace Dongww\Db\RedBean;

abstract class SimpleCategoryManagerAbstract extends ManagerAbstract
{
    protected static $fields = [
        [
            'name'    => 'name',
            'type'    => 'string',
            'options' => [
                'unique' => true,
            ]
        ],
        [
            'name' => 'sort',
            'type' => 'integer',
        ],
    ];
}
