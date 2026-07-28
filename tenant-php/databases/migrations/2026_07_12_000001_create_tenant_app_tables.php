<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_installed_app', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('identifier', 100)->comment('acme/shop');
            $table->string('version', 20)->comment('1.0.0');
            $table->tinyInteger('status')->default(1)->comment('1=enabled 2=disabled 3=uninstalled');
            $table->json('config')->nullable()->comment('应用配置');
            $table->dateTime('installed_at');
            $table->dateTime('expires_at')->nullable()->comment('授权到期，null=永久');
            $table->dateTime('updated_at');
            $table->unique('identifier');
        });

        Schema::create('tenant_app_permission', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('app_identifier', 100);
            $table->string('code', 100);
            $table->string('title', 100);
            $table->unique(['app_identifier', 'code'], 'uk_app_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_app_permission');
        Schema::dropIfExists('tenant_installed_app');
    }
};
