<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\AssetImage;
use App\Models\Category;
use App\Models\Department;
use App\Models\JobProfile;
use App\Models\Loan;
use App\Models\Location;
use App\Models\Maintenance;
use App\Models\Procurement;
use App\Models\Recommendation;
use App\Models\User;
use App\Observers\GeneralObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\{Event, Artisan, Broadcast, URL};
use Symfony\Component\Console\Output\ConsoleOutput;

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
        if($this->app->environment('production')) {
            URL::forceScheme('https');
        }
        Category::observe(GeneralObserver::class);
        Department::observe(GeneralObserver::class);
        Location::observe(GeneralObserver::class);
        JobProfile::observe(GeneralObserver::class);
        User::observe(GeneralObserver::class);
        Asset::observe(GeneralObserver::class);
        AssetImage::observe(GeneralObserver::class);
        Loan::observe(GeneralObserver::class);
        Maintenance::observe(GeneralObserver::class);
        Procurement::observe(GeneralObserver::class);
        Recommendation::observe(GeneralObserver::class);
        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            $riskyCommands = [
                'migrate:fresh',
                'migrate:refresh',
                'migrate:reset',
                'db:wipe'
            ];
            if (in_array($event->command, $riskyCommands)) {
                $output = new ConsoleOutput();
                $output->writeln("<comment>Autosave: Melakukan backup database sebelum menjalankan perintah destruktif...</comment>");
                Artisan::call('backup:run --only-db --disable-notifications');
                $output->writeln("<info>Backup selesai! Melanjutkan perintah asli...</info>");
            }
        });
        Broadcast::routes([
            'middleware' => ['auth:sanctum'],
            'prefix' => 'api',
        ]);
        require base_path('routes/channels.php');
    }
}
