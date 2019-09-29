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

}