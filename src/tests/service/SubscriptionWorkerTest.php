<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;

class SubscriptionWorkerTest extends Orchestra\Testbench\TestCase
{
    protected $newsletter;
    protected $subscription;
    protected $mailjet;

    use WithoutMiddleware;

    public function setUp()
    {
        parent::setUp();

        $this->mailjet = Mockery::mock('designpond\newsletter\Newsletter\Worker\MailjetServiceInterface');
        $this->app->instance('designpond\newsletter\Newsletter\Worker\MailjetServiceInterface', $this->mailjet);

        $this->withFactories(dirname(__DIR__).'/newsletter/factories');
    }

    public function tearDown()
    {
        Mockery::close();
    }

    protected function getPackageProviders($app)
    {
        return [
            designpond\newsletter\newsletterServiceProvider::class,
            Vinkla\Alert\AlertServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'test');
        $app['config']->set('database.connections.test', [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'dev',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8',
            'unix_socket' => '/Applications/MAMP/tmp/mysql/mysql.sock',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ]);
    }

    public function testSubscriptionWorker()
    {
        \DB::table('newsletters')->truncate();
        \DB::table('newsletter_users')->truncate();
        \DB::table('newsletter_subscriptions')->truncate();

        $newsletter   = factory(designpond\newsletter\Newsletter\Entities\Newsletter::class)->create(['list_id' => 1]);

        $worker = new \designpond\newsletter\Newsletter\Worker\SubscriptionWorker(
            $this->app->make('designpond\newsletter\Newsletter\Repo\NewsletterInterface'),
            $this->app->make('designpond\newsletter\Newsletter\Repo\NewsletterUserInterface'),
            $this->mailjet
        );

        $result = $worker->make('info@publications-droit.ch',$newsletter->id);

        $this->assertTrue($result->subscriptions->contains('id',$newsletter->id));

        \DB::table('newsletters')->truncate();
        \DB::table('newsletter_users')->truncate();
        \DB::table('newsletter_subscriptions')->truncate();
    }

    public function testSubscriberExist()
    {
        \DB::table('newsletters')->truncate();
        \DB::table('newsletter_users')->truncate();
        \DB::table('newsletter_subscriptions')->truncate();

        $worker = new \designpond\newsletter\Newsletter\Worker\SubscriptionWorker(
            $this->app->make('designpond\newsletter\Newsletter\Repo\NewsletterInterface'),
            $this->app->make('designpond\newsletter\Newsletter\Repo\NewsletterUserInterface'),
            $this->mailjet
        );

        $newsletter = factory(designpond\newsletter\Newsletter\Entities\Newsletter::class)->create(['list_id' => 1]);

        $subscriber = factory(designpond\newsletter\Newsletter\Entities\Newsletter_users::class)->create(['email' => 'info@publications-droit.ch']);
        $subscriber->subscriptions()->attach(1);

        $result = $worker->activate('info@publications-droit.ch',$newsletter->id);

        $this->assertFalse($result);

        \DB::table('newsletters')->truncate();
        \DB::table('newsletter_users')->truncate();
        \DB::table('newsletter_subscriptions')->truncate();
    }

    public function testUnsubscribe()
    {
        \DB::table('newsletters')->truncate();
        \DB::table('newsletter_users')->truncate();
        \DB::table('newsletter_subscriptions')->truncate();

        $worker = new \designpond\newsletter\Newsletter\Worker\SubscriptionWorker(
            App::make('\designpond\newsletter\Newsletter\Repo\NewsletterInterface'),
            App::make('\designpond\newsletter\Newsletter\Repo\NewsletterUserInterface'),
            $this->mailjet
        );

        $newsletter   = factory(designpond\newsletter\Newsletter\Entities\Newsletter::class)->create(['list_id' => 1]);
        $subscription = factory(designpond\newsletter\Newsletter\Entities\Newsletter_users::class)->create(['email' => 'info@test.org']);

        $subscription->subscriptions()->attach($newsletter->id);

        $this->mailjet->shouldReceive('setList')->once();
        $this->mailjet->shouldReceive('removeContact')->once()->andReturn(true);

        $this->assertTrue($subscription->subscriptions->contains('id',$newsletter->id));

        $worker->unsubscribe($subscription,[$newsletter->id]);

        $subscription->fresh();
        $subscription->load('subscriptions');
        $subscription->fresh();

        $results = !$subscription->subscriptions->isEmpty() ? $subscription->pluck('subscriptions')->flatten(1) : collect([]);

        $this->assertTrue($results->isEmpty());

        $this->dontSeeInDatabase('newsletter_users', ['id'  => $subscription->id]);
    }

    /**
     *
     * @return void
     */
    public function testUpdateSubscriptions()
    {
        \DB::table('newsletters')->truncate();
        \DB::table('newsletter_users')->truncate();
        \DB::table('newsletter_subscriptions')->truncate();

        /******************************/
        $subscriber = factory(designpond\newsletter\Newsletter\Entities\Newsletter_users::class)->create(['email' => 'info@test.org']);

        $newsletter1   = factory(designpond\newsletter\Newsletter\Entities\Newsletter::class)->create(['list_id' => 1]);
        $newsletter2   = factory(designpond\newsletter\Newsletter\Entities\Newsletter::class)->create(['list_id' => 1]);
        $newsletter3   = factory(designpond\newsletter\Newsletter\Entities\Newsletter::class)->create(['list_id' => 1]);
        $newsletter4   = factory(designpond\newsletter\Newsletter\Entities\Newsletter::class)->create(['list_id' => 1]);
        $newsletter5   = factory(designpond\newsletter\Newsletter\Entities\Newsletter::class)->create(['list_id' => 1]);

        $has = [$newsletter1->id, $newsletter2->id, $newsletter3->id];
        $subscriber->subscriptions()->attach($has);

        /******************************/

        $worker = new \designpond\newsletter\Newsletter\Worker\SubscriptionWorker(
            App::make('\designpond\newsletter\Newsletter\Repo\NewsletterInterface'),
            App::make('\designpond\newsletter\Newsletter\Repo\NewsletterUserInterface'),
            $this->mailjet
        );

        $this->app->instance('designpond\newsletter\Newsletter\Worker\SubscriptionWorkerInterface', $worker);

        $this->mailjet->shouldReceive('setList')->times(4);
        $this->mailjet->shouldReceive('removeContact')->times(2)->andReturn(true);
        $this->mailjet->shouldReceive('subscribeEmailToList')->times(2)->andReturn(true);

        $new      = [$newsletter1->id, $newsletter4->id, $newsletter5->id];

        $response = $this->call('PUT', 'build/subscriber/'.$subscriber->id, [
            'id' => $subscriber->id ,
            'email' => $subscriber->email,
            'newsletter_id' => $new,
            'activation' => 1
        ]);

        $this->assertRedirectedTo('build/subscriber/'.$subscriber->id);

        $content = $this->followRedirects()->response->getOriginalContent();
        $content = $content->getData();

        $subscriber = $content['subscriber'];

        $effective = $subscriber->subscriptions->pluck('id')->all();

        $this->assertSame($new,$effective);
    }

    /**
     *
     * @return void
     */
    public function testUpdateRemoveAllSubscriptions()
    {
        \DB::table('newsletters')->truncate();
        \DB::table('newsletter_users')->truncate();
        \DB::table('newsletter_subscriptions')->truncate();
        /******************************/
        $subscriber = factory(designpond\newsletter\Newsletter\Entities\Newsletter_users::class)->create([
            'email' => 'info@test.org'
        ]);

        $newsletter1   = factory(designpond\newsletter\Newsletter\Entities\Newsletter::class)->create(['list_id' => 1]);
        $newsletter2   = factory(designpond\newsletter\Newsletter\Entities\Newsletter::class)->create(['list_id' => 1]);
        $newsletter3   = factory(designpond\newsletter\Newsletter\Entities\Newsletter::class)->create(['list_id' => 1]);

        $has = [$newsletter1->id, $newsletter2->id, $newsletter3->id];
        $subscriber->subscriptions()->attach($has);

        /******************************/

        $worker = new \designpond\newsletter\Newsletter\Worker\SubscriptionWorker(
            App::make('\designpond\newsletter\Newsletter\Repo\NewsletterInterface'),
            App::make('\designpond\newsletter\Newsletter\Repo\NewsletterUserInterface'),
            $this->mailjet
        );

        $this->app->instance('designpond\newsletter\Newsletter\Worker\SubscriptionWorkerInterface', $worker);

        $this->mailjet->shouldReceive('setList')->times(3);
        $this->mailjet->shouldReceive('removeContact')->times(3)->andReturn(true);

        $new      = [];
        $response = $this->call('PUT', 'build/subscriber/'.$subscriber->id, ['id' => $subscriber->id , 'email' => $subscriber->email, 'newsletter_id' => $new, 'activation' => 1]);

        $this->assertRedirectedTo('build/subscriber');

        $subscriber->fresh();

        $effective = $subscriber->subscriptions->pluck('id')->all();

        $this->assertSame($new,$effective);
    }
}
