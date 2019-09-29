<?php

namespace app\components\authlogin;

use Yii;

class Facebook extends AuthInterface
{
    protected $client_id;
    protected $client_secret;
    protected $redirect_uri;

    public $authUrl = 'https://www.facebook.com/v2.10/dialog/oauth';
    public $tokenUrl = 'https://graph.facebook.com/v2.10/oauth/access_token';
    public $userinfoUrl = 'https://graph.facebook.com/v2.10/me';


    public function init()
    {
        parent::init();
        $this->client_id = Yii::$app->params['oAuthConfig']['facebook']['client_id'];
        $this->client_secret = Yii::$app->params['oAuthConfig']['facebook']['client_secret'];
        $this->redirect_uri = Yii::$app->params['oAuthConfig']['facebook']['redirect_uri'];
    }

    public function login()
    {
        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => 'code',
            'scope' => 'email',
        ];
        return Yii::$app->getResponse()->redirect($this->createAuthUrl($this->authUrl, $params));
    }

    public function callback($code)
    {
        return $this->get_userinfo($this->get_access_token($code));
    }

    public function get_access_token($code)
    {
        $params = [
            'code' => $code,
            'redirect_uri' => $this->redirect_uri,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'appsecret_proof' => $this->get_appsecret_proof(),
        ];
        $access_token_url = $this->createAuthUrl($this->tokenUrl, $params);
        return $this->_httpRequest(static::HTTP_US_GET_URL, 'GET', $access_token_url)->access_token;
    }

    public function get_userinfo($access_token)
    {
        $user_params = [
            'access_token' => $access_token,
            'appsecret_proof' => hash_hmac('sha256', $access_token, $this->client_secret),
            'fields' => 'id,name,email,picture',
        ];
        $userinfo_url = $this->createAuthUrl($this->userinfoUrl, $user_params);
        return $this->format_userinfo($this->_httpRequest(static::HTTP_US_GET_URL, 'GET', $userinfo_url));
    }

    public function format_userinfo($userinfo)
    {
        return [
            'opend_id' => $userinfo->id,
            'email' => $userinfo->email,
            'full_name' => $userinfo->name,
            'image_url' => $userinfo->picture->data->url,
        ];
    }

    public function get_appsecret_proof()
    {
        return hash_hmac('sha256', $this->client_id . '|' . $this->client_secret, $this->client_secret);
    }

}