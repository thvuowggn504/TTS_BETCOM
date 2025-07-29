<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Unique;

return new class extends Migration {
    public function up(): void
    {
        // USERS
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->string('password', 255);
            $table->enum('role', ['admin', 'editor', 'viewer'])->default('viewer');
            $table->timestamp('created_at')->useCurrent();
        });

        // Tên đúng với Laravel Breeze
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // TYPE
        Schema::create('type', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
        });

        // PARTS
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('code');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('type_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('type_id')->references('id')->on('type')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // REVISIONS
        Schema::create('revisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('part_id');
            $table->string('revision_code', 30);
            $table->unsignedBigInteger('latest_version')->nullable(); // FK bổ sung sau
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('part_id')->references('id')->on('parts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // VERSIONS
        Schema::create('versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('revision_id');
            $table->string('version_code', 30);
            $table->string('name', 100);
            $table->text('code');
            $table->unsignedBigInteger('based_upon_version_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('type_id')->nullable();
            $table->enum('status', ['Draft', 'Published', 'Archived'])->default('Draft');
            $table->boolean('enable_assembly_groups')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('revision_id')->references('id')->on('revisions')->onDelete('cascade');
            $table->foreign('based_upon_version_id')->references('id')->on('versions')->onDelete('set null');
            $table->foreign('type_id')->references('id')->on('type')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // Bổ sung FK latest_version cho revisions
        Schema::table('revisions', function (Blueprint $table) {
            $table->foreign('latest_version')->references('id')->on('versions')->onDelete('set null');
        });

        // ADDITIONAL_FIELDS
        Schema::create('additional_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('value', 50);
            $table->unsignedBigInteger('version_id');
            $table->enum('data_type', ['string', 'int', 'boolean', 'select', 'select-multi'])->default('string');
            $table->enum('type_group', ['custom', 'inherited', 'standard'])->default('custom');
            $table->foreign('version_id')->references('id')->on('versions')->onDelete('cascade');
        });

        // GROUPS
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assembler_id');
            $table->string('name', 100);
            $table->unsignedBigInteger('type_id')->nullable(); // Bổ sung trường type_id
            $table->unsignedBigInteger('version_id')->nullable();
            $table->unique(['version_id', 'name']);
            $table->boolean('is_optional')->default(false);

            $table->foreign('assembler_id')->references('id')->on('parts')->onDelete(action: 'cascade');
            $table->foreign('type_id')->references('id')->on('type')->onDelete('set null'); //Thêm FK cho type_id
            $table->foreign('version_id')->references('id')->on('versions')->onDelete('set null');
        });

        // GROUP_PARTS
        Schema::create('group_parts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('part_id');
            $table->integer('quantity')->default(1);
            $table->unsignedBigInteger('version_id')->nullable();

            $table->foreign('version_id')->references('id')->on('versions')->onDelete('set null');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->foreign('part_id')->references('id')->on('parts')->onDelete('cascade');
            $table->unique(['group_id', 'part_id']);
        });

        // CODEBUILDER_RULES
        Schema::create('codebuilder', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('version_id');
            $table->string('name', 100);
            $table->text('rule')->nullable();
            $table->json('rule_data')->nullable();
            $table->boolean('is_default')->default();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('version_id')->references('id')->on('versions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codebuilder');
        Schema::dropIfExists('group_parts');
        Schema::dropIfExists('groups');

        Schema::table('revisions', function (Blueprint $table) {
            $table->dropForeign(['latest_version']);
        });

        Schema::dropIfExists('additional_fields');
        Schema::dropIfExists('versions');
        Schema::dropIfExists('revisions');
        Schema::dropIfExists('parts');
        Schema::dropIfExists('type');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('users');
    }
};
