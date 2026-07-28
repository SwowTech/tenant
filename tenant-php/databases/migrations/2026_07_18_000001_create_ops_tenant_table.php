<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateOpsTenantTable extends Migration
{
    public function up(): void
    {
        // 租户开通会带 cy_{id}_ 前缀跑全量迁移；ops_tenant 是平台名册，禁止建成 cy_x_ops_tenant
        if (Schema::getConnection()->getTablePrefix() !== '') {
            return;
        }

        Schema::create('ops_tenant', static function (Blueprint $table) {
            $table->comment('业务租户名册');
            $table->bigIncrements('id');
            $table->string('code', 64)->unique();
            $table->string('name', 128);
            $table->string('domain', 128)->unique();
            $table->string('custom_domain', 255)->default('');
            $table->string('table_prefix', 32)->default('');
            $table->tinyInteger('status')->default(5)->comment('5 provisioning');
            $table->string('contact_phone', 32)->default('');
            $table->string('contact_email', 128)->default('');
            $table->string('remark', 500)->default('');
            $table->datetimes();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getTablePrefix() !== '') {
            return;
        }
        Schema::dropIfExists('ops_tenant');
    }
}
