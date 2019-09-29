# yii2-authlogin 第三方登录说明文档
yii2框架的第三方登录插件

1.0 版本发布，目前支持的登录平台包括：

-   Facebook
-   Google
-   LinkedIn

引入步骤：
1. 下载authlogin代码至yii2框架component文件夹下；
2. 在config/params.php配置登录信息；


### 目录结构

```
.
├── authlogin                         代码源文件目录
│   ├── AuthInterface.php             登录接口
│   │   
│   ├── Facebook.php
│   ├── LinkedIn.php
│   └── Google.php                    
│                    
└── README.md                         说明文件
```


### 典型用法

> 这是在你的登录函数下


```php
<?php

namespace app\mobile\controller;

use app\components\authClient\AuthInterface;

class LoginController extends Controller
{
   
    public function actionOAuthLogin($client = '') {
        $social = new AuthInterface();
        $social->authClient($client);
    }
    
    public function actionOAuthCallback($client = '', $code = ''){
        $social = new AuthInterface();
        $social->authCallback($client, $code);
    }
}
```


### 配置文件样例


#### 在params.php文件中添加：

```
return [
    
    ... ...
    
    'oAuthConfig' => [
        'google' => [
            'client_id' => '999456684324-kt4evh162******',
            'client_secret' => 'RSIoP74gkTNt0sl******',
            'redirect_uri' => 'https://example.com/login/o-auth-callback?client=google',
        ],
        'facebook' => [
            'client_id' => '2443209******',
            'client_secret' => 'd6e574aa9e5caa0******',
            'redirect_uri' => 'https://example.com/login/o-auth-callback?client=facebook',
        ],
        'linkedin' => [
            'client_id'     => '86bgaja46******',
            'client_secret' => 'KrSb3jIA******',
            'redirect_uri'  => 'https://example.com/login/o-auth-callback?client=linkedin',
        ],
    ],
];
```


### 返回样例

```
Array
(
    [open_id] => *******
    [email] => xxx@xxx.com    
    [full_name] => caohongyuan
    [image_url] => https://example.com/photo.jpg
)
```


### 其他

使用中如果有什么问题，请提交 issue，我会及时查看回复
