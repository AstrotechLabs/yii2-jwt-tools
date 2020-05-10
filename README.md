# JWT Tools to Yii Framework 2

![GitHub](https://img.shields.io/github/license/dersonsena/yii2-jwt-tools) ![GitHub repo size](https://img.shields.io/github/repo-size/dersonsena/yii2-jwt-tools) ![Packagist Stars](https://img.shields.io/packagist/stars/dersonsena/yii2-jwt-tools) ![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/dersonsena/yii2-jwt-tools)

JWT Tools is a toolbox that will help you to configure authentication with [JWT](http://jwt.io/) token. Not only authentication but also signature validation, the famous secret key.

My biggest motivation to do this was because I didn't see a easy way to setup a simple JWT Validation with some helper functions. I always needed copy and past whole the code to a new project.

Follow the steps below to install and setup in your project.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

To install, either run:

```bash
$ php composer.phar require dersonsena/yii2-jwt-tools
```

or add

```
"dersonsena/yii2-jwt-tools": "@dev"
```

to the `require` section of your `composer.json` file.

## Usage

### Configuration File

Let's guarantee somes application settings are correct. Open your `config/web.php` and setup such as:

```php
'components' => [
    // ...
    'request' => [
        'enableCookieValidation' => false,
    ],
    'user' => [
        'identityClass' => 'app\models\User',
        'enableAutoLogin' => false,
        'enableSession' => false,
        'loginUrl' => null
    ],
    // ...
```

### Controller

In your controller class, register the [JWTSignatureBehavior](./src/JWTSignatureBehavior.php) and [HttpBearerAuth](https://www.yiiframework.com/doc/api/2.0/yii-filters-auth-httpbearerauth) behaviors in `behaviors()` method, such as below:

```php
use yii\rest\Controller;

class YourCuteController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['jwtValidator'] = [
            'class' => JWTSignatureBehavior::class,
            'secretKey' => Yii::$app->params['jwt']['secret'],
            'except' => ['login'] // it's doesn't run in login action
        ];

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['login'] // it's doesn't run in login action
        ];

        return $behaviors;
    }
}
```

> **NOTE:** in this examples I used `Yii::$app->params['jwt']['secret']` to store my JWT Secret Key, but, I like a lot of the .env files and this information could be stored there

The `JWTSignatureBehavior` will validate the JWT token sent by `Authorization` HTTP Header. If there are some problem with your token this one it will throw one of Exceptions below:

-   [UnauthorizedHttpException](https://www.yiiframework.com/doc/api/2.0/yii-web-unauthorizedhttpexception) with message `Your request was made without an authorization token.` if HTTP Header doesn't exist or token is empty or null.

-   [UnauthorizedHttpException](https://www.yiiframework.com/doc/api/2.0/yii-web-unauthorizedhttpexception) with message `Authentication token is expired.` if token is out of due.

-   [UnauthorizedHttpException](https://www.yiiframework.com/doc/api/2.0/yii-web-unauthorizedhttpexception) with message `The token signature is invalid.` if the token signature is invalid.

If for some reason you need to change the HTTP Header name (to be honest I can't see this scenario) you can change this one setting up the `headerName` property, such as below:

```php
class YourCuteController extends Controller
{
    // ...
    public function behaviors()
    {
        $behaviors['jwtValidator'] = [
            'class' => JWTSignatureBehavior::class,
            'secretKey' => Yii::$app->params['jwt']['secret'],
            'headerName' => 'Auth'
        ];
    }
    // ...
}
```

### Model Identity Class

At this point we know that the token is valid and we can decode this one to authenticate user.

I'm using here `app/models/User` as my User Identity, so, let's implement the `findIdentityByAccessToken()` method of the [IdentityInterface](https://www.yiiframework.com/doc/api/2.0/yii-web-identityinterface) interface:

```php
namespace app\models;

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord implements IdentityInterface
{
    // ...
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        // we don't need to implement this method
    }

    public function validateAuthKey($authKey)
    {
        // we don't need to implement this method
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        $decodedToken = JWTTools::build(Yii::$app->params['jwt']['secret'])
            ->decodeToken($token);

        return static::findOne(['id' => $decodedToken->sub]);
    }
}
```

If all ok, at this point you're able to authenticate with a valid JWT Token.

## Demos

### Generating a token

You can use the [JWTTools](./src/JWTTools.php) methods to make specific things in your project. See some examples below:

```php
use Dersonsena\JWTTools\JWTTools;

$jwtTools = JWTTools::build('my-secret-key');

$token = $jwtTools->getJWT();
$payload = $jwtTools->getPayload()->getData();

var_dump($token);
print_r($payload);
```

This code will be return something like:

```
string(248) "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImtpZCI6ImJlMTgzOTQ4YjJmNjkzZSJ9.eyJzdWIiOiJiZTE4Mzk0OGIyZjY5M2UiLCJpc3MiOiIiLCJhdWQiOiIiLCJpYXQiOjE1ODkxMzEzNjIsImV4cCI6MTU4OTEzNDk2MiwianRpIjoiNTM4NTRiMGQ5MzFkMGVkIn0.-JDBkID1oJ7anC_JLg68AJxbKGK-5ubA83zZlDZYYso"

Array
(
    [sub] => 9c65241853de774
    [iss] =>
    [aud] =>
    [iat] => 1589129672
    [exp] => 1589133272
    [jti] => a0a98e2364d2721
)
```

> **NOTE:** the `->getPayload()` returns an instance of the [JWTPayload](./src/JWTPayload.php).

### Generating Token with an Active Record

You can insert the active record attributes in your payload using `withModel()` method, like this:

```php
use Dersonsena\JWTTools\JWTTools;

$user = app\models\User::findOne(2);

$payload = JWTTools::build('my-secret-key');
    ->withModel($user, ['id', 'name', 'email'])
    ->getPayload()
    ->getData();

print_r($payload);
```

This code will be return something like:

```
Array
(
    [sub] => 10                   <~~~~
    [iss] =>
    [aud] =>
    [iat] => 1589130028
    [exp] => 1589133628
    [jti] => 7aba5b7666d7868
    [id] => 10                    <~~~~
    [name] => Kilderson Sena      <~~~~
    [email] => email@email.com.br <~~~~
)
```

The `sub` property is automatically override to `$model->getPrimaryKey()` value, following the [RFC7519](https://tools.ietf.org/html/rfc7519#section-4.1) instructions.

### Changing JWT Properties

You can change the JWT Properties (such as `iss`, `aud` etc) adding an array in second method parameter, as below:

```php
use Dersonsena\JWTTools\JWTTools;

$payload = JWTTools::build('my-secret-key', [
    'algorithm' => 'ES256',
    'expiration' => 1589069866,  //<~~ It will generate the exp property automatically
    'iss' => 'yourdomain.com',
    'aud' => 'yourdomain.com',
]);
```

## Authors

-   [Kilderson Sena](https://github.com/dersonsena) - Initial work - [Yii Academy](https://www.yiiacademy.com.br)

See also the list of [contributors](https://github.com/dersonsena/yii2-jwt-tools/contributors) who participated in this project.

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## Licence

This package is released under the [MIT](https://choosealicense.com/licenses/mit/) License. See the bundled [LICENSE](./LICENSE) for details.
