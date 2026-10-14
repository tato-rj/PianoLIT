<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\{Admin, Subscription, User, Membership, Piece, Composer};
use App\Blog\Post;
use App\Quiz\{Quiz, QuizResult};
use App\Mail\Admin\AdminReport;

class SendAdminReport extends Command
{    
    protected $signature = 'pianolit:admin-report';
    
    protected $description = 'Send weekly reports to admins with relevant data such as new subscribers, pieces and more.';

    protected $duration = 7;

    protected $models = [
        \App\Subscription::class,
        \App\Piece::class,
        \App\Blog\Post::class,
        \App\Quiz\Quiz::class,
        \App\Quiz\QuizResult::class
        // \App\User::class,
        // \App\Membership::class,
        // \App\Composer::class,
    ];
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function generateReports()
    {
        $results = [];

        foreach ($this->models as $model) {
            $current = $model::report($this->duration, now());
            $previous = $model::report($this->duration, now()->subDays($this->duration));
            foreach ([$current, $previous] as $query) {
                if ($model === Subscription::class) \App\Activity\ExcludedAccounts::users($query, 'subscriptions.email');
                if ($model === QuizResult::class) \App\Activity\ExcludedAccounts::owned($query, 'quiz_results.user_id');
            }
            array_push($results, [
                'name' => class_str($model),
                'title' => class_str($model, $plural = true),
                'data' => $current->get(),
                'pastData' => $previous->get()
            ]);
        }

        return $results;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $reports = $this->generateReports();

        foreach (Admin::managers()->get() as $recipient) {
            \Mail::to($recipient->email)->queue(new AdminReport($reports, $recipient));
        }

        return $this->info('The report emails were sent successfully.');
    }
}
