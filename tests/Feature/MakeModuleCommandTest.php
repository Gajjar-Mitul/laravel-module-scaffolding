<?php

namespace LaravelScaffolding\Scaffolding\Tests\Feature;

use Illuminate\Support\Facades\File;
use LaravelScaffolding\Scaffolding\Tests\TestCase;

class MakeModuleCommandTest extends TestCase
{
    protected function tearDown(): void
    {
    File::deleteDirectory(base_path('app/Domains'));
    File::deleteDirectory(base_path('resources/views/modules'));
    File::deleteDirectory(base_path('resources/js/project'));
        File::deleteDirectory(base_path('scaffolding'));
    File::put(base_path('routes/web.php'), "<?php\n\n");

    config()->set('scaffolding.query.enforce_explicit_select', true);

        parent::tearDown();
    }

    public function test_command_is_registered(): void
    {
        $this->artisan('list')
            ->expectsOutputToContain('make:module')
            ->assertExitCode(0);
    }

    public function test_it_generates_module_from_yaml_definition(): void
    {
        File::ensureDirectoryExists(base_path('scaffolding'));
        File::put(base_path('scaffolding/post.yml'), <<<'YAML'
fields:
  title:
    type: string
    nullable: false
  status:
    type: enum
    values: [draft, published]
    nullable: false
  description:
    type: text
    nullable: true
YAML
);

        $this->artisan('make:module Post --datatable --force')
            ->assertExitCode(0);

        $this->assertFileExists(base_path('app/Domains/Post/Controllers/PostController.php'));
        $this->assertFileExists(base_path('app/Domains/Post/Models/Post.php'));
        $this->assertFileExists(base_path('app/Domains/Post/Queries/PostQueries.php'));
        $this->assertFileExists(base_path('app/Domains/Post/Services/PostService.php'));
        $this->assertFileExists(base_path('app/Domains/Post/Requests/StorePostRequest.php'));
        $this->assertFileExists(base_path('app/Domains/Post/Requests/UpdatePostRequest.php'));
        $this->assertFileExists(base_path('app/Domains/Post/Enums/PostStatusEnum.php'));

        $this->assertFileExists(base_path('resources/views/modules/post/index.blade.php'));
        $this->assertFileExists(base_path('resources/js/project/posts/index.js'));

        $routes = File::get(base_path('routes/web.php'));
        $this->assertStringContainsString("Route::get('posts',", $routes);
        $this->assertStringContainsString("->name('posts.index')", $routes);
        $this->assertStringContainsString("Route::get('posts/create',", $routes);
        $this->assertStringContainsString("Route::post('posts',", $routes);
        $this->assertStringContainsString("Route::get('posts/{post}',", $routes);
        $this->assertStringContainsString("Route::get('posts/{post}/edit',", $routes);
        $this->assertStringContainsString("Route::put('posts/{post}',", $routes);
        $this->assertStringContainsString("Route::patch('posts/{post}',", $routes);
        $this->assertStringContainsString("Route::delete('posts/{post}',", $routes);
        $this->assertStringNotContainsString("Route::resource('posts'", $routes);

        $controller = File::get(base_path('app/Domains/Post/Controllers/PostController.php'));
        $this->assertStringContainsString('private readonly PostService $service', $controller);
        $this->assertStringContainsString('$this->service->create(', $controller);
        $this->assertStringNotContainsString('auth()->user()', $controller);
        $this->assertStringContainsString('$posts = $this->queries->getAll();', $controller);

        $query = File::get(base_path('app/Domains/Post/Queries/PostQueries.php'));
        $this->assertStringContainsString('public function getAll(array $columns = [\'id\', \'title\', \'status\', \'description\', \'created_by\', \'updated_by\', \'created_at\', \'updated_at\'])', $query);
        $this->assertStringNotContainsString('Authenticatable', $query);
        $this->assertStringNotContainsString('applyRoleScope', $query);

        $updateRequest = File::get(base_path('app/Domains/Post/Requests/UpdatePostRequest.php'));
        $this->assertStringContainsString('use Illuminate\\Validation\\Rule;', $updateRequest);
        $this->assertStringContainsString('use App\\Domains\\Post\\Enums\\PostStatusEnum;', $updateRequest);
        $this->assertStringContainsString("Rule::enum(PostStatusEnum::class)", $updateRequest);
        $this->assertStringNotContainsString("in:draft,published", $updateRequest);

        $this->assertFileDoesNotExist(base_path('app/Domains/Post/Policies/PostPolicy.php'));
    }

    public function test_it_generates_spatie_data_objects_when_requested(): void
    {
        File::ensureDirectoryExists(base_path('scaffolding'));
        File::put(base_path('scaffolding/post.yml'), <<<'YAML'
fields:
  title:
    type: string
    nullable: false
YAML
);

        $this->artisan('make:module Post --validation=spatie --no-views --no-js --no-routes --force')
            ->assertExitCode(0);

        $this->assertFileExists(base_path('app/Domains/Post/DTOs/StorePostData.php'));
        $this->assertFileExists(base_path('app/Domains/Post/DTOs/UpdatePostData.php'));
        $this->assertFileDoesNotExist(base_path('app/Domains/Post/Requests/StorePostRequest.php'));
        $this->assertFileDoesNotExist(base_path('resources/views/modules/post/index.blade.php'));
        $this->assertFileDoesNotExist(base_path('resources/js/project/posts/index.js'));

        $routes = File::get(base_path('routes/web.php'));
        $this->assertStringNotContainsString("Route::get('posts',", $routes);
      }

      public function test_route_generation_is_idempotent(): void
      {
        File::ensureDirectoryExists(base_path('scaffolding'));
        File::put(base_path('scaffolding/post.yml'), <<<'YAML'
    fields:
      title:
      type: string
      nullable: false
    YAML
    );

        $this->artisan('make:module Post --force')
          ->assertExitCode(0);

        $this->artisan('make:module Post --force')
          ->assertExitCode(0);

        $routes = File::get(base_path('routes/web.php'));
        $this->assertSame(1, substr_count($routes, "->name('posts.index')"));
      }

      public function test_query_can_use_wildcard_when_explicit_select_is_disabled(): void
      {
        config()->set('scaffolding.query.enforce_explicit_select', false);

        File::ensureDirectoryExists(base_path('scaffolding'));
        File::put(base_path('scaffolding/post.yml'), <<<'YAML'
    fields:
      title:
      type: string
      nullable: false
    YAML
    );

        $this->artisan('make:module Post --no-views --no-js --no-routes --force')
          ->assertExitCode(0);

        $query = File::get(base_path('app/Domains/Post/Queries/PostQueries.php'));
        $this->assertStringContainsString('public function getAll(array $columns = [\'*\'])', $query);
    }
}
