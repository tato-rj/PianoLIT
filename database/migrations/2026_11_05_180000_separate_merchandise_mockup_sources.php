<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

class SeparateMerchandiseMockupSources extends Migration
{
    public function up()
    {
        Schema::table('merchandise_images', function (Blueprint $table) {
            $table->dropUnique('merchandise_images_product_id_checksum_unique');
            $table->unique(['product_id', 'source', 'checksum'], 'merchandise_images_source_checksum_unique');
        });
    }

    public function down()
    {
        if (DB::table('merchandise_images')->select('product_id', 'checksum')->groupBy('product_id', 'checksum')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new \RuntimeException('Export and reconcile identical imported/uploaded mockups before reverting this migration. No images were changed.');
        }
        Schema::table('merchandise_images', function (Blueprint $table) {
            $table->dropUnique('merchandise_images_source_checksum_unique');
            $table->unique(['product_id', 'checksum']);
        });
    }
}
