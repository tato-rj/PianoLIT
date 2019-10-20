<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\{Admin, Subscription, User, Membership, Piece, Composer};
use App\Blog\Post;
use App\Quiz\{Quiz, QuizResult};
use App\Mail\Admin\AdminReport;

class SendAdminReport extends Command
{
    protected $recipients, $reports;
    
    protected $signature = 'pianolit:admin-report';
    
    protected $description = 'Send weekly reports to admins with relevant data such as new subscribers, pieces and more.';

    protected $duration = 7;

    protected $models = [
        \App\Subscription::class,
        // \App\User::class,
        // \App\Membership::class,
        \App\Piece::class,
        // \App\Composer::class,
        \App\Blog\Post::class,
        \App\Quiz\Quiz::class,
        \App\Quiz\QuizResult::class
    ];
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->recipients = Admin::where('email', 'arthurvillar@gmail.com')->get();//Admin::managers()->get();
        $this->reports = $this->generateReports();
    }

    public function generateReports()
    {
        $results = [];

        foreach ($this->models as $model) {
            array_push($results, [
                'name' => class_str($model),
                'title' => class_str($model, $plural = true),
                'data' => $model::report($this->duration)->get(),
                'duration' => $this->duration
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
        foreach ($this->recipients as $recipient) {
            \Mail::to($recipient->email)->send(new AdminReport($this->reports, $recipient));
        }
    }
}
