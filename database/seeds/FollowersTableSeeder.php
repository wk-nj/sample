<?php

use Illuminate\Database\Seeder;
use App\Models\User;
class FollowersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::all();
        $user = $users->first();
        $user_id = $user->id;

        $followers = $users->slice(1);
        $follower_ids = $followers->pluck('id')->toArray();
        //所有用户关注1
        $user->follow($follower_ids);
        //1关注所有用户
        foreach ($followers as $follower) {
        	$follower->follow($user_id);
        }
    }
}
