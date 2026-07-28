<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\WechatAccount;

final class WechatAccountRepository
{
    public function first(): ?WechatAccount
    {
        return WechatAccount::query()->orderBy('id')->first();
    }

    public function upsert(array $data): WechatAccount
    {
        $row = $this->first();
        if ($row === null) {
            /** @var WechatAccount $created */
            $created = WechatAccount::query()->create($data);
            return $created;
        }
        $row->fill($data);
        $row->save();
        return $row;
    }
}
