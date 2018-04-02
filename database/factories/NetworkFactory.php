<?php

use Faker\Generator as Faker;

$factory->define(App\Network::class, function (Faker $faker) {
    return [
        'network_provider' => $faker->company,
        'user_location' => $faker->city,
        'ping_time' => $faker->numberBetween(0, 200),
        'download_speed' => $faker->randomFloat(2, 0.01, 100),
        'upload_speed' => $faker->randomFloat(2, 0.01, 100),
        'user_id' => factory(App\User::class)->create()->id
    ];
});
