<?php

if (file_exists(CMF_ROOT . "data/config/app.php")) {
    $runtimeConfig = include CMF_ROOT . "data/config/app.php";
} else {
    $runtimeConfig = [];
}
$configs = [
    'record_url' => 'https://ycycc.yunchuanglive.com/',
    'open_push' => true,
    'module'    =>[
    	'1'=>[    //学校
    		['alias'=>'学校简介','url'=>'card/school/info','cover'=>'/static/images/icon/paper-plane.png','new_show'=>'','list_order'=>100],
    		['alias'=>'食堂信息','url'=>'card/canteen/index','cover'=>'/static/images/icon/store.png','new_show'=>'','list_order'=>100],
    		['alias'=>'消息通知','url'=>'card/chat/index','cover'=>'/static/images/icon/megaphone.png','new_show'=>'','list_order'=>100],
    		['alias'=>'视频直播','url'=>'card/video/cIndex','cover'=>'/static/images/icon/video.png','new_show'=>'','list_order'=>100],
    		['alias'=>'留言板','url'=>'card/comment/index','cover'=>'/static/images/icon/speech-bubble.png','new_show'=>'','list_order'=>100],
    		['alias'=>'个人中心','url'=>'card/user/info','cover'=>'/static/images/icon/suit.png','new_show'=>'','list_order'=>100],
    	],
    	'2'=>[    //食堂
    		['alias'=>'食堂简介','url'=>'card/canteen/info','cover'=>'/static/images/icon/store.png','new_show'=>'','list_order'=>100],
    		['alias'=>'每日上传','url'=>'card/mission/index','cover'=>'/static/images/icon/pencil-rockets.png','new_show'=>'','list_order'=>100],
    		['alias'=>'每日台账','url'=>'card/mission/list','cover'=>'/static/images/icon/website-speed.png','new_show'=>'','list_order'=>100],
    		['alias'=>'信息设置','url'=>'card/canteen/more','cover'=>'/static/images/icon/website-settings.png','new_show'=>'','list_order'=>100],
    		['alias'=>'个人中心','url'=>'card/user/info','cover'=>'/static/images/icon/suit.png','new_show'=>'','list_order'=>100],
    	],
    	'3'=>[    //家长
    		['alias'=>'学校简介','url'=>'card/school/info','cover'=>'/static/images/icon/paper-plane.png','new_show'=>'','list_order'=>100],
    		['alias'=>'食堂简介','url'=>'card/canteen/index','cover'=>'/static/images/icon/store.png','new_show'=>'','list_order'=>100],
    		['alias'=>'每日清单','url'=>'card/mission/list','cover'=>'/static/images/icon/website-speed.png','new_show'=>'','list_order'=>100],
    		['alias'=>'视频直播','url'=>'card/video/cIndex','cover'=>'/static/images/icon/video.png','new_show'=>'','list_order'=>100],
    		['alias'=>'留言板','url'=>'card/comment/index','cover'=>'/static/images/icon/speech-bubble.png','new_show'=>'','list_order'=>100],
            //['alias'=>'消息通知','url'=>'card/chat/index','cover'=>'/static/images/icon/megaphone.png','new_show'=>'','list_order'=>100],
            ['alias'=>'充值服务','url'=>'card/pay/index','cover'=>'/static/images/icon/diamond.png','new_show'=>'','list_order'=>100],
            ['alias'=>'个人中心','url'=>'card/user/info','cover'=>'/static/images/icon/suit.png','new_show'=>'','list_order'=>100],
    	],
    	'4'=>[    //行政监管
    		['alias'=>'学校简介','url'=>'card/school/index','cover'=>'/static/images/icon/paper-plane.png','new_show'=>'','list_order'=>100],
    		['alias'=>'食堂简介','url'=>'card/canteen/index','cover'=>'/static/images/icon/store.png','new_show'=>'','list_order'=>100],
    		['alias'=>'每日台账','url'=>'card/mission/list4','cover'=>'/static/images/icon/website-speed.png','new_show'=>'','list_order'=>100],
    		['alias'=>'视频直播','url'=>'card/video/cIndex4','cover'=>'/static/images/icon/video.png','new_show'=>'','list_order'=>100],
    		['alias'=>'留言板','url'=>'card/comment/index','cover'=>'/static/images/icon/speech-bubble.png','new_show'=>'','list_order'=>100],
            //['alias'=>'消息通知','url'=>'card/chat/index','cover'=>'/static/images/icon/megaphone.png','new_show'=>'','list_order'=>100],
            ['alias'=>'个人中心','url'=>'card/user/info','cover'=>'/static/images/icon/suit.png','new_show'=>'','list_order'=>100],
    	],
    ],
];
return array_merge($configs, $runtimeConfig);