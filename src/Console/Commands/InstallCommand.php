<?php

namespace Soara\Larastrom\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larastrom:install-auth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Installs JWT and Spatie Permissions with migrations, routes, and controllers.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Starting installation process...');

        // Publish JWT config
        Artisan::call('vendor:publish', [
            '--provider' => 'Tymon\JWTAuth\Providers\LaravelServiceProvider',
            '--tag' => 'config',
        ]);
        $this->info('JWT configuration published.');

        // Ask user if they want to install Spatie Permissions
        $useSpatie = $this->choice(
            'Do you want to install Spatie Permissions?',
            ['yes', 'no'],
            0
        );

        if ($useSpatie === 'yes') {
             // Publish Spatie Permissions config
             Artisan::call('vendor:publish', [
                '--provider' => 'Spatie\Permission\PermissionServiceProvider',
                '--tag' => 'config',
            ]);
            $this->info('Spatie Permissions configuration published.');

            // Run migrations for Spatie Permissions
            Artisan::call('migrate');
            $this->info('Spatie Permissions migrations completed.');

            // Add Spatie Permissions routes and controllers
            $this->addSpatieRoutes();
            $this->addSpatieController();
        } else {
            $this->info('Skipping Spatie Permissions installation...');
        }

        // Add JWT routes and controller
        $this->addJwtRoutes();
        $this->addJwtController();

        $this->info('Installation completed successfully.');
    }

    /**
     * Install a package using composer.
     *
     * @param string $package
     * @param string $version
     */
    protected function installPackage($package, $version)
    {
        $process = Process::run("composer require {$package}:{$version}");

        if ($process->successful()) {
            $this->info("Successfully installed {$package} {$version}");
        } else {
            $this->error("Error installing {$package}: {$process->errorOutput()}");
        }
    }

    /**
     * Check if a package is already installed.
     *
     * @param string $package
     * @return bool
     */
    protected function isPackageInstalled($package)
    {
        $installedPackages = json_decode(file_get_contents(base_path('composer.lock')), true);
        foreach ($installedPackages['packages'] as $installedPackage) {
            if ($installedPackage['name'] === $package) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add Spatie Permissions routes.
     */
    // protected function addSpatieRoutes()
    // {
    //     $routesPath = base_path('routes/api.php');

    //     if (!File::exists($routesPath)) {
    //         $this->error('Routes file not found.');
    //         return;
    //     }

    //     $routeContent = "\n// Spatie Permissions Routes\n";
    //     $routeContent .= "Route::post('role/{id}/assign-permission', [RoleController::class, 'assignPermission']);\n";
    //     $routeContent .= "Route::apiResource('role', RoleController::class);\n";
    //     $routeContent .= "Route::apiResource('permission', PermissionController::class);\n";

    //     File::append($routesPath, $routeContent);
    //     $this->info('Spatie Permissions routes added.');
    // }
    /**
 * Add Spatie Permissions routes.
 */
// protected function addSpatieRoutes()
// {
//     $routesPath = base_path('routes/api.php');

//     if (!File::exists($routesPath)) {
//         $this->error('Routes file not found.');
//         return;
//     }

//     $routeContent = "\n// Spatie Permissions Routes\n";
//     $routeContent .= "Route::post('role/{id}/assign-permission', [RoleController::class, 'assignPermission']);\n";
//     $routeContent .= "Route::apiResource('role', RoleController::class);\n";
//     $routeContent .= "Route::apiResource('permission', PermissionController::class);\n";

//     File::append($routesPath, $routeContent); // Corrected line
//     $this->info('Spatie Permissions routes added.');
// }

protected function addSpatieRoutes()
{
    $routesPath = base_path('routes/api.php');
    $appPath = base_path('bootstrap/app.php');

    // Cek apakah file routes/api.php ada
    if (!File::exists($routesPath)) {
        $this->error('Routes file not found.');
        return;
    }

    // Cek apakah file bootstrap/app.php ada
    if (!File::exists($appPath)) {
        $this->error('bootstrap/app.php not found.');
        return;
    }

    // Baca konten bootstrap/app.php
    $appContent = File::get($appPath);

    // Cek apakah middleware sudah terdaftar
    if (strpos($appContent, 'jwt.verify') === false) {
        // Menyisipkan pendaftaran middleware jwt.verify
        $middlewareRegistration = "\$middleware->alias([\n";
        $middlewareRegistration .= "    'jwt.verify' => App\Http\Middleware\JwtVerify::class,\n";
        $middlewareRegistration .= "]);\n";

        // Menemukan posisi untuk menyisipkan kode
        $insertPosition = strpos($appContent, '->withMiddleware(function (Middleware $middleware) {') + strlen('->withMiddleware(function (Middleware $middleware) {');

        // Menyisipkan kode pendaftaran middleware
        $appContent = substr_replace($appContent, $middlewareRegistration, $insertPosition, 0);

        // Menyimpan kembali konten ke bootstrap/app.php
        File::put($appPath, $appContent);
    }

    // Menambahkan rute Spatie Permissions
    $routeContent = "\n// Spatie Permissions Routes\n";
    $routeContent .= "Route::middleware(['jwt.verify'])->group(function () {\n";
    $routeContent .= "    Route::post('role/{id}/assign-permission', [RoleController::class, 'assignPermission']);\n";
    $routeContent .= "    Route::apiResource('role', RoleController::class);\n";
    $routeContent .= "    Route::apiResource('permission', PermissionController::class);\n";
    $routeContent .= "});\n";

    // Append routes to routes/api.php
    File::append($routesPath, $routeContent);
    $this->info('Spatie Permissions routes added and JWT middleware registered.');
}

    /**
     * Add Spatie Permissions controller.
     */
    protected function addSpatieController()
    {
        $controllerPath = app_path('Http/Controllers/RoleController.php');

        if (!File::exists($controllerPath)) {
            $controllerContent = <<<PHP
            <?php

            namespace App\Http\Controllers;

            use Illuminate\Http\Request;
            use Spatie\Permission\Models\Role;

            class RoleController extends Controller
            {
                public function index()
                {
                    \$roles = Role::allowInteraction()
                        ->orderBy('id', 'desc')
                        ->fetch();

                    return response()->json([
                        'message' => 'Successfully retrieved all roles',
                        'data' => \$roles
                    ]);
                }

                public function store(Request \$request)
                {
                    \$request->validate([
                        'name' => 'required|string|max:255|unique:roles,name',
                        'guard_name' => 'required|string|max:255',
                    ]);

                    \$role = Role::create(\$request->all());

                    return response()->json([
                        'message' => 'Successfully created role',
                        'data' => \$role
                    ]);
                }

                public function show(\$id)
                {
                    \$role = Role::with('permissions')->find(\$id);

                    return response()->json([
                        'message' => 'Successfully retrieved role',
                        'data' => \$role
                    ]);
                }

                public function update(Request \$request, \$id)
                {
                    \$request->validate([
                        'name' => 'required|string|max:255|unique:roles,name,' . \$id,
                        'guard_name' => 'required|string|max:255',
                    ]);

                    \$role = Role::find(\$id)->update(\$request->all());

                    return response()->json([
                        'message' => 'Successfully updated role',
                        'data' => \$role
                    ]);
                }

                public function destroy(\$id)
                {
                    \$role = Role::destroy(\$id);

                    return response()->json([
                        'message' => 'Successfully deleted role',
                        'data' => \$role
                    ]);
                }

                public function assignPermission(Request \$request, \$id)
                {
                    \$role = Role::with('permissions')->find(\$id);
                    \$permissions = array_keys(array_filter(\$request->permissions, function (\$value) {
                        return \$value === true;
                    }));
                    \$role->syncPermissions(\$permissions);

                    return response()->json([
                        'message' => 'Successfully assigned permission to role',
                        'data' => \$role
                    ]);
                }
            }
            PHP;

            File::put($controllerPath, $controllerContent);
            $this->info('Spatie Permissions controller added.');
        } else {
            $this->info('Spatie Permissions controller already exists. Skipping creation.');
        }
    }

    /**
     * Add JWT authentication routes.
     */
    protected function addJwtRoutes()
    {
        $routesPath = base_path('routes/api.php');

        if (!File::exists($routesPath)) {
            $this->info('routes/api.php not found.');
        }

        $this->appendJwtRoutes($routesPath);
    }

    /**
     * Append JWT routes to the api.php file.
     */
    protected function appendJwtRoutes($routesPath)
    {
        $routeContent = <<<PHP

        // JWT Authentication Routes
        use App\Http\Controllers\AuthController;

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

    /**
     * Add JWT controller.
     */
    protected function addJwtController()
    {
        $controllerPath = app_path('Http/Controllers/AuthController.php');

        if (!File::exists($controllerPath)) {
            $controllerContent = <<<PHP
        <?php

        namespace App\Http\Controllers;

        use Illuminate\Http\Request;
        use App\Models\User;

        class AuthController extends Controller
        {
            public function login(Request \$request)
            {
                \$request->validate([
                    'email' => 'required|email|string|max:255',
                    'password' => 'required|string|max:255'
                ]);

                \$credentials = \$request->only('email', 'password');
                if (! \$token = auth()->attempt(\$credentials)) {
                    return response()->json(['message' => 'Email or password is wrong'], 401);
                }

                return \$this->respondWithToken(\$token);
            }

            public function me()
            {
                return response()->json([
                    'message' => 'Successfully retrieved current user',
                    'data' => auth()->user()
                ]);
            }

            public function logout()
            {
                auth()->logout();
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
}
