<?php

use Illuminate\Database\Seeder;
use App\{Tag, Admin};

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = Admin::first();

        Tag::create(['type' => 'technique', 'name' => 'staccato', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'mood', 'name' => 'calm', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'mood', 'name' => 'agitaded', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'level', 'name' => 'elementary', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'level', 'name' => 'beginner', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'level', 'name' => 'intermediate', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'level', 'name' => 'advanced', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'sublevel', 'name' => 'early beginner', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'sublevel', 'name' => 'late beginner', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'sublevel', 'name' => 'early intermediate', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'sublevel', 'name' => 'late intermediate', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'period', 'name' => 'baroque', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'period', 'name' => 'classical', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'period', 'name' => 'romantic', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'period', 'name' => 'modern', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'period', 'name' => 'impressionist', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'length', 'name' => 'short', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'length', 'name' => 'medium', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'length', 'name' => 'long', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'genre', 'name' => 'transcription', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'ranking', 'name' => 'ABRSM 1', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'ranking', 'name' => 'ABRSM 2', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'ranking', 'name' => 'ABRSM 3', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'ranking', 'name' => 'RCM 1', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'ranking', 'name' => 'RCM 2', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'ranking', 'name' => 'RCM 3', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'season', 'name' => 'halloween', 'creator_id' => $admin->id]);
        Tag::create(['type' => 'season', 'name' => 'christmas', 'creator_id' => $admin->id]);
    }
}
