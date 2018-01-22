<?php

$factory = app('Illuminate\Database\Eloquent\Factory');

$factory->define(designpond\newsletter\Newsletter\Entities\Newsletter_users::class, function (Faker\Generator $faker) {
    return [
        'id'           => $faker->numberBetween(50,150),
        'email'        => $faker->email,
        'activation_token'        => '1234',
        'activated_at' => date('Y-m-d G:i:s')
    ];
});

$factory->define(designpond\newsletter\Newsletter\Entities\Newsletter_subscriptions::class, function (Faker\Generator $faker) {
    return [
        'user_id'       => 1,
        'newsletter_id' => 1
    ];
});

$factory->define(designpond\newsletter\Newsletter\Entities\Newsletter::class, function (Faker\Generator $faker) {
    return [
        'titre'        => 'Titre',
        'list_id'      => '1',
        'from_name'    => 'Nom',
        'from_email'   => 'cindy.leschaud@gmail.com',
        'return_email' => 'cindy.leschaud@gmail.com',
        'unsuscribe'   => 'unsubscribe',
        'preview'      => 'droit.local',
        'logos'        => 'logos.jpg',
        'header'       => 'header.jpg',
        'color'        => '#fff'
    ];
});

$factory->define(designpond\newsletter\Newsletter\Entities\Newsletter_campagnes::class, function (Faker\Generator $faker) {
    return [
        'sujet'         => 'Sujet',
        'auteurs'       => 'Cindy Leschaud',
        'status'        => 'Brouillon',
        'newsletter_id' => 1
    ];
});

$factory->defineAs(App\Droit\User\Entities\User::class, 'admin' ,function ($factory){
    return [
        'name'       => 'Cindy Leschaud',
        'email'      => 'cindy.leschaud@unine.ch',
        'password'   => bcrypt('cindy2')
    ];
});