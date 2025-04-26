<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeviceIdToRefreshTokensTable extends Migration
{
    public function up()
    {
        Schema::table('refresh_tokens', function (Blueprint $table) {
            $table->string('device_id')->nullable()->after('user_id'); // Tambahkan kolom device_id, nullable agar aman
        });
    }

    public function down()
    {
        Schema::table('refresh_tokens', function (Blueprint $table) {
            $table->dropColumn('device_id');
        });
    }
}