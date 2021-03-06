<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace plugins\switch_theme_demo;//Demo插件英文名，改成你的插件英文就行了
use cmf\lib\Plugin;

//Demo插件英文名，改成你的插件英文就行了
class SwitchThemeDemoPlugin extends Plugin
{

    public $info = [
        'name'        => 'SwitchThemeDemo',//Demo插件英文名，改成你的插件英文就行了
        'title'       => '前台模板切换演示',
        'description' => '前台模板切换演示',
        'status'      => 1,
        'author'      => 'ThinkCMF',
        'version'     => '1.0.1',
        'demo_url'    => 'http://demo.thinkcmf.com',
        'author_url'  => 'http://www.thinkcmf.com'
    ];

    public $hasAdmin = 0;//插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        return true;//安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        return true;//卸载成功返回true，失败false
    }

    //实现的switch_theme钩子方法
    public function switchTheme($param)
    {
        $config = $this->getConfig();

        $mobileTheme = empty($config['mobile_theme']) ? '' : $config['mobile_theme'];

        return $mobileTheme;
    }

}