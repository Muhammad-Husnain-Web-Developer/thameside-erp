<?php

namespace App\Providers;

use App\Modules\Core\Listeners\RecordUserLogin;
use App\Modules\Core\Models\User;
use App\Modules\Core\Policies\UserPolicy;
use App\Modules\Sales\Events\InvoicePosted;
use App\Modules\Sales\Listeners\PostInvoiceJournal;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Models live inside app/Modules, so policies are registered explicitly.
        Gate::policy(User::class, UserPolicy::class);

        Event::listen(Login::class, RecordUserLogin::class);

        // Posting engine runs off domain events, not inside sales controllers.
        Event::listen(InvoicePosted::class, PostInvoiceJournal::class);

        // Surface typo'd or unfillable attributes early instead of silently dropping them.
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }
}
