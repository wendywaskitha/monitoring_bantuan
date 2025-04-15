<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;
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
        // Log semua query SQL ke laravel.log
        DB::listen(function ($query) {
            // Format query dan bindings agar mudah dibaca
            $sql = $query->sql;
            foreach ($query->bindings as $binding) {
                $value = is_numeric($binding) ? $binding : "'".$binding."'";
                $sql = preg_replace('/\?/', $value, $sql, 1);
            }
            // Log query yang sudah diformat
            Log::channel('stderr')->info('SQL Query:', ['sql' => $sql, 'time' => $query->time]);

            // Opsional: Log stack trace untuk melihat asal query
            // Log::channel('stderr')->info('SQL Query Stack Trace:', ['trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)]); // Batasi kedalaman trace
        });

        // Share $errors ke semua view (terutama untuk Livewire/Filament Page)
        View::share('errors', Session::get('errors') ?: new \Illuminate\Support\ViewErrorBag);
    }

}
