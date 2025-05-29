<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    public function up(): void
    {
        if (!Capsule::schema()->hasTable('stream_keys')) {
            Capsule::schema()->create('stream_keys', function (Blueprint $table) {
                $table->increments('id');
                $table->string('user')->nullable();
                $table->string('stream_key')->unique();
                $table->boolean('active')->default(true)->nullable();
                $table->timestamp('created_at')->useCurrent()->nullable();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->nullable();
            });
        }
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('stream_keys');
    }
};