<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('locations', function (Blueprint $table) {
            //
            // cpu price double
            $table->double('cpu_price')->default(0.00)->after('price');
            $table->double('memory_price')->default(0.00)->after('price');
            $table->double('disk_price')->default(0.00)->after('price');
            $table->double('backup_price')->default(0.00)->after('price');
            $table->double('allocation_price')->default(0.00)->after('price');
            $table->double('database_price')->default(0.00)->after('price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('locations', function (Blueprint $table) {
            //
        });
    }
};
