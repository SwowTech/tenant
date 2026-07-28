<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

/**
 * 租户已装应用增加授权到期时间.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('tenant_installed_app')) {
            return;
        }
        if (Schema::hasColumn('tenant_installed_app', 'expires_at')) {
            return;
        }
        Schema::table('tenant_installed_app', function (Blueprint $table) {
            $table->dateTime('expires_at')->nullable()->after('installed_at')->comment('授权到期，null=永久');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenant_installed_app')) {
            return;
        }
        if (! Schema::hasColumn('tenant_installed_app', 'expires_at')) {
            return;
        }
        Schema::table('tenant_installed_app', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
