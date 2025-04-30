<?php

namespace Soara\Larastrom\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class InstallCommand extends Command
{
    protected $signature = 'larastrom:install-auth';
    protected $description = 'Installs JWT and Spatie Permissions with migrations, routes, and controllers.';

    public function handle()
    {
        $this->info('Starting full installation process...');

        $this->installJwtAuth();
        $this->addJwtMiddleware();
        $this->installSpatiePermissions();

        $this->info('Installation completed successfully.');
    }

    protected function installJwtAuth()
    {
        $this->info('Installing JWT Auth package...');
        $this->runProcess(['composer', 'require', 'tymon/jwt-auth']);
        $this->publishVendorConfig('Tymon\JWTAuth\Providers\LaravelServiceProvider');
        $this->addJwtGuardConfiguration();
        $this->addJwtRoutes();
        $this->addJwtController();
        $this->info('JWT configuration published.');
    }

    protected function addJwtGuardConfiguration()
    {
        $authConfigPath = config_path('auth.php');

        if (File::exists($authConfigPath)) {
            $authConfigContent = File::get($authConfigPath);

            // Check if the 'api' guard already exists
            if (strpos($authConfigContent, "'api' => [") === false) {
                // Add the JWT guard configuration
                $guardConfig = "\n    'api' => [\n        'driver' => 'jwt',\n        'provider' => 'users',\n    ],\n";
                $authConfigContent = preg_replace("/'guards' => \[\n/", "'guards' => [\n$guardConfig", $authConfigContent);
                File::put($authConfigPath, $authConfigContent);
                $this->info('JWT guard configuration added to config/auth.php.');
            } else {
                $this->info('JWT guard configuration already exists in config/auth.php. Skipping addition.');
            }
        } else {
            $this->error('auth.php configuration file not found.');
        }
    }

    protected function installSpatiePermissions()
    {
        if ($this->confirm('Do you want to install Spatie Permissions?')) {
            $this->info('Installing Spatie Permissions package...');
            $this->runProcess(['composer', 'require', 'spatie/laravel-permission']);
            $this->publishVendorConfig('Spatie\Permission\PermissionServiceProvider');
            Artisan::call('migrate');
            $this->info('Spatie Permissions migrations completed.');

            $this->addSpatieRoutes();
            $this->addSpatieController();
        } else {
            $this->info('Skipping Spatie Permissions installation...');
        }
    }

    protected function runProcess(array $command)
    {
        $process = Process::run($command);
        $this->handleProcessOutput($process, $command);
    }

    protected function handleProcessOutput($process, array $command)
    {
        if ($process->successful()) {
            $this->info('Successfully executed: ' . implode(' ', $command));
        } else {
            $this->error('Error executing command: ' . implode(' ', $command) . ' ' . $process->errorOutput());
        }
    }

    protected function publishVendorConfig($provider)
    {
        Artisan::call('vendor:publish', [
            '--provider' => $provider
        ]);
    }

    protected function addSpatieRoutes()
    {
        $routesPath = base_path('routes/api.php');
        $this->checkFileExists($routesPath, 'Routes file not found.');

        $this->appendSpatieRoutes($routesPath);
        $this->info('Spatie Permissions routes added and JWT middleware registered.');
    }
    protected function addJwtMiddleware()
    {
        $middlewarePath = app_path('Http/Middleware/JwtVerify.php');
        $directoryPath = dirname($middlewarePath);

        // Cek apakah direktori ada, jika tidak, buat direktori
        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0755, true);
        }
        if (!File::exists($middlewarePath)) {
            $middlewareContent = <<<PHP
            <?php

            namespace App\Http\Middleware;

            use Closure;
            use Illuminate\Http\Request;
            use Symfony\Component\HttpFoundation\Response;
            use Tymon\JWTAuth\Facades\JWTAuth;
            use Exception;

            class JwtVerify
            {
                /**
                 * Handle an incoming request.
                 *
                 * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  \$next
                 */
                public function handle(Request \$request, Closure \$next): Response
                {
                    try {
                        \$user = JWTAuth::parseToken()->authenticate();
                    } catch (Exception \$e) {
                        if (\$e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                            return response()->json(['message' => 'Token is Invalid'], 401);
                        } else if (\$e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                            // handle token expired
                            if (\$request->url() == url('api/auth/refresh')) {
                                return \$next(\$request);
                            }
                            return response()->json(['message' => 'Token is Expired'], 401);
                        } else if (\$e instanceof \Tymon\JWTAuth\Exceptions\TokenBlacklistedException) {
                            return response()->json(['message' => 'Token is Blacklisted'], 401);
                        } else {
                            return response()->json(['message' => 'Authorization Token not found'], 401);
                        }
                    }
                    return \$next(\$request);
                }
            }
            PHP;

            File::put($middlewarePath, $middlewareContent);
            $this->registerJwtMiddleware();
            $this->info('JWT Verify middleware added.');
        } else {
            $this->info('JWT Verify middleware already exists. Skipping creation.');
        }
    }
    protected function registerJwtMiddleware()
    {
        $appPath = base_path('bootstrap/app.php');
        $this->checkFileExists($appPath, 'bootstrap/app.php not found.');

        $appContent = File::get($appPath);
        $this->addUseStatement($appContent, "use App\\Http\\Middleware\\JwtVerify;");
        $this->addMiddlewareAlias($appContent, "'jwt.verify' => JwtVerify::class");
        $this->addApiMiddleware($appContent, "'jwt.verify' => JwtVerify::class");
        File::put($appPath, $appContent);
    }

    protected function addApiMiddleware(&$content, $alias)
    {
        // Cek apakah alias sudah ada
        if (strpos($content, $alias) === false) {
            $apiMiddlewareCode = "\n        \$middleware->alias([\n            $alias,\n        ]);\n";
            $insertToken = '->withRouting(';
            $insertPosition = strpos($content, $insertToken);

            if ($insertPosition !== false) {
                // Menemukan posisi untuk menambahkan middleware ke routing API
                $insertPosition += strlen($insertToken);
                $content = substr_replace($content, $apiMiddlewareCode, $insertPosition, 0);
            }
        }
    }

    protected function addUseStatement(&$content, $statement)
    {
        if (strpos($content, $statement) === false) {
            $pos = strpos($content, "<?php") + strlen("<?php");
            $content = substr_replace($content, "\n\n$statement", $pos, 0);
        }
    }

    protected function addMiddlewareAlias(&$content, $alias)
    {
        if (strpos($content, $alias) === false) {
            $middlewareCode = "\n        \$middleware->alias([\n            $alias,\n        ]);\n";
            $insertToken = '->withMiddleware(function (Middleware $middleware) {';
            $insertPosition = strpos($content, $insertToken);

            if ($insertPosition !== false) {
                $insertPosition += strlen($insertToken);
                $content = substr_replace($content, $middlewareCode, $insertPosition, 0);
            }
        }
    }

    protected function appendSpatieRoutes($routesPath)
    {
        $routeContent = "\n// Spatie Permissions Routes\n";
        $routeContent .= "Route::middleware(['jwt.verify'])->group(function () {\n";
        $routeContent .= "    Route::post('role/{id}/assign-permission', [RoleController::class, 'assignPermission']);\n";
        $routeContent .= "    Route::apiResource('role', RoleController::class);\n";
        $routeContent .= "    Route::apiResource('permission', PermissionController::class);\n";
        $routeContent .= "});\n";

        File::append($routesPath, $routeContent);
    }

    protected function addSpatieController()
    {
        $this->addRoleModel();
        $this->addRoleController();
        $this->addPermissionModel();
        $this->addPermissionController();
    }

    protected function addRoleModel()
    {
        $modelPath = app_path('Models/Role.php');

        if (!File::exists($modelPath)) {
            $modelContent = <<<PHP
            <?php

            namespace App\Models;

            use Illuminate\Database\Eloquent\Factories\HasFactory;
            use Spatie\Permission\Models\Role as RoleModel;

            class Role extends RoleModel
            {
                use HasFactory;

                protected \$table = 'roles';

                protected \$guarded = ['id'];

                public \$timestamps = false;
            }
            PHP;

            File::put($modelPath, $modelContent);
            $this->info('Role model added.');
        } else {
            $this->info('Role model already exists. Skipping creation.');
        }
    }

    protected function addPermissionModel()
    {
        $modelPath = app_path('Models/Permission.php');

        if (!File::exists($modelPath)) {
            $modelContent = <<<PHP
            <?php

            namespace App\Models;

            use Spatie\Permission\Models\Permission as PermissionModel;

            class Permission extends PermissionModel
            {
                protected \$table = 'permissions';

                protected \$guarded = ['id'];
            }
            PHP;

            File::put($modelPath, $modelContent);
            $this->info('Permission model added.');
        } else {
            $this->info('Permission model already exists. Skipping creation.');
        }
    }

    protected function addRoleController()
    {
        $controllerPath = app_path('Http/Controllers/Api/Role/RoleController.php');
        $directoryPath = dirname($controllerPath);

        // Cek apakah direktori ada, jika tidak, buat direktori
        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0755, true);
        }

        // Cek apakah file controller sudah ada
        if (!File::exists($controllerPath)) {
            $controllerContent = <<<PHP
            <?php

            namespace App\Http\Controllers\Api\Role;

            use Illuminate\Http\Request;
            use App\Models\Role;

            class RoleController extends Controller
            {
                public function index()
                {
                    \$roles = Role::allowInteraction()->orderBy('id', 'desc')->fetch();
                    return setResponse('Successfully retrieved all roles', \$roles);
                }

                public function store(Request \$request)
                {
                    \$request->validate(['name' => 'required|string|max:255|unique:roles,name', 'guard_name' => 'required|string|max:255']);
                    \$role = Role::create(\$request->all());
                    return setResponse('Successfully created role', \$role);
                }

                public function show(\$id)
                {
                    \$role = Role::with('permissions')->find(\$id);
                    return setResponse('Successfully retrieved role', \$role);
                }

                public function update(Request \$request, \$id)
                {
                    \$request->validate(['name' => 'required|string|max:255|unique:roles,name,' . \$id, 'guard_name' => 'required|string|max:255']);
                    \$role = Role::find(\$id)->update(\$request->all());
                    return setResponse('Successfully updated role', \$role);
                }

                public function destroy(\$id)
                {
                    \$role = Role::destroy(\$id);
                    return setResponse('Successfully deleted role', \$role);
                }

                public function assignPermission(Request \$request, \$id)
                {
                    \$role = Role::with('permissions')->find(\$id);
                    \$permissions = array_keys(array_filter(\$request->permissions, fn(\$value) => \$value === true));
                    \$role->syncPermissions(\$permissions);
                    return setResponse('Successfully assigned permission to role', \$role);
                }
            }
            PHP;

            File::put($controllerPath, $controllerContent); // Buat file dengan konten yang ditentukan
            $this->info('Role controller added.');
        } else {
            $this->info('Role controller already exists. Skipping creation.');
        }
    }

    protected function addPermissionController()
    {
        $controllerPath = app_path('Http/Controllers/Api/Permission/PermissionController.php');
        $directoryPath = dirname($controllerPath);

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0755, true);
        }

        if (!File::exists($controllerPath)) {
            $controllerContent = <<<PHP
            <?php

            namespace App\Http\Controllers\Api\Permission;

            use App\Http\Controllers\Controller;
            use App\Models\Permission;
            use Illuminate\Http\Request;

            class PermissionController extends Controller
            {
                public function index()
                {
                    \$permissions = Permission::allowInteraction()->orderBy('id', 'desc')->fetch();
                    return setResponse('Successfully retrieved all permissions', \$permissions);
                }

                public function store(Request \$request)
                {
                    \$request->validate(['name' => 'required|string|max:255|unique:permissions,name', 'guard_name' => 'required|string|max:255']);
                    \$permission = Permission::create(\$request->all());
                    return setResponse('Successfully created permission', \$permission);
                }

                public function show(\$id)
                {
                    \$permission = Permission::find(\$id);
                    return setResponse('Successfully retrieved permission', \$permission);
                }

                public function update(Request \$request, \$id)
                {
                    \$request->validate(['name' => 'required|string|max:255|unique:permissions,name,' . \$id, 'guard_name' => 'required|string|max:255']);
                    \$permission = Permission::find(\$id)->update(\$request->all());
                    return setResponse('Successfully updated permission', \$permission);
                }

                public function destroy(\$id)
                {
                    \$permission = Permission::find(\$id)->delete();
                    return setResponse('Successfully deleted permission', \$permission);
                }
            }
            PHP;

            File::put($controllerPath, $controllerContent);
            $this->info('Permission controller added.');
        } else {
            $this->info('Permission controller already exists. Skipping creation.');
        }
    }

    protected function addJwtRoutes()
    {
        $routesPath = base_path('routes/api.php');
        $this->checkFileExists($routesPath, 'routes/api.php not found.');
        $this->appendJwtRoutes($routesPath);
    }

    protected function appendJwtRoutes($routesPath)
    {
        $routeContent = <<<PHP

        // JWT Authentication Routes
        use App\Http\Controllers\Api\Auth\AuthController;

        Route::prefix('auth')->group(function () {
            Route::post('login', [AuthController::class, "login"]);
            Route::middleware('jwt.verify')->group(function () {
                Route::post('me', [AuthController::class, "me"]);
                Route::post('logout', [AuthController::class, "logout"]);
                Route::post('refresh', [AuthController::class, "refresh"]);
            });
        });

        PHP;

        File::append($routesPath, $routeContent);
        $this->info('JWT authentication routes added.');
    }

    protected function addJwtController()
    {
        $controllerPath = app_path('Http/Controllers/Api/Auth/AuthController.php');
        $directoryPath = dirname($controllerPath);

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0755, true);
        }
        if (!File::exists($controllerPath)) {
            $controllerContent = <<<PHP
        <?php

        namespace App\Http\Controllers\Api\Auth;

        use Illuminate\Http\Request;
        use App\Models\User;

        class AuthController extends Controller
        {
            public function login(Request \$request)
            {
                \$request->validate(['email' => 'required|email|string|max:255', 'password' => 'required|string|max:255']);
                \$credentials = \$request->only('email', 'password');
                if (! \$token = auth()->attempt(\$credentials)) {
                    return response()->json(['message' => 'Email or password is wrong'], 401);
                }
                return \$this->respondWithToken(\$token);
            }

            public function me()
            {
                return response()->json(['message' => 'Successfully retrieved current user', 'data' => auth()->user()]);
            }

            public function logout()
            {
                auth()-> logout();
                return response()->json(['message' => 'Successfully logged out']);
            }

            public function refresh()
            {
                return \$this->respondWithToken(auth()->refresh());
            }

            protected function respondWithToken(\$token)
            {
                return response()->json([
                    'data' => [
                        'access_token' => \$token,
                        'token_type' => 'bearer',
                        'expires_in' => auth()->factory()->getTTL() * 60,
                        'auth' => auth()->user(),
                        'permissions' => auth()->user()->getPermissionsViaRoles()
                    ],
                    'message' => 'Successfully logged in'
                ]);
            }
        }
        PHP;

            File::put($controllerPath, $controllerContent);
            $this->info('JWT Auth controller added.');
        } else {
            $this->info('JWT Auth controller already exists. Skipping creation.');
        }
    }

    protected function checkFileExists($filePath, $errorMessage)
    {
        if (!File::exists($filePath)) {
            $this->error($errorMessage);
            exit;
        }
    }
}
