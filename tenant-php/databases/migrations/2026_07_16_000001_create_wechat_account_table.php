<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateWechatAccountTable extends Migration
{
    public function up(): void
    {
        Schema::create('wechat_account', static function (Blueprint $table) {
            $table->comment('微信公众号接入配置');
            $table->bigIncrements('id');
            $table->string('name', 100)->default('')->comment('公众号名称');
            $table->string('app_id', 64)->default('')->comment('AppID');
            $table->string('app_secret', 128)->default('')->comment('AppSecret');
            $table->string('token', 64)->default('')->comment('服务器 Token');
            $table->string('encoding_aes_key', 64)->default('')->comment('EncodingAESKey');
            $table->tinyInteger('level')->default(1)->comment('1订阅号 2服务号 3认证订阅号 4认证服务号');
            $table->tinyInteger('status')->default(1)->comment('1启用 0停用');
            $table->datetimes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wechat_account');
    }
}
