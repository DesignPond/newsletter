<?php

namespace designpond\newsletter;

use Illuminate\Support\ServiceProvider;

class newsletterServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        if (!$this->app->routesAreCached()) {
            require __DIR__.'/routes.php';
        }

        $this->app->make('designpond\newsletter\Http\Controllers\Frontend\NewsletterController');
        $this->app->make('designpond\newsletter\Http\Controllers\Frontend\InscriptionController');
        
        $this->app->make('designpond\newsletter\Http\Controllers\Backend\NewsletterController');
        $this->app->make('designpond\newsletter\Http\Controllers\Backend\CampagneController');
        $this->app->make('designpond\newsletter\Http\Controllers\Backend\ContentController');
        $this->app->make('designpond\newsletter\Http\Controllers\Backend\SendController');
        $this->app->make('designpond\newsletter\Http\Controllers\Backend\SubscriberController');
        $this->app->make('designpond\newsletter\Http\Controllers\Backend\ImportController');
        $this->app->make('designpond\newsletter\Http\Controllers\Backend\ListController');
        $this->app->make('designpond\newsletter\Http\Controllers\Backend\EmailController');
        $this->app->make('designpond\newsletter\Http\Controllers\Backend\ClipboardController');

        $this->publishes([
            __DIR__.'/database/migrations/' => database_path('migrations')
        ], 'migrations');

        $this->publishes([
            __DIR__ . '/database/seeds' => base_path('database/seeds')
        ], 'seeds');

        $this->publishes([
            __DIR__.'/config/newsletter.php' => config_path('newsletter.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/assets' => public_path('newsletter'),
        ], 'assets');

        $this->loadViewsFrom(__DIR__.'/views', 'newsletter');

        $this->publishes([
            __DIR__.'/views' => resource_path('views/vendor/newsletter'),
        ], 'views');

        $this->publishes([
            __DIR__.'/views/Backend/layouts' => resource_path('views/vendor/newsletter/Backend/layouts'),
        ], 'layouts');

        $this->app['validator']->extend('emailconfirmed', function ($attribute, $value, $parameters)
        {
            $email = \DB::table('newsletter_users')->where('email','=',$value)->first();

            if($email)
            {
                return (!$email->activated_at ? false  : true);
            }

            return false;
        });

        $this->app['validator']->extend('mustBeEmpty', function ($attr, $value, $parameters)
        {
            if(!empty($attr)) return false;
            return true;
        });

    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/newsletter.php', 'newsletter'
        );

        // Custom Facade for CampagneWorker
        $this->app['newsworker'] = $this->app->share(function ()
        {
            return $this->app->make('\designpond\newsletter\Newsletter\Worker\CampagneInterface');
        });

        $this->registerMailjetService();
        $this->registerMailjetNewService();
        $this->registerNewsletterService();
        $this->registerContentService();
        $this->registerTypesService();
        $this->registerCampagneService();
        $this->registerCampagneWorkerService();
        $this->registerInscriptionService();
        $this->registerSubscribeService();
        $this->registerImportService();
        $this->registerUploadService();
        $this->registerListService();
        $this->registerEmailService();
        $this->registerClipboardService();
        $this->registerSubscribeWorkerService();
    }

    /**
     * Newsletter Content service
     */
    protected function registerMailjetService(){

        $this->app->bind('designpond\newsletter\Newsletter\Worker\MailjetInterface', function()
        {
            return new \designpond\newsletter\Newsletter\Worker\MailjetWorker(
                new \designpond\newsletter\Newsletter\Service\Mailjet(
                    config('newsletter.mailjet.api'),config('newsletter.mailjet.secret')
                )
            );
        });
    }

    /**
     * Newsletter Content service
     */
    protected function registerMailjetNewService(){

        $this->app->bind('designpond\newsletter\Newsletter\Worker\MailjetServiceInterface', function()
        {
            return new \designpond\newsletter\Newsletter\Worker\MailjetService(
                new \Mailjet\Client(config('newsletter.mailjet.api'),config('newsletter.mailjet.secret')),
                new \Mailjet\Resources()
            );
        });
    }

    /**
     * Newsletter Content service
     */
    protected function registerNewsletterService(){

        $this->app->bind('designpond\newsletter\Newsletter\Repo\NewsletterInterface', function()
        {
            return new \designpond\newsletter\Newsletter\Repo\NewsletterEloquent( new \designpond\newsletter\Newsletter\Entities\Newsletter );
        });
    }

    /**
     * Newsletter service
     */
    protected function registerContentService(){

        $this->app->bind('designpond\newsletter\Newsletter\Repo\NewsletterContentInterface', function()
        {
            return new \designpond\newsletter\Newsletter\Repo\NewsletterContentEloquent( new \designpond\newsletter\Newsletter\Entities\Newsletter_contents );
        });
    }

    /**
     * Newsletter Types service
     */
    protected function registerTypesService(){

        $this->app->bind('designpond\newsletter\Newsletter\Repo\NewsletterTypesInterface', function()
        {
            return new \designpond\newsletter\Newsletter\Repo\NewsletterTypesEloquent( new \designpond\newsletter\Newsletter\Entities\Newsletter_types );
        });
    }


    /**
     * Newsletter Types service
     */
    protected function registerCampagneService(){

        $this->app->bind('designpond\newsletter\Newsletter\Repo\NewsletterCampagneInterface', function()
        {
            return new \designpond\newsletter\Newsletter\Repo\NewsletterCampagneEloquent( new \designpond\newsletter\Newsletter\Entities\Newsletter_campagnes );
        });
    }

    /**
     * Newsletter Campagne worker
     */
    protected function registerCampagneWorkerService(){

        $this->app->bind('designpond\newsletter\Newsletter\Worker\CampagneInterface', function()
        {
            return new \designpond\newsletter\Newsletter\Worker\CampagneWorker(
                \App::make('designpond\newsletter\Newsletter\Repo\NewsletterContentInterface'),
                \App::make('designpond\newsletter\Newsletter\Repo\NewsletterCampagneInterface'),
                \App::make('designpond\newsletter\Newsletter\Repo\NewsletterInterface'),
                \App::make('designpond\newsletter\Newsletter\Repo\NewsletterUserInterface')
            );
        });
    }


    /**
     * Newsletter user abo service
     */
    protected function registerInscriptionService(){

        $this->app->bind('designpond\newsletter\Newsletter\Repo\NewsletterUserInterface', function()
        {
            return new \designpond\newsletter\Newsletter\Repo\NewsletterUserEloquent( new \designpond\newsletter\Newsletter\Entities\Newsletter_users );
        });
    }

    /**
     * Newsletter user abo service
     */
    protected function registerSubscribeService(){

        $this->app->bind('designpond\newsletter\Newsletter\Repo\NewsletterSubscriptionInterface', function()
        {
            return new \designpond\newsletter\Newsletter\Repo\NewsletterSubscriptionEloquent(
                new \designpond\newsletter\Newsletter\Entities\Newsletter_subscriptions
            );
        });
    }

    /**
     * Newsletter Import worker
     */
    protected function registerImportService(){

        $this->app->singleton('designpond\newsletter\Newsletter\Worker\ImportWorkerInterface', function()
        {
            return new \designpond\newsletter\Newsletter\Worker\ImportWorker(
                \App::make('designpond\newsletter\Newsletter\Worker\MailjetServiceInterface'),
                \App::make('designpond\newsletter\Newsletter\Repo\NewsletterUserInterface'),
                \App::make('designpond\newsletter\Newsletter\Repo\NewsletterInterface'),
                \App::make('Maatwebsite\Excel\Excel'),
                \App::make('designpond\newsletter\Newsletter\Repo\NewsletterCampagneInterface'),
                \App::make('designpond\newsletter\Newsletter\Worker\CampagneInterface'),
                \App::make('designpond\newsletter\Newsletter\Service\UploadInterface')
            );
        });
    }

    /**
     * Upload service
     */
    protected function registerUploadService(){

        $this->app->singleton('designpond\newsletter\Newsletter\Service\UploadInterface', function()
        {
            return new \designpond\newsletter\Newsletter\Service\UploadWorker();
        });
    }

    /**
     * Newsletter list
     */
    protected function registerListService(){

        $this->app->singleton('designpond\newsletter\Newsletter\Repo\NewsletterListInterface', function()
        {
            return new \designpond\newsletter\Newsletter\Repo\NewsletterListEloquent( new \designpond\newsletter\Newsletter\Entities\Newsletter_lists() );
        });
    }

    /**
     * Newsletter Email
     */
    protected function registerEmailService(){

        $this->app->singleton('designpond\newsletter\Newsletter\Repo\NewsletterEmailInterface', function()
        {
            return new \designpond\newsletter\Newsletter\Repo\NewsletterEmailEloquent( new \designpond\newsletter\Newsletter\Entities\Newsletter_emails() );
        });
    }

    /**
     * Newsletter clipboard
     */
    protected function registerClipboardService(){

        $this->app->singleton('designpond\newsletter\Newsletter\Repo\NewsletterClipboardInterface', function()
        {
            return new \designpond\newsletter\Newsletter\Repo\NewsletterClipboardEloquent( new \designpond\newsletter\Newsletter\Entities\Newsletter_clipboards() );
        });
    }

    /**
     * Newsletter subscriber service
     */
    protected function registerSubscribeWorkerService(){

        $this->app->bind('designpond\newsletter\Newsletter\Worker\SubscriptionWorkerInterface', function()
        {
            return new \designpond\newsletter\Newsletter\Worker\SubscriptionWorker(
                \App::make('designpond\newsletter\Newsletter\Repo\NewsletterInterface'),
                \App::make('designpond\newsletter\Newsletter\Repo\NewsletterUserInterface'),
                \App::make('designpond\newsletter\Newsletter\Worker\MailjetServiceInterface')
            );
        });
    }
}