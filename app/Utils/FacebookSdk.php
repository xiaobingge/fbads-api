<?php


namespace App\Utils;

use Facebook\Facebook;
use Illuminate\Support\Facades\Redirect;

class FacebookSdk extends Facebook
{

    /**
     * 获取 AppId
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->app->getId();
    }

    /**
     * Generate an OAuth 2.0 authorization URL for authentication.
     *
     * @param array $scope
     * @param string $callback_url
     *
     * @return string
     */
    public function getLoginUrl(array $scope = [], $callback_url = '')
    {
        $scope = $this->getScope($scope);
        $callback_url = $this->getCallbackUrl($callback_url);

        return $this->getRedirectLoginHelper()->getLoginUrl($callback_url, $scope);
    }

    /**
     * Generate a re-request authorization URL.
     *
     * @param array $scope
     * @param string $callback_url
     *
     * @return string
     */
    public function getReRequestUrl(array $scope, $callback_url = '')
    {
        $scope = $this->getScope($scope);
        $callback_url = $this->getCallbackUrl($callback_url);

        return $this->getRedirectLoginHelper()->getReRequestUrl($callback_url, $scope);
    }

    /**
     * Generate a re-authentication authorization URL.
     *
     * @param array $scope
     * @param string $callback_url
     *
     * @return string
     */
    public function getReAuthenticationUrl(array $scope = [], $callback_url = '')
    {
        $scope = $this->getScope($scope);
        $callback_url = $this->getCallbackUrl($callback_url);

        return $this->getRedirectLoginHelper()->getReAuthenticationUrl($callback_url, $scope);
    }

    /**
     * Get an access token from a redirect.
     *
     * @param string $callback_url
     * @return \Facebook\Authentication\AccessToken|null
     */
    public function getAccessToken()
    {
        return $this->getRedirectLoginHelper()->getAccessToken();
    }

    /**
     * Get an access token from a redirect.
     *
     * @param string $callback_url
     * @return \Facebook\Authentication\AccessToken|null
     */
    public function getAccessTokenFromRedirect($callback_url = '')
    {
        $callback_url = $this->getCallbackUrl($callback_url);

        return $this->getRedirectLoginHelper()->getAccessToken($callback_url);
    }

    /**
     * Get the fallback scope if none provided.
     *
     * @param array $scope
     *
     * @return array
     */
    private function getScope(array $scope)
    {
        return $scope ?: app('config')->get('facebook-sdk.default_scope');
    }

    /**
     * Get the fallback callback redirect URL if none provided.
     *
     * @param string $callback_url
     *
     * @return string
     */
    private function getCallbackUrl($callback_url)
    {
        $callback_url = $callback_url ?: app('config')->get('facebook-sdk.default_redirect_uri');

        return Redirect::route($callback_url)->getTargetUrl();
    }
}