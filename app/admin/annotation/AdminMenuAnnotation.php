<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\admin\annotation;

use mindplay\annotations\Annotation;

/**
 * Specifies validation of a string, requiring a minimum and/or maximum length.
 *
 * @usage('method'=>true, 'inherited'=>true, 'multiple'=>false)
 */
class AdminMenuAnnotation extends Annotation
{
    public $remark = '';

    public $icon = '';

    public $name = '';

    public $param = '';

    public $parent = '';

    public $display = false;

    public $order = 10000;

    public $hasView = true;

    /**
     * Initialize the annotation.
     * @param array $properties
     */
    public function initAnnotation(array $properties)
    {
        parent::initAnnotation($properties);
    }
}
