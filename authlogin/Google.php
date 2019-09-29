<?php

namespace app\components\authClient;

use Yii;

class Google extends AuthInterface
{
    protected $client_id;
    protected $client_secret;
    protected $redirect_uri;

    public $authUrl = 'https://accounts.google.com/o/oauth2/auth';
    public $tokenUrl = 'https://accounts.google.com/o/oauth2/token';
    public $userinfoUrl = 'https://www.googleapis.com/userinfo/v2/me?oauth_token=';

    public function init()
    {
        parent::init();
        $this->client_id = Yii::$app->params['oAuthConfig']['google']['client_id'];
        $this->client_secret = Yii::$app->params['oAuthConfig']['google']['client_secret'];
        $this->redirect_uri = Yii::$app->params['oAuthConfig']['google']['redirect_uri'];
    }

    public function login()
    {
        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/plus.me',
            'response_type' => 'code',
        ];
        return Yii::$app->getResponse()->redirect($this->createAuthUrl($this->authUrl, $params));
    }

    public function callback($code = '')
    {
        $params = [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'client_secret' => $this->client_secret,
        ];
        $post_data = [
            'url' => $this->tokenUrl,
            'params' => $params,
        ];
        $res = $this->_httpRequest(static::HTTP_US_POST_URL, 'POST', $post_data);
        $access_token = !empty($res->access_token) ? $res->access_token : '';
        $google_url = $this->userinfoUrl . $access_token;
        $userinfo = $this->_httpRequest(static::HTTP_US_GET_URL, 'GET', $google_url);
        return $this->format_userinfo($userinfo);
    }

    public function format_userinfo($userinfo)
    {
        return [
            'opend_id' => $userinfo->id,
            'email' => $userinfo->email,
            'full_name' => $userinfo->name,
            'image_url' => $userinfo->picture,
        ];
    }

}