<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class DropStudioPoliciesTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('studio_policies');
    }

    public function down()
    {
        // Restore the empty schema for a code rollback; deleted policies cannot be recovered.
        if (!Schema::hasTable('studio_policies')) {
            require_once __DIR__.'/2019_12_13_111419_create_studio_policies_table.php';
            (new CreateStudioPoliciesTable)->up();
        }
    }
}
