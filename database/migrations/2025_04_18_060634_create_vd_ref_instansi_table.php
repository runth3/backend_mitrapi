<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVdRefInstansiTable extends Migration
{
    public function up()
    {
        Schema::create('simpeg_vd_ref_instansi', function (Blueprint $table) {
            $table->string('id_instansi')->primary();
            $table->string('nama_instansi');
            $table->text('alamat_instansi')->nullable();
            $table->string('kota')->nullable();
            $table->string('kodepos', 5)->nullable();
            $table->string('phone')->nullable();
            $table->string('fax')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('nama_bagian')->nullable();
            $table->string('id_instansi_induk')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('simpeg_vd_ref_instansi');
    }
}