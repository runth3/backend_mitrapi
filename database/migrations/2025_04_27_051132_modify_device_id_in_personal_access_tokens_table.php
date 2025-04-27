<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Ubah device_id menjadi nullable
            $table->string('device_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Kembalikan ke non-nullable (hati-hati dengan data existing)
            $table->string('device_id')->nullable(false)->change();
        });
    }
};