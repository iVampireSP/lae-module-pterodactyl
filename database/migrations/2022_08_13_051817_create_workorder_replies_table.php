<?php

use App\Models\User;
use App\Models\WorkOrder\WorkOrder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_order_replies', function (Blueprint $table) {
            $table->id();

            // $table->unsignedBigInteger('upstream_id')->index();

            // content
            $table->text('content');

            // workorder id (on delete cascade)
            $table->unsignedBigInteger('work_order_id')->index();
            $table->foreign('work_order_id')->references('id')->on('work_orders')->onDelete('cascade');

            
            
            $table->boolean('is_pending')->default(false)->index();


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
        Schema::dropIfExists('workorder_replies');
    }
};
