# All In One Fullstack Tools For Laravel ( Larastrom )

Larastrom is a developer-friendly Laravel package that speeds up and simplifies the process of building RESTful APIs. It includes ready-to-use tools and smart defaults so you can focus on your business logic, not boilerplate code.

## 🚀 Features

- 🔐 **Automatic JWT Authentication**  
  Built-in support for JWT-based auth with zero configuration.

- 🔁 **Consistent API Response Format**  
  Unified response structure for success and error handling across all endpoints.

- 🔎 **Search & Sorting Made Easy**  
  Just pass query parameters like `?searchField[name]=john&sortField[created_at]=desc` — no extra code required.

- 📦 **Pagination Made Easy**
  Just pass query parameters like `?pageSize=10` — no extra code required.

---

## Installation

```
composer require soara/larastrom
```

Add the `LarastromServiceProvider` to your `bootstrap/providers.php` file:

```php
return [
    App\Providers\AppServiceProvider::class,
    Soara\Larastrom\LarastromServiceProvider::class, // add this line
    Tymon\JWTAuth\Providers\LaravelServiceProvider::class // add this line
];
```

## Usage

- [JWT Authentication](#jwt-authentication)
- [Response Format](#response-format)
- [Model Builder](#model-builder)

### JWT Authentication

To enable authentication please run the following command:

```
php artisan install:api
php artisan larastrom:install-auth

# jwt
php artisan key:generate

# spatie
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

Add Middleware for jwt verify in file `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'jwt.verify' => App\Http\Middleware\JwtVerify::class,
    ]);
})
```

Setup default guard in file `config/auth.php`:

```php
'defaults' => [
    'guard' => 'api',
    'passwords' => 'users',
],

'guards' => [
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

Add authtenticate route to file `routes/api.php`:

```php
use App\Http\Controllers\Api\Auth\AuthController;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, "login"]);
    Route::middleware('jwt.verify')->group(function () {
        Route::post('me', [AuthController::class, "me"]);
        Route::post('logout', [AuthController::class, "logout"]);
        Route::post('refresh', [AuthController::class, "refresh"]);
    });
});
```

Update your `User` model:

```php
<?php

namespace App;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable, HasRoles;

    // Rest omitted for brevity

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
```

### Response Format

To return a successful response from your controller, use the following format:

```php
return setResponse('Get data successfully', $data); // 200 = OK

return setResponse('Opps something wrong', [], 500); // 500 = Internal Server Error
```

### Model Builder

Model Builder can provide a simple pagination, sorting and searching functionality for your database models.

```js
const req = axios.get('/users', {
    params: {
        pageSize: 10, // if you want to use pagination
        searchField: { // if you want to use searching
            name: 'john',
            ...
        },
        sortField: { // if you want to use sorting
            created_at: 'desc',
            ...
        }
    }
})
```

In your model add traits:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Soara\Larastrom\Traits\WithBuilder; // add this line


class User extends Model
{
    use WithBuilder; // add this line
}
```

implement in your controller:

```php
$user = User::allowSearch()->allowOrder()->fetch();
```

Or shorthand:

```php
$user = User::allowInteraction()->fetch();
```

## License

This package is open source and released under the [MIT License](https://opensource.org/licenses/MIT).
