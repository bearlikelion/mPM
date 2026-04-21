<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('registration_enabled')->default(true);
            $table->boolean('org_creation_enabled')->default(true);
            $table->boolean('org_invites_bypass_registration')->default(true);
            $table->unsignedInteger('org_limit_per_user')->default(5);
            $table->unsignedInteger('user_limit_per_org')->default(50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
