<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class CreateFreePicksTable extends Migration
{
    public function up()
    {
        Schema::create('free_picks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('piece_id');
            $table->dateTime('scheduled_at')->index();
            $table->dateTime('reserved_at')->nullable()->unique();
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('retired_at')->nullable();
            $table->timestamps();
            $table->index(['retired_at', 'scheduled_at', 'id']);
            $table->index(['piece_id', 'scheduled_at']);
            $table->foreign('piece_id')->references('id')->on('pieces')->onDelete('cascade');
        });

        $cutover = now('UTC');
        DB::table('pieces')->whereNotNull('highlighted_at')->orderBy('id')->chunkById(200, function ($pieces) use ($cutover) {
            $rows = [];
            foreach ($pieces as $piece) {
                $date = \Carbon\Carbon::parse($piece->highlighted_at, config('app.timezone'))->utc();
                $rows[] = ['piece_id' => $piece->id, 'scheduled_at' => $date,
                    'activated_at' => $date, 'retired_at' => $cutover,
                    'created_at' => $cutover, 'updated_at' => $cutover];
            }
            DB::table('free_picks')->insert($rows);
        });
        // Preserve the explicit old selection, even if historical dates are out of order.
        $current = DB::table('pieces')->where('is_free', true)->orderByDesc('highlighted_at')->orderByDesc('id')->first();
        if ($current && $current->highlighted_at) {
            $date = \Carbon\Carbon::parse($current->highlighted_at, config('app.timezone'))->utc();
            DB::table('free_picks')->where('piece_id', $current->id)->update([
                'scheduled_at' => $date->lessThanOrEqualTo($cutover) ? $date : $cutover,
                'retired_at' => null,
            ]);
        } elseif ($current) {
            DB::table('free_picks')->insert([
                'piece_id' => $current->id, 'scheduled_at' => $cutover, 'activated_at' => $cutover,
                'created_at' => $cutover, 'updated_at' => $cutover,
            ]);
        }
        // Avoid Laravel 8's SQLite table rebuild, which would cascade-delete related records.
        DB::statement('ALTER TABLE pieces DROP COLUMN is_free');
    }

    public function down()
    {
        $current = DB::table('free_picks')->whereNull('retired_at')->where('scheduled_at', '<=', now('UTC'))
            ->orderByDesc('scheduled_at')->orderByDesc('id')->value('piece_id');
        Schema::table('pieces', function (Blueprint $table) { $table->boolean('is_free')->default(false); });
        if ($current) DB::table('pieces')->where('id', $current)->update(['is_free' => true]);
        Schema::dropIfExists('free_picks');
    }
}
