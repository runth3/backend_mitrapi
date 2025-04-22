<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpdTable extends Migration
{
    public function up()
    {
        Schema::create('ekin_opd', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('nama');
            $table->string('ka_nip', 18)->nullable();
            $table->string('ka_nama')->nullable();
            $table->string('ka_jabatan')->nullable();
            $table->date('tmt_jabatan')->nullable();
            $table->string('idk')->nullable();
            $table->integer('jam_kerja')->nullable();
            $table->integer('menit_kerja')->nullable();
            $table->integer('menit_kerja_harian')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ekin_opd');
    }
}