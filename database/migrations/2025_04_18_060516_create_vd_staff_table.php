<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVdStaffTable extends Migration
{
    public function up()
    {
        Schema::create('absen_vd_staff', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('lvl')->nullable();
            $table->string('name');
            $table->string('id_instansi')->nullable();
            $table->string('id_unit_kerja')->nullable();
            $table->string('id_pejabat')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('full_akses')->default(false);
            $table->string('pwd')->nullable();
            $table->string('auth_token')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('absen_vd_staff');
    }
}