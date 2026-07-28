<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('system_setting', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key', 64)->unique();
            $table->json('value')->nullable();
            $table->datetimes();
        });
        Schema::create('site_icp', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('domain', 255)->default('');
            $table->string('icp', 128)->default('');
            $table->string('police', 128)->default('');
            $table->string('license_url', 512)->default('');
            $table->datetimes();
        });

        // raw SQL 不会自动加表前缀，必须手动拼接（租户开通时为 cy_{id}_）
        $table = Schema::getConnection()->getTablePrefix() . 'site_icp';
        try {
            Db::statement("ALTER TABLE `{$table}` ADD INDEX `site_icp_domain_index` (`domain`(191))");
        } catch (\Throwable $e) {
            if (! str_contains($e->getMessage(), 'Duplicate key name')
                && ! str_contains($e->getMessage(), '1061')) {
                throw $e;
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_icp');
        Schema::dropIfExists('system_setting');
    }
};
