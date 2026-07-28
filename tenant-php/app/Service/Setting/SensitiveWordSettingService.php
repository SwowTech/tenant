<?php

declare(strict_types=1);

namespace App\Service\Setting;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use App\Repository\SystemSettingRepository;

final class SensitiveWordSettingService
{
    public const KEY = 'sensitive_words';

    public function __construct(private readonly SystemSettingRepository $repo) {}

    public function get(): array
    {
        return ['list' => $this->list()];
    }

    /** @return list<string> */
    public function list(): array
    {
        $stored = $this->repo->get(self::KEY, []);
        if (! is_array($stored)) {
            return [];
        }

        $list = [];
        foreach ($stored as $item) {
            $word = trim((string) $item);
            if ($word !== '' && ! in_array($word, $list, true)) {
                $list[] = $word;
            }
        }

        return $list;
    }

    public function add(string $word): array
    {
        $word = trim($word);
        if ($word === '') {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '敏感词不能为空');
        }

        $list = $this->list();
        foreach (preg_split('/\R/u', $word) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '' && ! in_array($line, $list, true)) {
                $list[] = $line;
            }
        }
        $this->repo->set(self::KEY, $list);

        return ['list' => $list];
    }

    public function delete(string $word): array
    {
        $word = trim($word);
        if ($word === '') {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '敏感词不能为空');
        }

        $list = $this->list();
        $index = array_search($word, $list, true);
        if ($index === false) {
            throw new BusinessException(ResultCode::NOT_FOUND, '敏感词不存在');
        }
        unset($list[$index]);
        $list = array_values($list);
        $this->repo->set(self::KEY, $list);

        return ['list' => $list];
    }
}
