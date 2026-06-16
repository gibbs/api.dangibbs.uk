<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Scramble::ignoreDefaultRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::configure()
            ->withOperationTransformers(function (Operation $operation, RouteInfo $routeInfo) {
                $uri = $routeInfo->route->uri();
                $routeMiddleware = $routeInfo->route->gatherMiddleware();

                // Group tool routes
                if (str_starts_with($uri, 'v1/tools')) {
                    $operation->tags = ['Tools'];
                }

                // Detect auth middleware on routes
                $hasAuthMiddleware = collect($routeMiddleware)->contains(
                    fn ($middleware) => Str::startsWith($middleware, 'auth:')
                );

                if (! $hasAuthMiddleware) {
                    $operation->security = [];
                }
            })
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer')
                );
            });

        Scramble::registerApi('v1', ['info' => ['version' => config('app.api_v1_version')]])
            ->routes(function (Route $route) {
                return Str::startsWith($route->uri, 'v1/');
            });
    }
}
