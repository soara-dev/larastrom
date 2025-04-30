<?php

namespace Soara\Larastrom\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishCommand extends Command
{
    protected $signature = 'larastrom:publish {--force : Overwrite existing files}';
    protected $description = 'Publish customizable components like controllers, models, and middleware.';

    public function handle()
    {
        $this->info('Publishing customizable components...');

        $this->publishControllers();
        $this->publishModels();
        $this->publishMiddleware();

        $this->info('Publishing completed successfully.');
    }

    protected function publishControllers()
    {
        $controllerPath = app_path('Http/Controllers/Api/Auth/AuthController.php');
        $this->publishFile($controllerPath, $this->getAuthControllerContent(), 'AuthController');
    }

    protected function publishModels()
    {
        $modelPath = app_path('Models/Role.php');
        $this->publishFile($modelPath, $this->getRoleModelContent(), 'Role Model');

        $modelPath = app_path('Models/Permission.php');
        $this->publishFile($modelPath, $this->getPermissionModelContent(), 'Permission Model');
    }

    protected function publishMiddleware()
    {
        $middlewarePath = app_path('Http/Middleware/JwtVerify.php');
        $this->publishFile($middlewarePath, $this->getJwtMiddlewareContent(), 'JwtVerify Middleware');
    }

    protected function publishFile($path, $content, $name)
    {
        if (File::exists($path) && !$this->option('force')) {
            $this->info("$name already exists. Use --force to overwrite.");
            return;
        }

        File::put($path, $content);
        $this->info("$name published successfully.");
    }

    protected function getAuthControllerContent()
    {
        return <<<PHP
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
    }

    protected function getRoleModelContent()
    {
                return <<<PHP
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
    }

    protected function getPermissionModelContent()
    {
                return <<<PHP
        <?php

        namespace App\Models;

        use Spatie\Permission\Models\Permission as PermissionModel;

        class Permission extends PermissionModel
        {
            protected \$table = 'permissions';

            protected \$guarded = ['id'];
        }
        PHP;
    }

    protected function getJwtMiddlewareContent()
    {
                return <<<PHP
        <?php

        namespace App\Http\Middleware;

        use Closure;
        use Illuminate\Http\Request;
        use Symfony\Component\HttpFoundation\Response;
        use Tymon\JWTAuth\Facades\JWTAuth;
        use Exception;

        class JwtVerify
        {
            public function handle(Request \$request, Closure \$next): Response
            {
                try {
                    \$user = JWTAuth:: parseToken()->authenticate();
                } catch (Exception \$e) {
                    if (\$e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                        return response()->json(['message' => 'Token is Invalid'], 401);
                    } else if (\$e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
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
    }
}
