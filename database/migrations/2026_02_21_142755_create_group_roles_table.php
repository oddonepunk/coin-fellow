<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('group_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); //owner, admin, member
            $table->string('display_name'); //владелец, админ, участник
            $table->string('description')->nullable();
            $table->integer('level')->default(0); //иерархия прав
            $table->json('permissions')->nullable(); //иерархия прав
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_roles');
    }
};
