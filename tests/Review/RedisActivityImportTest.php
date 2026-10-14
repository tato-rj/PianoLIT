<?php

namespace Tests\Review;

use Illuminate\Support\Facades\{DB, Redis};

class RedisActivityImportTest extends ReviewTestCase
{
    public function test_excluded_accounts_are_not_imported_or_counted_as_missing_during_verification()
    {
        $excluded = \Illuminate\Database\Eloquent\Model::withoutEvents(function () { return create(\App\User::class, ['email' => 'arthurvillar@gmail.com']); });
        $normal = \Illuminate\Database\Eloquent\Model::withoutEvents(function () { return create(\App\User::class); });
        $redis = $this->redis(); $prefix = config('database.redis.prefix');
        $privateKey = $prefix.'user:'.$excluded->id.':web'; $normalKey = $prefix.'user:'.$normal->id.':web';
        $redis->shouldReceive('scan')->with('0', 'MATCH', $prefix.'*', 'COUNT', 500)->once()->andReturn(['0', [$privateKey, $normalKey]]);
        $redis->shouldReceive('scan')->with('0', 'MATCH', $prefix.'user:*', 'COUNT', 500)->once()->andReturn(['0', [$privateKey, $normalKey]]);
        $redis->shouldReceive('type')->with($normalKey)->once()->andReturn('hash');
        $redis->shouldReceive('hscan')->with($normalKey, '0', 'COUNT', 500)->twice()->andReturn(['0', ['1577836800' => '{"url":"/normal"}']]);
        $this->artisan('activity:import-redis', ['--before' => (string) (time() - 60), '--sleep-ms' => 0, '--verify' => true])->assertExitCode(0);
        $this->assertDatabaseCount('activity_events', 1);
        $this->assertDatabaseHas('activity_events', ['user_id' => $normal->id]);
        $this->assertDatabaseMissing('activity_events', ['user_id' => $excluded->id]);
    }

    private function redis()
    {
        $redis = \Mockery::mock();
        Redis::shouldReceive('connection')->with('default')->andReturn($redis);
        return $redis;
    }

    public function test_import_resumes_empty_scan_pages_and_preserves_orphans_malformed_records_and_totals()
    {
        $redis = $this->redis(); $prefix = config('database.redis.prefix');
        $key = $prefix.'user:999999:web'; $daily = $prefix.'logs:daily:2020-01-01:web';
        $before = time() - 60;
        $json = json_encode(['url' => 'https://pianolit.com/?search=Bach', 'data' => ['search' => 'Bach']]);
        $redis->shouldReceive('scan')->once()->with('0', 'MATCH', $prefix.'*', 'COUNT', 2)->andReturn(['5', []]);
        $redis->shouldReceive('scan')->once()->with('5', 'MATCH', $prefix.'*', 'COUNT', 2)->andReturn(['0', [$key, $daily, $key]]);
        $redis->shouldReceive('type')->with($key)->twice()->andReturn('hash');
        $redis->shouldReceive('hscan')->with($key, '0', 'COUNT', 2)->twice()->andReturn(['8', ['1577836800' => $json, 'unknown-time' => 'broken json']]);
        $redis->shouldReceive('hscan')->with($key, '8', 'COUNT', 2)->twice()->andReturn(['0', ['1577836800' => $json, '1577836801' => '{invalid', (string) ($before + 5) => $json]]);
        $redis->shouldReceive('get')->once()->with($daily)->andReturn('12');
        $options = ['--before' => (string) $before, '--batch' => 2, '--sleep-ms' => 0];
        $this->artisan('activity:import-redis', $options + ['--max-batches' => 4])->assertExitCode(0);
        $this->assertDatabaseCount('activity_events', 2);
        $this->assertSame('8', DB::table('activity_imports')->value('hash_cursor'));
        $this->artisan('activity:import-redis', $options)->assertExitCode(0);
        $this->assertDatabaseCount('activity_events', 3);
        $this->assertDatabaseHas('activity_events', ['user_id' => 999999, 'type' => 'legacy_request', 'legacy_payload' => $json]);
        $this->assertDatabaseHas('activity_events', ['type' => 'legacy_unparsed', 'legacy_field' => 'unknown-time', 'occurred_at' => null]);
        $this->assertDatabaseHas('activity_legacy_daily', ['date' => '2020-01-01', 'platform' => 'web', 'total' => 12]);
        $this->assertTrue((bool) DB::table('activity_imports')->value('complete'));
        $this->artisan('activity:import-redis', $options)->assertExitCode(0);
        $this->assertDatabaseCount('activity_events', 3);
    }

    public function test_failed_import_batch_rolls_back_events_and_checkpoint_then_retries()
    {
        $redis = $this->redis(); $prefix = config('database.redis.prefix'); $key = $prefix.'user:42:webapp';
        $redis->shouldReceive('scan')->once()->andReturn(['0', [$key]]);
        $redis->shouldReceive('type')->once()->with($key)->andReturn('hash');
        $redis->shouldReceive('hscan')->twice()->andReturn(['0', ['1577836800' => '{"url":"/first"}', 'blocked' => 'raw']]);
        DB::statement("CREATE TRIGGER activity_import_fail BEFORE INSERT ON activity_events WHEN NEW.legacy_field = 'blocked' BEGIN SELECT RAISE(ABORT, 'simulated failure'); END");
        $options = ['--before' => (string) (time() - 60), '--sleep-ms' => 0];
        try {
            $this->artisan('activity:import-redis', $options)->run();
            $this->fail('The database failure must stop the importer.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertDatabaseCount('activity_events', 0);
            $this->assertSame('0', DB::table('activity_imports')->value('hash_cursor'));
            $this->assertSame($key, DB::table('activity_imports')->value('current_key'));
        }
        DB::statement('DROP TRIGGER activity_import_fail');
        $this->artisan('activity:import-redis', $options)->assertExitCode(0);
        $this->assertDatabaseCount('activity_events', 2);
    }

    public function test_verification_detects_changed_payloads_and_supports_an_empty_production_prefix()
    {
        config(['database.redis.prefix' => null]);
        $redis = $this->redis(); $key = 'user:42:web'; $json = '{"url":"/original"}';
        $redis->shouldReceive('scan')->with('0', 'MATCH', '*', 'COUNT', 500)->once()->andReturn(['0', [$key]]);
        $redis->shouldReceive('type')->once()->andReturn('hash');
        $redis->shouldReceive('hscan')->with($key, '0', 'COUNT', 500)->times(3)->andReturn(['0', ['1577836800' => $json]], ['0', ['1577836800' => $json]], ['0', ['1577836800' => '{"url":"/changed"}']]);
        $redis->shouldReceive('scan')->with('0', 'MATCH', 'user:*', 'COUNT', 500)->twice()->andReturn(['0', [$key]]);
        $options = ['--before' => (string) (time() - 60), '--sleep-ms' => 0, '--verify' => true];
        $this->artisan('activity:import-redis', $options)->assertExitCode(0);
        $this->artisan('activity:import-redis', $options)->assertExitCode(1);
        $this->assertDatabaseHas('activity_events', ['legacy_payload' => $json]);
    }

    public function test_binary_legacy_fields_and_payloads_are_preserved_and_verified()
    {
        $redis = $this->redis(); $key = config('database.redis.prefix').'user:42:app';
        $invalidDaily = config('database.redis.prefix').'logs:daily:2020-99-99:app';
        $redis->shouldReceive('scan')->twice()->andReturn(['0', [$key, $invalidDaily]]);
        $redis->shouldReceive('type')->once()->andReturn('hash');
        $redis->shouldReceive('get')->once()->with($invalidDaily)->andReturn('12');
        $redis->shouldReceive('hscan')->twice()->andReturn(['0', ["\xff" => "\xfe"]]);
        $this->artisan('activity:import-redis', ['--before' => (string) (time() - 60), '--sleep-ms' => 0, '--verify' => true])->assertExitCode(0);
        $event = DB::table('activity_events')->first();
        $this->assertSame("\xff", base64_decode($event->legacy_field));
        $this->assertSame("\xfe", base64_decode($event->legacy_payload));
        $this->assertSame('base64', json_decode($event->metadata, true)['field_encoding']);
        $this->assertDatabaseCount('activity_legacy_daily', 0);
    }
}
