<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('namespace')->default('main');
            // Foreign key to revisions.id is added in a later migration,
            // because revisions does not exist yet (circular reference).
            $table->unsignedBigInteger('current_rev')->nullable();
            $table->smallInteger('protection')->default(0);
            $table->timestamps();

            $table->index(['namespace', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
