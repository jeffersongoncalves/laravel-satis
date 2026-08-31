<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('satis_packages', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->nullable();
        });

        Schema::table('satis_credentials', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->nullable();
        });
    }
};
