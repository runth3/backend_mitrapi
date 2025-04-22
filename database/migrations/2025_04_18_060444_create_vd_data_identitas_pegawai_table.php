<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVdDataIdentitasPegawaiTable extends Migration
{
    public function up()
    {
        // Untuk Simpeg (DataPegawaiSimpeg, DataPegawaiAbsen)
        Schema::create('simpeg_vd_data_identitas_pegawai', function (Blueprint $table) {
            $table->string('id_pegawai')->primary();
            $table->string('nip', 18)->unique();
            $table->string('nama_lengkap');
            $table->string('gelar')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('jenis_kelamin', 1)->nullable();
            $table->string('id_pangkat')->nullable();
            $table->string('id_instansi')->nullable();
            $table->string('id_unit_kerja')->nullable();
            $table->string('id_sub_unit_kerja')->nullable();
            $table->string('id_jabatan')->nullable();
            $table->date('tmt_jabatan')->nullable();
            $table->string('id_eselon')->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_telp')->nullable();
            $table->string('nip9')->nullable();
            $table->string('title')->nullable();
            $table->string('id_agama')->nullable();
            $table->timestamps();
        });

        // Untuk Ekinerja (DataPegawaiEkinerja)
        Schema::create('ekin_vd_data_identitas_pegawai', function (Blueprint $table) {
            $table->string('id_pegawai')->primary();
            $table->string('nip', 18)->unique();
            $table->string('nama_lengkap');
            $table->string('gelar')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('jenis_kelamin', 1)->nullable();
            $table->string('id_pangkat')->nullable();
            $table->string('id_instansi')->nullable();
            $table->string('id_unit_kerja')->nullable();
            $table->string('id_sub_unit_kerja')->nullable();
            $table->string('id_jabatan')->nullable();
            $table->date('tmt_jabatan')->nullable();
            $table->string('id_eselon')->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_telp')->nullable();
            $table->string('nip9')->nullable();
            $table->string('title')->nullable();
            $table->string('id_agama')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('simpeg_vd_data_identitas_pegawai');
        Schema::dropIfExists('ekin_vd_data_identitas_pegawai');
    }
}