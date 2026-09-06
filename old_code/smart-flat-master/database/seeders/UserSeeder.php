<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('users')->insert([
            'name' => 'Ivan',
            'email' => 'vanzzosolutions@gmail.com',
            'password' => bcrypt('>G9U*8Wn6IG~nf_%'),
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        \DB::table('users')->insert([
            'name' => 'Inna',
            'email' => 'inesskalucevnikova@gmail.com',
            'password' => bcrypt('L2*W{rSk5Xy]5=TW'),
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
