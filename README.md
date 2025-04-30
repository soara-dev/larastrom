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

## Usage

- [JWT Authentication](#jwt-authentication)
- [Response Format](#response-format)
- [Model Builder](#model-builder)

### JWT Authentication

```php
Route::post('/login', [AuthController::class, 'login']);
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
