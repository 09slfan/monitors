<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Model;

class HookModel extends Model
{

    public function plugins()
    {
        return $this->belongsToMany('PluginModel', 'hook_plugin', 'plugin', 'hook');
    }

}