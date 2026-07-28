<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

/**
 * 租户已装应用增加档位 edition 与应用家族 family.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('tenant_installed_app')) {
            return;
        }
        if (! Schema::hasColumn('tenant_installed_app', 'edition')) {
            Schema::table('tenant_installed_app', function (Blueprint $table) {
                $table->string('edition', 32)->default('')->after('version')->comment('档位标识，来自 app.json');
            });
        }
        if (! Schema::hasColumn('tenant_installed_app', 'family')) {
            Schema::table('tenant_installed_app', function (Blueprint $table) {
                $table->string('family', 100)->default('')->after('edition')->comment('应用家族，用于聚合展示');
                $table->index('family');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenant_installed_app')) {
            return;
        }
        if (Schema::hasColumn('tenant_installed_app', 'family')) {
            Schema::table('tenant_installed_app', function (Blueprint $table) {
                $table->dropIndex(['family']);
                $table->dropColumn('family');
            });
        }
        if (Schema::hasColumn('tenant_installed_app', 'edition')) {
            Schema::table('tenant_installed_app', function (Blueprint $table) {
                $table->dropColumn('edition');
            });
        }
    }
};
