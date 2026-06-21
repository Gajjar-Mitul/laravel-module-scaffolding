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
    File::delete(base_path('database/factories/PostFactory.php'));
    File::delete(base_path('app/Events/PostCreated.php'));
    File::delete(base_path('app/Jobs/ProcessPost.php'));
    foreach (glob(base_path('app/Jobs/Process*Post.php')) ?: [] as $file) {
      File::delete($file);
    }
    foreach (glob(base_path('app/Jobs/Async*PostTask.php')) ?: [] as $file) {
      File::delete($file);
    }
    File::delete(base_path('app/Console/Commands/PostSyncCommand.php'));
    foreach (glob(base_path('database/migrations/*_create_jobs_table.php')) ?: [] as $file) {
      File::delete($file);
    }
    foreach (glob(base_path('database/migrations/*_create_failed_jobs_table.php')) ?: [] as $file) {
      File::delete($file);
    }
    foreach (glob(base_path('database/migrations/*_create_async_jobs_table.php')) ?: [] as $file) {
      File::delete($file);
    }
    foreach (glob(base_path('database/migrations/*_create_async_failed_jobs_table.php')) ?: [] as $file) {
      File::delete($file);
    }
    foreach (glob(base_path('database/migrations/*_create_async_only_jobs_table.php')) ?: [] as $file) {
      File::delete($file);
    }
    foreach (glob(base_path('database/migrations/*_create_async_only_failed_jobs_table.php')) ?: [] as $file) {
      File::delete($file);
    }
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

      public function test_it_respects_custom_naming_templates_and_optional_artifacts(): void
      {
        config()->set('scaffolding.artifacts.controller.class_template', '{Module}HttpController');
        config()->set('scaffolding.artifacts.query.class_template', '{Module}ReadModel');
        config()->set('scaffolding.artifacts.query.path_template', 'app/Domains/{Module}/Queries/{Class}.php');
        config()->set('scaffolding.artifacts.service.class_template', '{Module}Manager');
        config()->set('scaffolding.artifacts.service.path_template', 'app/Domains/{Module}/Services/{Class}.php');
        config()->set('scaffolding.artifacts.validation.form_request.store_class_template', 'Create{Module}Request');
        config()->set('scaffolding.artifacts.validation.form_request.update_class_template', 'Edit{Module}Request');
        config()->set('scaffolding.artifacts.factory.enabled', true);
        config()->set('scaffolding.artifacts.event.enabled', true);
        config()->set('scaffolding.artifacts.job.enabled', true);
        config()->set('scaffolding.artifacts.command.enabled', true);

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

        $this->assertFileExists(base_path('app/Domains/Post/Controllers/PostHttpController.php'));
        $this->assertFileExists(base_path('app/Domains/Post/Queries/PostReadModel.php'));
        $this->assertFileExists(base_path('app/Domains/Post/Services/PostManager.php'));
        $this->assertFileExists(base_path('app/Domains/Post/Requests/CreatePostRequest.php'));
        $this->assertFileExists(base_path('app/Domains/Post/Requests/EditPostRequest.php'));

        $this->assertFileExists(base_path('database/factories/PostFactory.php'));
        $this->assertFileExists(base_path('app/Events/PostCreated.php'));
        $this->assertFileExists(base_path('app/Jobs/ProcessPost.php'));
        $this->assertFileExists(base_path('app/Console/Commands/PostSyncCommand.php'));

        $controller = File::get(base_path('app/Domains/Post/Controllers/PostHttpController.php'));
        $this->assertStringContainsString('class PostHttpController extends Controller', $controller);
        $this->assertStringContainsString('private readonly PostReadModel $queries', $controller);
        $this->assertStringContainsString('private readonly PostManager $service', $controller);
        $this->assertStringContainsString('public function store(CreatePostRequest $request): RedirectResponse', $controller);
        $this->assertStringContainsString('public function update(EditPostRequest $request, Post $post): RedirectResponse', $controller);

        $query = File::get(base_path('app/Domains/Post/Queries/PostReadModel.php'));
        $this->assertStringContainsString('class PostReadModel', $query);

        $service = File::get(base_path('app/Domains/Post/Services/PostManager.php'));
        $this->assertStringContainsString('class PostManager', $service);
      }

      public function test_queue_strategy_both_generates_job_and_queue_infrastructure(): void
      {
        config()->set('scaffolding.artifacts.job.enabled', false);
        config()->set('scaffolding.artifacts.queue.enabled', true);
        config()->set('scaffolding.artifacts.queue.strategy', 'both');

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

        $this->assertFileExists(base_path('app/Jobs/ProcessPost.php'));
        $this->assertNotEmpty(glob(base_path('database/migrations/*_create_jobs_table.php')) ?: []);
        $this->assertNotEmpty(glob(base_path('database/migrations/*_create_failed_jobs_table.php')) ?: []);
      }

      public function test_it_respects_route_name_uri_and_parameter_templates(): void
      {
        config()->set('scaffolding.routing.name_template', 'admin_{modules}');
        config()->set('scaffolding.routing.uri_template', 'cms/{modules}');
        config()->set('scaffolding.routing.parameter_template', '{module}_item');

        File::ensureDirectoryExists(base_path('scaffolding'));
        File::put(base_path('scaffolding/post.yml'), <<<'YAML'
    fields:
      title:
      type: string
      nullable: false
    YAML
    );

        $this->artisan('make:module Post --no-views --no-js --force')
          ->assertExitCode(0);

        $routes = File::get(base_path('routes/web.php'));
        $this->assertStringContainsString("Route::get('cms/posts',", $routes);
        $this->assertStringContainsString("->name('admin_posts.index')", $routes);
        $this->assertStringContainsString("Route::get('cms/posts/{post_item}',", $routes);

        $controller = File::get(base_path('app/Domains/Post/Controllers/PostController.php'));
        $this->assertStringContainsString('public function show(Post $post_item): View', $controller);
        $this->assertStringContainsString('public function update(UpdatePostRequest $request, Post $post_item): RedirectResponse', $controller);
      }

      public function test_queue_supports_custom_table_names_and_action_job_templates(): void
      {
        config()->set('scaffolding.artifacts.job.enabled', false);
        config()->set('scaffolding.artifacts.queue.enabled', true);
        config()->set('scaffolding.artifacts.queue.strategy', 'both');
        config()->set('scaffolding.artifacts.queue.job.actions', ['create', 'update']);
        config()->set('scaffolding.artifacts.queue.job.class_template', 'Process{Action}{Module}');
        config()->set('scaffolding.artifacts.queue.infrastructure.jobs_table_migration.name_template', 'create_async_jobs_table');
        config()->set('scaffolding.artifacts.queue.infrastructure.jobs_table_migration.table_name', 'async_jobs');
        config()->set('scaffolding.artifacts.queue.infrastructure.failed_jobs_table_migration.name_template', 'create_async_failed_jobs_table');
        config()->set('scaffolding.artifacts.queue.infrastructure.failed_jobs_table_migration.table_name', 'async_failed_jobs');

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

        $this->assertFileExists(base_path('app/Jobs/ProcessCreatePost.php'));
        $this->assertFileExists(base_path('app/Jobs/ProcessUpdatePost.php'));

        $jobCreate = File::get(base_path('app/Jobs/ProcessCreatePost.php'));
        $this->assertStringContainsString('class ProcessCreatePost implements ShouldQueue', $jobCreate);

        $jobsMigrations = glob(base_path('database/migrations/*_create_async_jobs_table.php')) ?: [];
        $failedMigrations = glob(base_path('database/migrations/*_create_async_failed_jobs_table.php')) ?: [];

        $this->assertNotEmpty($jobsMigrations);
        $this->assertNotEmpty($failedMigrations);

        $jobsMigration = File::get((string) $jobsMigrations[0]);
        $failedMigration = File::get((string) $failedMigrations[0]);

        $this->assertStringContainsString("Schema::create('async_jobs'", $jobsMigration);
        $this->assertStringContainsString("Schema::dropIfExists('async_jobs'", $jobsMigration);
        $this->assertStringContainsString("Schema::create('async_failed_jobs'", $failedMigration);
        $this->assertStringContainsString("Schema::dropIfExists('async_failed_jobs'", $failedMigration);
      }

      public function test_queue_strategy_infrastructure_does_not_generate_job_classes(): void
      {
        config()->set('scaffolding.artifacts.job.enabled', false);
        config()->set('scaffolding.artifacts.queue.enabled', true);
        config()->set('scaffolding.artifacts.queue.strategy', 'infrastructure');
        config()->set('scaffolding.artifacts.queue.job.actions', ['create', 'update']);
        config()->set('scaffolding.artifacts.queue.infrastructure.jobs_table_migration.name_template', 'create_async_only_jobs_table');
        config()->set('scaffolding.artifacts.queue.infrastructure.failed_jobs_table_migration.name_template', 'create_async_only_failed_jobs_table');

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

        $this->assertFileDoesNotExist(base_path('app/Jobs/ProcessPost.php'));
        $this->assertFileDoesNotExist(base_path('app/Jobs/ProcessCreatePost.php'));
        $this->assertFileDoesNotExist(base_path('app/Jobs/ProcessUpdatePost.php'));
        $this->assertNotEmpty(glob(base_path('database/migrations/*_create_async_only_jobs_table.php')) ?: []);
        $this->assertNotEmpty(glob(base_path('database/migrations/*_create_async_only_failed_jobs_table.php')) ?: []);
      }

      public function test_route_generation_is_idempotent_with_custom_route_name_template(): void
      {
        config()->set('scaffolding.routing.name_template', 'office_{modules}');
        config()->set('scaffolding.routing.uri_template', 'office/{modules}');

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
        $this->assertSame(1, substr_count($routes, "->name('office_posts.index')"));
        $this->assertSame(1, substr_count($routes, "Route::get('office/posts',"));
      }

      public function test_queue_job_actions_use_custom_template_and_namespace_path(): void
      {
        config()->set('scaffolding.artifacts.job.enabled', false);
        config()->set('scaffolding.artifacts.queue.enabled', true);
        config()->set('scaffolding.artifacts.queue.strategy', 'job_only');
        config()->set('scaffolding.artifacts.queue.job.actions', ['sync', 'archive']);
        config()->set('scaffolding.artifacts.queue.job.class_template', 'Async{Action}{Module}Task');
        config()->set('scaffolding.artifacts.queue.job.namespace_template', 'App\\Async\\Jobs');
        config()->set('scaffolding.artifacts.queue.job.path_template', 'app/Jobs/{Class}.php');

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

        $this->assertFileExists(base_path('app/Jobs/AsyncSyncPostTask.php'));
        $this->assertFileExists(base_path('app/Jobs/AsyncArchivePostTask.php'));

        $syncJob = File::get(base_path('app/Jobs/AsyncSyncPostTask.php'));
        $this->assertStringContainsString('namespace App\\Async\\Jobs;', $syncJob);
        $this->assertStringContainsString('class AsyncSyncPostTask implements ShouldQueue', $syncJob);

        $this->assertEmpty(glob(base_path('database/migrations/*_create_jobs_table.php')) ?: []);
        $this->assertEmpty(glob(base_path('database/migrations/*_create_failed_jobs_table.php')) ?: []);
      }

      public function test_invalid_queue_strategy_fails_fast_with_clear_error(): void
      {
        config()->set('scaffolding.artifacts.queue.enabled', true);
        config()->set('scaffolding.artifacts.queue.strategy', 'invalid_mode');

        File::ensureDirectoryExists(base_path('scaffolding'));
        File::put(base_path('scaffolding/post.yml'), <<<'YAML'
    fields:
      title:
      type: string
      nullable: false
    YAML
    );

        $this->artisan('make:module Post --no-views --no-js --no-routes --force')
          ->expectsOutputToContain('Invalid queue strategy [invalid_mode]. Allowed values: job_only, infrastructure, both')
          ->assertExitCode(1);
      }

      public function test_valid_queue_strategy_remains_accepted(): void
      {
        config()->set('scaffolding.artifacts.queue.enabled', true);
        config()->set('scaffolding.artifacts.queue.strategy', 'job_only');
        config()->set('scaffolding.artifacts.queue.job.actions', ['sync']);

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

        $this->assertFileExists(base_path('app/Jobs/ProcessSyncPost.php'));
      }
}
