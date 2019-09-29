<?php

namespace app\components\authlogin;

use Yii;
use yii\base\Component;

class AuthInterface extends Component
{

    const HTTP_US_POST_URL = "http://api.unrealfront.com/curl_post";
    const HTTP_US_GET_URL = "http://api.unrealfront.com/get-fb-token";

    private $clients = [
        'google' => Google::class,
        'facebook' => Facebook::class,
        'linkedin' => LinkedIn::class,
        'twitter' => Twitter::class,
    ];
    public $classModel;

    public function authClient($client = '')
    {
        if (!array_key_exists($client, $this->clients)) {
            return '当前' . $client . '不存在';
        }
        $this->classModel = $this->getClassModel($client);
        return $this->classModel->login();
    }


    public function authCallback($client = '', $code = '')
    {
        $this->classModel = $this->getClassModel($client);
        $res = $this->classModel->callback($code);
        echo(json_encode($res));
        die;
    }

    public function getClassModel($client)
    {
        $classModel = [
            'class' => $this->clients[$client]
        ];
        return Yii::createObject($classModel);
    }

    public function createAuthUrl($url, $params)
    {
        return $url . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    // 国外接口
    public function _httpRequest($url, $method = 'GET', $params = [])
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        if (strtolower($method) == 'get') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(["url" => $params]));
        } elseif (strtolower($method) == 'post') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        $data = curl_exec($curl);
        curl_close($curl);
        return json_decode($data);
    }

    // 系统本地接口
    public function httpRequest($url, $method = 'GET', $params = [])
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if (strtolower($method) == 'get') {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $params);
        } elseif (strtolower($method) == 'post') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        $data = curl_exec($curl);
        curl_close($curl);
        return json_decode($data);
    }

    public function saveUserInfo($google_userinfo)
    {
        if (!empty($google_userinfo)) {
            $id = $google_userinfo->id;
            $full_name = !empty($google_userinfo->name) ? $google_userinfo->name : '';
            $email = !empty($google_userinfo->email) ? $google_userinfo->email : '';
            $avatar = !empty($google_userinfo->picture) ? $google_userinfo->picture : '';
            // $user_social_account = SocialAccount::findOne(['client_id' => $id]);
            $proMark = User::getProductMark();
            if ($proMark <= 3) {
                $proMarkArr = [0, 1, 2, 3];
            } else if ($proMark == 4 || $proMark == 9) {
                $proMarkArr = [0, $proMark];
            } else {
                $proMarkArr = $proMark;
            }
            $user_social_account = SocialAccount::find()
                ->from(SocialAccount::tableName() . ' as sa')
                ->where(['sa.client_id' => $id, 'sa.type' => 3])
                ->joinWith('user u')
                ->andWhere(['u.product_mark' => $proMarkArr])
                ->one();
            if (!empty($user_social_account)) {
                $user = User::findOne($user_social_account->user_id);
                if (empty($user->status)) {
                    return false;
                }
                $profile = Profile::findOne(['user_id' => $user->id]);
                $profile->full_name = $full_name;
                $profile->avatar = $avatar;
                $profile->save();
            } else {
                $user = new User();
                $user->username = $full_name;
                $user->email = '';
                $user->status = 1;
                if ($user->save()) {
                    $profile = Profile::findOne(['user_id' => $user->id]);
                    if (empty($profile)) {
                        $profile = new Profile();
                        $profile->user_id = $user->id;
                    }
                    $profile->full_name = $full_name;
                    $profile->avatar = $avatar;
                    $profile->save();
                    $social_account = SocialAccount::findOne(['user_id' => $user->id, 'client_id' => $id]);
                    if (empty($social_account)) {
                        $social_account = new SocialAccount();
                        $social_account->user_id = $user->id;
                        $social_account->client_id = $id;
                        $social_account->type = 3;
                        $social_account->email = $email;
                        $social_account->username = $full_name;
                        $social_account->save();
                    }
                }
            }
            $returnUrl = $this->performLogin($user);
            Yii::info(Yii::$app->session, __METHOD__);
            if (strpos(Yii::$app->request->hostInfo, "guangdada") || strpos(Yii::$app->request->hostInfo, "socialpeta")) {
                return $this->redirect("/#/main");
            }
            return $this->redirect($returnUrl);
        }
        return '';
    }
}