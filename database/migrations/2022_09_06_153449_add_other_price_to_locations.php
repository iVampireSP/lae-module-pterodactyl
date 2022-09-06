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
            // cpu price decimal
            $table->decimal('cpu_price', 8, 2)->default(0.00)->after('price');
            $table->decimal(
                'memory_price',
                8,
                2
            )->default(0.00)->after('price');
            $table->decimal(
                'disk_price',
                8,
                2
            )->default(0.00)->after('price');
            $table->decimal('backup_price', 8, 2)->default(0.00)->after('price');
            $table->decimal('allocation_price', 8, 2)->default(0.00)->after('price');
            $table->decimal('database_price', 8, 2)->default(0.00)->after('price');
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
