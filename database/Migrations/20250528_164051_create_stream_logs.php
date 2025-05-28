<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return new class
{
    public function up(): void
    {
        if (!Capsule::schema()->hasTable('stream_logs')) {
            Capsule::schema()->create('stream_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->string('stream_key')->nullable();
                $table->enum('action_name', ['start', 'stop'])->nullable();
                $table->string('source_ip', 45)->nullable();
                $table->timestamp('created_at')->useCurrent()->nullable();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->nullable();
            });
        }
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('stream_logs');
    }
};