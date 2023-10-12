<?php

use Illuminate\Database\Seeder;
use App\{EmailList, Subscription};

class MailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        EmailList::create([
            'name' => 'Free Pick',
            'description' => 'Weekly emails showcasing our free pick along with the list of tutorials and curiosities about the piece and composer.'
        ]);

        create(Subscription::class, [], 100);

        foreach (Subscription::all() as $subscriber) {
            $subscriber->joinAll();
        }
    }
}
