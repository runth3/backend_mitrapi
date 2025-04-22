<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKinerjaUserTable extends Migration
{
    public function up()
    {
        Schema::create('ekin_kinerja_user', function (Blueprint $table) {
            $table->string('UID')->primary();
            $table->string('title')->nullable();
            $table->string('nama');
            $table->string('gelar')->nullable();
            $table->string('NIP', 18)->nullable();
            $table->string('sts')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->string('uType')->nullable();
            $table->string('opd_id')->nullable();
            $table->string('opd_unit_id')->nullable();
            $table->string('opd_unit_sub_id')->nullable();
            $table->string('apvId')->nullable();
            $table->boolean('isOps')->default(false);
            $table->string('opd')->nullable();
            $table->string('jabatan_id')->nullable();
            $table->string('pangkat_id')->nullable();
            $table->string('simpeg_id')->nullable();
            $table->string('avatar')->nullable();
            $table->date('b_day')->nullable();
            $table->integer('tunjangan')->nullable();
            $table->boolean('isOpsTp')->default(false);
            $table->timestamp('last_updated')->nullable();
            $table->string('apv')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ekin_kinerja_user');
    }
}