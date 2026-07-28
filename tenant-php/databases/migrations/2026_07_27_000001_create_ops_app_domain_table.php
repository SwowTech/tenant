<?php

declare(strict_types=1);

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

/**
 * 租户应用自定义域名：一应用可绑多个独立域名，扫码 URL 可缩短为 https://sy.example.com/qr?c=xxx
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('ops_app_domain', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('domain', 191)->unique('uk_domain')->comment('绑定域名，不含端口');
            $table->unsignedBigInteger('tenant_id');
            $table->string('identifier', 100)->comment('应用标识 vendor/app');
            $table->string('scheme', 10)->default('https')->comment('http|https，生成外链用');
            $table->unsignedTinyInteger('is_primary')->default(0)->comment('1=主域名');
            $table->timestamps();
            $table->index('tenant_id', 'idx_tenant');
            $table->index(['tenant_id', 'identifier'], 'ops_app_domain_tenant_identifier_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ops_app_domain');
    }
};
