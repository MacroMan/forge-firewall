<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServerRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('server_rules', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('server_id');
            $table->unsignedInteger('port');
            $table->integer('forge_id')->nullable();
            $table->boolean('is_installed')->default(false);

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
        Schema::dropIfExists('server_rules');
    }
}
