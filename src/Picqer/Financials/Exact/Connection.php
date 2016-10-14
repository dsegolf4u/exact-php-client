<?php namespace Picqer\Financials\Exact;

use CommerceGuys\Guzzle\Plugin\Oauth2\GrantType\AuthorizationCode;
use CommerceGuys\Guzzle\Plugin\Oauth2\GrantType\RefreshToken;
use CommerceGuys\Guzzle\Plugin\Oauth2\Oauth2Plugin;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\ServerErrorResponseException;
use Guzzle\Http\Message\Response;

class Connection {

    private $apiUrl = 'https://start.exactonline.nl/api/v1';
    private $authUrl = 'https://start.exactonline.nl/api/oauth2/auth';
    private $tokenUrl = 'https://start.exactonline.nl/api/oauth2/token';

    private $exactClientId;
    private $exactClientSecret;
    private $authorizationCode;
    private $accessToken;
    private $refreshToken;
    private $redirectUrl;
    private $division;
    private $nextUrl;

    private $lastRequest;
    private $lastResponse;

    /**
     * @var Client
     */
    private $client;

    public function client()
    {
        if ($this->client) return $this->client;

        $this->client = new Client($this->apiUrl);
        $this->client->setDefaultOption('headers/Accept', 'application/json');
        $this->client->setDefaultOption('headers/Content-Type', 'application/json');

        $this->authenticate();

        return $this->client;
    }

    public function get($url, array $params = array())
    {
        if ($url !== 'current/Me') $this->addDivisionNumberToApiUrl();

        $this->clearLastRequestAndLastResponse();

        $request = $this->client()->createRequest('GET', $url);
        $this->setLastRequest($request);

        $query = $request->getQuery();

        foreach ($params as $paramName => $paramValue)
        {
            $query->set($paramName, $paramValue);
        }

        $result = $this->client()->send($request);
        $this->setLastResponse($result);

        return $this->parseResult($result);
    }


    public function getAll($url, array $params = array())
    {
        $data = $this->get($url, $params);

        $nextUrl = $this->getNextUrl();
        while (!empty($nextUrl)) {

            $nextData = $this->get($nextUrl, $params);
            $data = array_merge($data, $nextData);

            $nextUrl = $this->getNextUrl();
        }

        return $data;
    }

    public function post($url, $body)
    {
        $this->addDivisionNumberToApiUrl();
        $this->clearLastRequestAndLastResponse();
        try
        {
            $request = $this->client()->post($url, null, $body);
            $this->setLastRequest($request);

            $result = $request->send();
            $this->setLastResponse($result);

        } catch (ServerErrorResponseException $e)
        {
            throw new ApiException($e->getResponse()->getBody(true));
        }

        return $this->parseResult($result);
    }

    public function put($url, $body)
    {
        $this->addDivisionNumberToApiUrl();
        $this->clearLastRequestAndLastResponse();

        $request = $this->client()->put($url, null, $body);
        $this->setLastRequest($request);
        $result = $request->send();
        $this->setLastResponse($result);

        return $this->parseResult($result);
    }

    public function delete($url)
    {
        $this->addDivisionNumberToApiUrl();
        $this->clearLastRequestAndLastResponse();

        $request = $this->client()->delete($url);
        $this->setLastRequest($request);
        $result = $request->send();
        $this->setLastResponse($result);

        return $this->parseResult($result);
    }

    public function getAuthUrl()
    {
        return $this->authUrl . '?' . http_build_query(array(
            'client_id'     => $this->exactClientId,
            'redirect_uri'  => $this->redirectUrl,
            'response_type' => 'code'
        ));
    }

    /**
     * @param mixed $exactClientId
     */
    public function setExactClientId($exactClientId)
    {
        $this->exactClientId = $exactClientId;
    }

    /**
     * @param mixed $exactClientSecret
     */
    public function setExactClientSecret($exactClientSecret)
    {
        $this->exactClientSecret = $exactClientSecret;
    }

    /**
     * @param mixed $authorizationCode
     */
    public function setAuthorizationCode($authorizationCode)
    {
        $this->authorizationCode = $authorizationCode;
    }

    /**
     * @param mixed $accessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @param mixed $refreshToken
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

    public function authenticate()
    {
        $params = array(
            'code'          => $this->authorizationCode,
            'redirect_uri'  => $this->redirectUrl,
            'client_id'     => $this->exactClientId,
            'client_secret' => $this->exactClientSecret,
            'refresh_token' => $this->refreshToken
        );

        $oauth2Client = new Client($this->tokenUrl);
        $grantType = new AuthorizationCode($oauth2Client, $params);
        $refreshTokenGrantType = new RefreshToken($oauth2Client, $params);
        $oauth2Plugin = new Oauth2Plugin($grantType, $refreshTokenGrantType);
        if (! empty($this->refreshToken))
            $oauth2Plugin->setRefreshToken($this->refreshToken);

        $this->accessToken = $oauth2Plugin->getAccessToken();
        $this->refreshToken = $oauth2Plugin->getRefreshToken();

        $this->client()->addSubscriber($oauth2Plugin);
    }

    public function redirectForAuthorization()
    {
        header('Location: ' . $this->getAuthUrl(
                $this->exactClientId,
                $this->redirectUrl
            )
        );
        exit;
    }

    /**
     * @param mixed $redirectUrl
     */
    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;
    }

    public function needsAuthentication()
    {
        return empty($this->refreshToken) && empty($this->authorizationCode);
    }

    public function parseResult(Response $response)
    {
        $this->setNextUrl('');
        
        try
        {
            $json = $response->json();
            if (array_key_exists('d', $json))
            {
                if (array_key_exists('__next', $json['d'])) {
                    $this->setNextUrl($json['d']['__next']);
                }

                if (array_key_exists('results', $json['d']))
                {
                    if (count($json['d']['results']) == 1)
                    {
                        return $json['d']['results'][0];
                    }

                    return $json['d']['results'];
                }

                return $json['d'];
            }

            return $json;
        } catch (\RuntimeException $e)
        {
            throw new ApiException($e->getMessage());
        }
    }

    public function addDivisionNumberToApiUrl()
    {
        if (empty($this->division))
        {
            $me = new Me($this);
            $this->division = $me->find()->CurrentDivision;
        }

        $this->client()->setBaseUrl($this->apiUrl . '/' . $this->division);
    }

    /**
     * @return mixed
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @return mixed
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }


    /**
     * @return mixed
     */
    public function getDivision()
    {
        return $this->division;
    }


    /**
     * @param mixed $division
     */
    public function setDivision($division)
    {
        $this->division = $division;
    }

    /**
     * @return mixed
     */
    public function getNextUrl()
    {
        return $this->nextUrl;
    }

    /**
     * @param mixed $nextUrl
     */
    public function setNextUrl($nextUrl)
    {
        $this->nextUrl = $nextUrl;
    }

    /**
     * @return mixed
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * @param mixed $lastRequest
     */
    public function setLastRequest($lastRequest)
    {
        $this->lastRequest = $lastRequest;
    }

    /**
     * @return mixed
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * @param mixed $lastResponse
     */
    public function setLastResponse($lastResponse)
    {
        $this->lastResponse = $lastResponse;
    }

    private function clearLastRequestAndLastResponse()
    {
        $this->setLastRequest('');
        $this->setLastResponse('');
    }

}