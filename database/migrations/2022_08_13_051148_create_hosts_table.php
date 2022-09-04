<?php

use App\Models\Module\ProviderModule;
use App\Models\User;
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
        Schema::create('hosts', function (Blueprint $table) {
            $table->id();

            // $table->unsignedBigInteger('upstream_id')->index();


            // name
            $table->string('name')->index();


            $table->unsignedInteger('cpu_limit')->index();
            $table->unsignedInteger('memory')->index();
            $table->unsignedInteger('disk')->index();
            $table->unsignedInteger('databases')->index();
            $table->unsignedInteger('backups')->index();
            $table->unsignedInteger('allocations')->index();

            // $table->unsignedBigInteger('location_id')->index();
            // $table->unsignedBigInteger('node_id')->index();
            $table->unsignedBigInteger('server_id')->index();
            $table->unsignedBigInteger('egg_id')->index();


            $table->string('ip')->index()->nullable();
            $table->unsignedSmallInteger('port')->index()->nullable();

            // user_id
            $table->unsignedBigInteger('user_id')->index();
            $table->foreign('user_id')->references('id')->on('users');

            // host_id
            $table->unsignedBigInteger('host_id')->index();

            // price
            $table->double('price', 60, 8)->index();

            // config
            $table->json('configuration')->nullable();

            // status
            $table->string('status')->default('pending')->index();

            // soft delete
            $table->softDeletes();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hosts');
    }
};
