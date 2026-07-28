<?php

declare(strict_types=1);

namespace App\Library\App;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use DateTimeImmutable;

/**
 * 应用授权有效期：年 + 月，均可为 0 表示永久.
 */
final class AppLicense
{
    /**
     * @return string|null Y-m-d H:i:s；null=永久
     */
    public static function calcExpiresAt(int $years, int $months, ?DateTimeImmutable $from = null): ?string
    {
        if ($years < 0 || $months < 0) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '有效期年/月不能为负数');
        }
        if ($years === 0 && $months === 0) {
            return null;
        }
        if ($years > 100 || $months > 1200) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '有效期过长');
        }
        $from ??= new DateTimeImmutable('now');
        $dt = $from;
        if ($years > 0) {
            $dt = $dt->modify('+' . $years . ' years');
        }
        if ($months > 0) {
            $dt = $dt->modify('+' . $months . ' months');
        }

        return $dt->format('Y-m-d H:i:s');
    }

    public static function isExpired(?string $expiresAt, ?int $now = null): bool
    {
        if ($expiresAt === null || $expiresAt === '') {
            return false;
        }
        $ts = strtotime($expiresAt);
        if ($ts === false) {
            return false;
        }

        return $ts < ($now ?? time());
    }

    public static function formatLabel(?string $expiresAt): string
    {
        if ($expiresAt === null || $expiresAt === '') {
            return 'forever';
        }
        if (self::isExpired($expiresAt)) {
            return 'expired|' . $expiresAt;
        }

        return 'until|' . $expiresAt;
    }
}
