<?php

namespace Dersonsena\JWTTools;

use Dersonsena\JWTTools\JWTTools;
use Yii;
use yii\base\ActionFilter;
use yii\web\UnauthorizedHttpException;

class JWTSignatureBehavior extends ActionFilter
{
    /**
     * @var string
     */
    public $headerName = 'Authorization';

    /**
     * @var string
     */
    public $secretKey;

    /**
     * @inheritDoc
     */
    public function beforeAction($action)
    {
        $authorizationHeader = Yii::$app->request->getHeaders()->get($this->headerName);

        if (!$authorizationHeader) {
            throw new UnauthorizedHttpException('Your request was made without an authorization token.');
        }

        $token = explode(' ', $authorizationHeader)[1];

        $jwtTools = JWTTools::build($this->secretKey);

        if ($jwtTools->tokenIsExpired($token)) {
            throw new UnauthorizedHttpException('Authentication token is expired.');
        }

        if (!$jwtTools->signatureIsValid($token)) {
            throw new UnauthorizedHttpException('The token signature is invalid.');
        }

        return true;
    }
}
