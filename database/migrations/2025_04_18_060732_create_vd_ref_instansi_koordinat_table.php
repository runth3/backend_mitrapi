<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVdRefInstansiKoordinatTable extends Migration
{
    public function up()
    {
        Schema::create('absen_vd_ref_instansi_koordinat', function (Blueprint $table) {
            $table->string('id_ref_instansi_koordinat')->primary();
            $table->string('id_instansi');
            $table->string('koordinat')->nullable();
            $table->string('block_koordinat', 1)->nullable();
            $table->integer('jarak_koordinat')->nullable();
            $table->string('wajib_absen', 1)->nullable();
            $table->string('absen_shift', 1)->nullable();
            $table->timestamp('cre_on')->nullable();
            $table->string('cre_by')->nullable();
            $table->timestamp('upd_on')->nullable();
            $table->string('upd_by')->nullable();
            $table->integer('aktif')->default(0);
            $table->timestamps();

            $table->foreign('id_instansi')->references('id_instansi')->on('simpeg_vd_ref_instansi');
        });
    }

    public function down()
    {
        Schema::dropIfExists('absen_vd_ref_instansi_koordinat');
    }
}