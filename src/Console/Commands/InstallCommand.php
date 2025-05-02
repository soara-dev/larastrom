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
        $this->addJwtController();
        $this->info('JWT configuration published.');
    }



    protected function installSpatiePermissions()
    {
        $this->info('Installing Spatie Permissions package...');
        $this->runProcess(['composer', 'require', 'spatie/laravel-permission']);
        $this->publishVendorConfig('Spatie\Permission\PermissionServiceProvider');
        Artisan::call('migrate');
        $this->info('Spatie Permissions migrations completed.');
        $this->addSpatieController();
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

    protected function addJwtMiddleware()
    {
        $middlewarePath = app_path('Http/Middleware/JwtVerify.php');
        $directoryPath = dirname($middlewarePath);

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0755, true);
        }
        if (!File::exists($middlewarePath)) {
            $middlewareContent = file_get_contents(realpath(__DIR__ . '/../../../stub/JwtVerify.stub'));
            File::put($middlewarePath, $middlewareContent);
            $this->info('JWT Verify middleware added.');
        } else {
            $this->info('JWT Verify middleware already exists. Skipping creation.');
        }
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
        // if (strpos($content, $statement) === false) {
        //     $pos = strpos($content, "<?php") + strlen("<?php");
        //     $content = substr_replace($content, "\n\n$statement", $pos, 0);
        // }
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


    protected function addJwtController()
    {
        $controllerPath = app_path('Http/Controllers/Api/Auth/AuthController.php');
        $directoryPath = dirname($controllerPath);

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0755, true);
        }
        if (!File::exists($controllerPath)) {
            $controllerContent = file_get_contents(realpath(__DIR__ . '/../../../stub/AuthController.stub'));

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
