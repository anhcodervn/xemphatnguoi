<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

const TEST_FEATURE_NAME = 'BlogPost';
const TEST_FEATURE_IMPORT = "require base_path('app/Features/BlogPost/routes.php');";
const TEST_FEATURE_BASE_PATH = 'D:/code/build/naptientudong/laravel-app/app/Features/BlogPost';
const TEST_NESTED_FEATURE_NAME = 'Api\\Auth';
const TEST_NESTED_FEATURE_IMPORT = "require base_path('app/Features/Api/Auth/routes.php');";
const TEST_NESTED_FEATURE_BASE_PATH = 'D:/code/build/naptientudong/laravel-app/app/Features/Api/Auth';

beforeEach(function (): void {
    File::deleteDirectory(app_path('Features/'.TEST_FEATURE_NAME));
    File::deleteDirectory(app_path('Features/Api'));

    $this->originalWebRoutesContents = File::get(base_path('routes/web.php'));
    $this->originalApiRoutesContents = File::get(base_path('routes/api.php'));
});

afterEach(function (): void {
    File::put(base_path('routes/web.php'), $this->originalWebRoutesContents);
    File::put(base_path('routes/api.php'), $this->originalApiRoutesContents);
    File::deleteDirectory(app_path('Features/'.TEST_FEATURE_NAME));
    File::deleteDirectory(app_path('Features/Api'));
});

test('it registers the feature command suite', function (): void {
    $commands = array_keys(Artisan::all());

    expect($commands)->toContain('feature:create');
    expect($commands)->toContain('feature:controller');
    expect($commands)->toContain('feature:service');
    expect($commands)->toContain('feature:request');
    expect($commands)->toContain('feature:action');
    expect($commands)->toContain('feature:dto');
    expect($commands)->toContain('feature:resource');
    expect($commands)->not->toContain('feature:model');
    expect($commands)->not->toContain('make:feature');
});

test('feature create scaffolds a valid feature module for web routes', function (): void {
    $this->artisan('feature:create', ['name' => 'blog-post', '--route' => 'web'])
        ->assertSuccessful()
        ->expectsOutput('Feature BlogPost created successfully.')
        ->expectsOutput('  - Controller: '.TEST_FEATURE_BASE_PATH.'/Controllers/BlogPostController.php')
        ->expectsOutput('  - Routes: '.TEST_FEATURE_BASE_PATH.'/routes.php')
        ->expectsOutput('  - README: '.TEST_FEATURE_BASE_PATH.'/README.md')
        ->expectsOutput('  - Route channel: web')
        ->expectsOutput('  - Route import: D:/code/build/naptientudong/laravel-app/routes/web.php')
        ->expectsOutput('  - Feature directory: '.TEST_FEATURE_BASE_PATH);

    expect(app_path('Features/'.TEST_FEATURE_NAME.'/Controllers/BlogPostController.php'))->toBeFile();
    expect(app_path('Features/'.TEST_FEATURE_NAME.'/routes.php'))->toBeFile();
    expect(app_path('Features/'.TEST_FEATURE_NAME.'/README.md'))->toBeFile();

    $routesContents = File::get(app_path('Features/'.TEST_FEATURE_NAME.'/routes.php'));
    $webRoutesContents = File::get(base_path('routes/web.php'));

    expect($routesContents)->toContain("Route::get('/', [BlogPostController::class, 'index'])->name('index');");
    expect($webRoutesContents)->toContain(TEST_FEATURE_IMPORT);
});

test('feature create can register the feature in api routes', function (): void {
    $this->artisan('feature:create', ['name' => 'blog-post', '--route' => 'api'])
        ->assertSuccessful()
        ->expectsOutput('Feature BlogPost created successfully.')
        ->expectsOutput('  - Route channel: api')
        ->expectsOutput('  - Route import: D:/code/build/naptientudong/laravel-app/routes/api.php');

    expect(File::get(base_path('routes/api.php')))->toContain(TEST_FEATURE_IMPORT);
    expect(File::get(base_path('routes/web.php')))->not->toContain(TEST_FEATURE_IMPORT);
});

test('feature create supports nested feature paths', function (): void {
    $this->artisan('feature:create', ['name' => 'Api/Auth', '--route' => 'api'])
        ->assertSuccessful()
        ->expectsOutput('Feature Api\Auth created successfully.')
        ->expectsOutput('  - Controller: '.TEST_NESTED_FEATURE_BASE_PATH.'/Controllers/AuthController.php')
        ->expectsOutput('  - Routes: '.TEST_NESTED_FEATURE_BASE_PATH.'/routes.php')
        ->expectsOutput('  - README: '.TEST_NESTED_FEATURE_BASE_PATH.'/README.md')
        ->expectsOutput('  - Route channel: api')
        ->expectsOutput('  - Route import: D:/code/build/naptientudong/laravel-app/routes/api.php')
        ->expectsOutput('  - Feature directory: '.TEST_NESTED_FEATURE_BASE_PATH);

    expect(app_path('Features/Api/Auth/Controllers/AuthController.php'))->toBeFile();
    expect(File::get(app_path('Features/Api/Auth/Controllers/AuthController.php')))
        ->toContain('namespace App\Features\Api\Auth\Controllers;')
        ->toContain('class AuthController extends Controller');
    expect(File::get(app_path('Features/Api/Auth/routes.php')))
        ->toContain("use App\Features\Api\Auth\Controllers\AuthController;")
        ->toContain("Route::prefix('api/auth')")
        ->toContain("Route::get('/', [AuthController::class, 'index'])->name('index');");
    expect(File::get(base_path('routes/api.php')))->toContain(TEST_NESTED_FEATURE_IMPORT);
});

test('feature create fails when the feature already exists', function (): void {
    File::ensureDirectoryExists(app_path('Features/'.TEST_FEATURE_NAME));

    $this->artisan('feature:create', ['name' => TEST_FEATURE_NAME])
        ->assertFailed()
        ->expectsOutput('Feature BlogPost already exists.');

    expect(File::get(base_path('routes/web.php')))->toBe($this->originalWebRoutesContents);
});

test('feature generators create files inside the feature namespace', function (
    string $command,
    string $name,
    string $relativePath,
    string $expectedContent
): void {
    $this->artisan('feature:create', ['name' => TEST_FEATURE_NAME])->assertSuccessful();

    $this->artisan($command, [
        'feature' => TEST_FEATURE_NAME,
        'name' => $name,
    ])->assertSuccessful()
        ->expectsOutput('  - File: '.str_replace('\\', '/', $fullPath = app_path('Features/'.TEST_FEATURE_NAME.'/'.$relativePath)));

    $fullPath = app_path('Features/'.TEST_FEATURE_NAME.'/'.$relativePath);

    expect($fullPath)->toBeFile();
    expect(File::get($fullPath))->toContain($expectedContent);
})->with([
    'controller' => ['feature:controller', 'Admin', 'Controllers/AdminController.php', 'class AdminController extends Controller'],
    'service' => ['feature:service', 'Publish', 'Services/PublishService.php', 'class PublishService'],
    'request' => ['feature:request', 'StorePost', 'Requests/StorePostRequest.php', 'class StorePostRequest extends FormRequest'],
    'action' => ['feature:action', 'SyncInventory', 'Actions/SyncInventoryAction.php', 'class SyncInventoryAction'],
    'dto' => ['feature:dto', 'Summary', 'DTOs/SummaryDto.php', 'final class SummaryDto'],
    'resource' => ['feature:resource', 'Post', 'Resources/PostResource.php', 'class PostResource extends JsonResource'],
]);

test('feature generators support nested feature paths from the features root', function (): void {
    $this->artisan('feature:create', ['name' => 'Api/Auth', '--route' => 'api'])->assertSuccessful();

    $this->artisan('feature:controller', [
        'feature' => 'Api/Auth',
        'name' => 'Login',
    ])->assertSuccessful()
        ->expectsOutput('  - File: D:/code/build/naptientudong/laravel-app/app/Features/Api/Auth/Controllers/LoginController.php');

    $this->artisan('feature:controller', [
        'feature' => 'Api/Auth',
    ])->assertFailed()
        ->expectsOutput('Controller AuthController already exists in feature Api\Auth.');

    $this->artisan('feature:service', [
        'feature' => 'Api/Auth',
        'name' => 'Token',
    ])->assertSuccessful()
        ->expectsOutput('  - File: D:/code/build/naptientudong/laravel-app/app/Features/Api/Auth/Services/TokenService.php');

    $this->artisan('feature:request', [
        'feature' => 'Api/Auth',
        'name' => 'Login',
    ])->assertSuccessful()
        ->expectsOutput('  - File: D:/code/build/naptientudong/laravel-app/app/Features/Api/Auth/Requests/LoginRequest.php');

    expect(app_path('Features/Api/Auth/Controllers/LoginController.php'))->toBeFile();
    expect(app_path('Features/Api/Auth/Controllers/AuthController.php'))->toBeFile();
    expect(app_path('Features/Api/Auth/Services/TokenService.php'))->toBeFile();
    expect(app_path('Features/Api/Auth/Requests/LoginRequest.php'))->toBeFile();

    expect(File::get(app_path('Features/Api/Auth/Controllers/LoginController.php')))
        ->toContain('namespace App\Features\Api\Auth\Controllers;')
        ->toContain('class LoginController extends Controller');
    expect(File::get(app_path('Features/Api/Auth/Controllers/AuthController.php')))
        ->toContain('namespace App\Features\Api\Auth\Controllers;')
        ->toContain('class AuthController extends Controller');
    expect(File::get(app_path('Features/Api/Auth/Services/TokenService.php')))
        ->toContain('namespace App\Features\Api\Auth\Services;')
        ->toContain('class TokenService');
    expect(File::get(app_path('Features/Api/Auth/Requests/LoginRequest.php')))
        ->toContain('namespace App\Features\Api\Auth\Requests;')
        ->toContain('class LoginRequest extends FormRequest')
        ->toContain('use App\Exceptions\ApiException;')
        ->toContain('use Illuminate\Contracts\Validation\Validator;')
        ->toContain('public function messages(): array')
        ->toContain('public function attributes(): array')
        ->toContain('protected function failedValidation(Validator $validator): void')
        ->toContain("throw new ApiException(\$validator->errors()->first(), 422);");
});

test('feature generator fails when the feature does not exist', function (): void {
    $this->artisan('feature:service', [
        'feature' => TEST_FEATURE_NAME,
        'name' => 'Publish',
    ])->assertFailed()
        ->expectsOutput('Feature BlogPost does not exist. Run feature:create first.');
});
