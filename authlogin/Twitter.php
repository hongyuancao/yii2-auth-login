<?php

namespace app\components\authlogin;

use Yii;

class Twitter extends AuthInterface
{
    protected $client_id;
    protected $client_secret;
    protected $redirect_uri;

    public $authUrl = 'https://www.linkedin.com/oauth/v2/authorization';
    public $tokenUrl = 'https://www.linkedin.com/oauth/v2/accessToken';
    public $userinfoUrl = 'https://api.linkedin.com/v2/me';
    public $useremailUrl = 'https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))';

    public function init()
    {
        parent::init();
        $this->client_id = Yii::$app->params['oAuthConfig']['linkedin']['client_id'];
        $this->client_secret = Yii::$app->params['oAuthConfig']['linkedin']['client_secret'];
        $this->redirect_uri = Yii::$app->params['oAuthConfig']['linkedin']['redirect_uri'];
    }

    public function login()
    {
        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => 'code',
            'scope' => "r_emailaddress r_liteprofile w_member_social",
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
            'grant_type' => 'authorization_code',
        ];
        return $this->httpRequest($this->tokenUrl, 'POST', $params)->access_token;
    }

    public function get_userinfo($access_token)
    {
        $headers[] = "Accept: application/json";
        $headers[] = "Connection: Keep-Alive";
        $headers[] = "Authorization: Bearer " . $access_token;
        $res_userinfo = $this->httpRequest($this->userinfoUrl, 'GET', $headers);
        $res_useremail = $this->httpRequest($this->useremailUrl, 'GET', $headers);

        return $this->format_userinfo($res_userinfo, $res_useremail);
    }

    public function format_userinfo($res_userinfo, $res_useremail)
    {
        $id = $res_userinfo->id;
        $full_name = $res_userinfo->localizedLastName . $res_userinfo->localizedFirstName;
        $image_urn = '';
        $image_url = '';
        if (!empty($res_userinfo->profilePicture)) {
            $image_urn = explode(':', $res_userinfo->profilePicture->displayImage)[3];
        }
        if (!empty($image_urn)) {
            $image_url = 'https://media.licdn.com/dms/image/' . $image_urn . '/profile-displayphoto-shrink_100_100/0?e=1571875200&v=beta&t=zsfmEcnLInP83m5Asfvra3fvNjxP5AtTSgsXkg2SX6Y';
        }
        $email = json_encode($res_useremail->elements[0]);
        return [
            'opend_id' => $id,
            'email' => json_decode($email, true)['handle~']['emailAddress'],
            'full_name' => $full_name,
            'image_url' => $image_url,
        ];
    }
}