<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveShopDisplayMockups extends Migration
{
    // SQLite rebuilds tables for column removal; its FK toggle must run outside a transaction.
    public $withinTransaction = false;

    public function up()
    {
        $connection = Schema::getConnection();
        $restoreForeignKeys = $connection->getDriverName() === 'sqlite'
            && (bool) $connection->selectOne('PRAGMA foreign_keys')->foreign_keys;
        if ($restoreForeignKeys) Schema::disableForeignKeyConstraints();
        try {
            foreach (['merchandise_products', 'e_books', 'e_scores'] as $table) {
                if (Schema::hasColumn($table, 'display_mockup_path')) {
                    Schema::table($table, fn (Blueprint $table) => $table->dropColumn('display_mockup_path'));
                }
            }
        } finally {
            if ($restoreForeignKeys) Schema::enableForeignKeyConstraints();
        }
    }

    public function down()
    {
        foreach (['merchandise_products', 'e_books', 'e_scores'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->string('display_mockup_path')->nullable());
        }
    }
}
