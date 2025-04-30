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

    protected $withConfirmation = true;

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
        // $authConfigPath = config_path('auth.php');

        // if (File::exists($authConfigPath)) {
        //     $authConfigContent = File::get($authConfigPath);

        //     // Check if the 'api' guard already exists
        //     if (strpos($authConfigContent, "'api' => [") === false) {
        //         // Add the JWT guard configuration
        //         $guardConfig = "\n    'api' => [\n        'driver' => 'jwt',\n        'provider' => 'users',\n    ],\n";
        //         $authConfigContent = preg_replace("/'guards' => \[\n/", "'guards' => [\n$guardConfig", $authConfigContent);
        //         File::put($authConfigPath, $authConfigContent);
        //         $this->info('JWT guard configuration added to config/auth.php.');
        //     } else {
        //         $this->info('JWT guard configuration already exists in config/auth.php. Skipping addition.');
        //     }
        // } else {
        //     $this->error('auth.php configuration file not found.');
        // }
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
            $this->withConfirmation = false;
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
        // $middlewarePath = app_path('Http/Middleware/JwtVerify.php');
        // $directoryPath = dirname($middlewarePath);

        // if (!File::exists($directoryPath)) {
        //     File::makeDirectory($directoryPath, 0755, true);
        // }
        // if (!File::exists($middlewarePath)) {
        //     $middlewareContent = file_get_contents(realpath(__DIR__ . '/../../../stub/JwtVerify.stub'));
        //     File::put($middlewarePath, $middlewareContent);
        //     $this->registerJwtMiddleware();
        //     $this->info('JWT Verify middleware added.');
        // } else {
        //     $this->info('JWT Verify middleware already exists. Skipping creation.');
        // }
    }
    protected function registerJwtMiddleware()
    {
        // $appPath = base_path('bootstrap/app.php');
        // $this->checkFileExists($appPath, 'bootstrap/app.php not found.');

        // $appContent = File::get($appPath);
        // $this->addUseStatement($appContent, "use App\\Http\\Middleware\\JwtVerify;");
        // $this->addMiddlewareAlias($appContent, "'jwt.verify' => JwtVerify::class");
        // File::put($appPath, $appContent);
    }

    protected function addMiddlewareAlias(&$content, $alias)
    {
        // if (strpos($content, $alias) === false) {
        //     $middlewareCode = "\n        \$middleware->alias([\n            $alias,\n        ]);\n";
        //     $insertToken = '->withMiddleware(function (Middleware $middleware) {';
        //     $insertPosition = strpos($content, $insertToken);

        //     if ($insertPosition !== false) {
        //         $insertPosition += strlen($insertToken);
        //         $content = substr_replace($content, $middlewareCode, $insertPosition, 0);
        //     }
        // }
    }

    protected function addApiMiddleware(&$content)
    {
        // Cek apakah pengaturan API sudah ada
        // if (strpos($content, 'api: __DIR__ . \'/../routes/api.php\'') === false) {
        //     $apiRoutingCode = ",\n        api: __DIR__ . '/../routes/api.php'";
        //     $insertToken = '->withRouting(';
        //     $insertPosition = strpos($content, $insertToken);

        //     if ($insertPosition !== false) {
        //         $insertPosition += strlen($insertToken);
        //         $content = substr_replace($content, $apiRoutingCode, $insertPosition, 0);
        //     }
        // }
    }

    protected function addUseStatement(&$content, $statement)
    {
        if (strpos($content, $statement) === false) {
            $pos = strpos($content, "<?php") + strlen("<?php");
            $content = substr_replace($content, "\n\n$statement", $pos, 0);
        }
    }

    protected function appendSpatieRoutes($routesPath)
    {
        // $routeContent = "\n// Spatie Permissions Routes\n";
        // $routeContent .= "Route::middleware(['jwt.verify'])->group(function () {\n";
        // $routeContent .= "    Route::post('role/{id}/assign-permission', [RoleController::class, 'assignPermission']);\n";
        // $routeContent .= "    Route::apiResource('role', RoleController::class);\n";
        // $routeContent .= "    Route::apiResource('permission', PermissionController::class);\n";
        // $routeContent .= "});\n";

        // File::append($routesPath, $routeContent);
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
            $modelContent = file_get_contents(realpath(__DIR__ . '/../../../stub/Role.stub'));

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
            $modelContent = file_get_contents(realpath(__DIR__ . '/../../../stub/Permission.stub'));

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

        // Cek if directory exists
        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0755, true);
        }

        // Cek if role controller already exists
        if (!File::exists($controllerPath)) {
            $controllerContent = file_get_contents(realpath(__DIR__ . '/../../../stub/RoleController.stub'));

            File::put($controllerPath, $controllerContent);
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
            $controllerContent = file_get_contents(realpath(__DIR__ . '/../../../stub/PermissionController.stub'));

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
        $routeContent = file_get_contents(realpath(__DIR__ . '/../../../stub/RouteContent.stub'));
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
            if ($this->withConfirmation) {
                $controllerContent = file_get_contents(realpath(__DIR__ . '/../../../stub/AuthController.stub'));
            } else {
                $controllerContent = file_get_contents(realpath(__DIR__ . '/../../../stub/AuthCOntrollerWihoutPermission.stub'));
            }

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
