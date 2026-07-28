<?php

declare(strict_types=1);

namespace TraceApp;

final class Api
{
    public static function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => $code === 200 ? 200 : $code, 'message' => $code === 200 ? '成功' : 'error', 'data' => $data], JSON_UNESCAPED_UNICODE);
    }

    public static function fail(string $message, int $status = 400): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => $status, 'message' => $message, 'data' => null], JSON_UNESCAPED_UNICODE);
    }

    public static function body(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }

    public static function method(): string
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    public static function dashboard(): array
    {
        $pdo = Db::pdo();
        $today = date('Y-m-d');
        $tp = Tenant::quote('trace_product');
        $tc = Tenant::quote('trace_code');
        $tw = Tenant::quote('trace_writeoff');
        $products = (int) $pdo->query("SELECT COUNT(*) FROM {$tp}")->fetchColumn();
        $codes = (int) $pdo->query("SELECT COUNT(*) FROM {$tc}")->fetchColumn();
        $written = (int) $pdo->query("SELECT COUNT(*) FROM {$tc} WHERE status=2")->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$tc} WHERE DATE(created_at)=?");
        $stmt->execute([$today]);
        $todayCodes = (int) $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$tw} WHERE DATE(created_at)=?");
        $stmt->execute([$today]);
        $todayWriteoff = (int) $stmt->fetchColumn();

        return [
            'products' => $products,
            'codes' => $codes,
            'written_off' => $written,
            'today_codes' => $todayCodes,
            'today_writeoff' => $todayWriteoff,
            'table_prefix' => Tenant::prefix(),
        ];
    }

    public static function listProducts(): array
    {
        $stmt = Db::pdo()->query('SELECT * FROM ' . Tenant::quote('trace_product') . ' ORDER BY id DESC');

        return $stmt ? $stmt->fetchAll() : [];
    }

    public static function saveProduct(array $data, ?int $id = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $code = trim((string) ($data['code'] ?? ''));
        $status = (int) ($data['status'] ?? 1);
        $remark = trim((string) ($data['remark'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('商品名称必填');
        }
        $now = date('Y-m-d H:i:s');
        $pdo = Db::pdo();
        $t = Tenant::quote('trace_product');
        if ($id) {
            $stmt = $pdo->prepare("UPDATE {$t} SET name=?, code=?, status=?, remark=?, updated_at=? WHERE id=?");
            $stmt->execute([$name, $code, $status === 2 ? 2 : 1, $remark, $now, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO {$t} (name,code,status,remark,created_at,updated_at) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$name, $code, $status === 2 ? 2 : 1, $remark, $now, $now]);
            $id = (int) $pdo->lastInsertId();
        }
        $stmt = $pdo->prepare("SELECT * FROM {$t} WHERE id=?");
        $stmt->execute([$id]);

        return $stmt->fetch() ?: [];
    }

    public static function listBatches(): array
    {
        $tb = Tenant::quote('trace_batch');
        $tp = Tenant::quote('trace_product');
        $sql = "SELECT b.*, p.name AS product_name FROM {$tb} b
                LEFT JOIN {$tp} p ON p.id=b.product_id
                ORDER BY b.id DESC";
        $stmt = Db::pdo()->query($sql);

        return $stmt ? $stmt->fetchAll() : [];
    }

    public static function createBatch(array $data): array
    {
        $productId = (int) ($data['product_id'] ?? 0);
        $quantity = (int) ($data['quantity'] ?? 0);
        $batchNo = trim((string) ($data['batch_no'] ?? ''));
        if ($productId <= 0 || $quantity <= 0 || $quantity > 5000) {
            throw new \InvalidArgumentException('商品与数量无效（1-5000）');
        }
        if ($batchNo === '') {
            $batchNo = 'B' . date('YmdHis') . random_int(100, 999);
        }
        $pdo = Db::pdo();
        $tp = Tenant::quote('trace_product');
        $tb = Tenant::quote('trace_batch');
        $tc = Tenant::quote('trace_code');
        $check = $pdo->prepare("SELECT id FROM {$tp} WHERE id=?");
        $check->execute([$productId]);
        if (! $check->fetch()) {
            throw new \InvalidArgumentException('商品不存在');
        }
        $now = date('Y-m-d H:i:s');
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO {$tb} (product_id,batch_no,quantity,created_at) VALUES (?,?,?,?)");
            $stmt->execute([$productId, $batchNo, $quantity, $now]);
            $batchId = (int) $pdo->lastInsertId();
            $ins = $pdo->prepare("INSERT INTO {$tc} (product_id,batch_id,code,status,created_at) VALUES (?,?,?,1,?)");
            for ($i = 0; $i < $quantity; ++$i) {
                $code = strtoupper(substr(bin2hex(random_bytes(8)), 0, 16));
                $ins->execute([$productId, $batchId, $code, $now]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        $stmt = $pdo->prepare("SELECT b.*, p.name AS product_name FROM {$tb} b LEFT JOIN {$tp} p ON p.id=b.product_id WHERE b.id=?");
        $stmt->execute([$batchId]);

        return $stmt->fetch() ?: [];
    }

    public static function listBatchCodes(int $batchId): array
    {
        $tc = Tenant::quote('trace_code');
        $stmt = Db::pdo()->prepare("SELECT id, code, status, created_at, written_off_at FROM {$tc} WHERE batch_id=? ORDER BY id ASC LIMIT 5000");
        $stmt->execute([$batchId]);

        return $stmt->fetchAll();
    }

    public static function lookupCode(string $code, string $ip = ''): array
    {
        $code = trim($code);
        $pdo = Db::pdo();
        $tc = Tenant::quote('trace_code');
        $tp = Tenant::quote('trace_product');
        $tb = Tenant::quote('trace_batch');
        $tl = Tenant::quote('trace_scan_log');
        $stmt = $pdo->prepare("SELECT c.*, p.name AS product_name, b.batch_no FROM {$tc} c
            LEFT JOIN {$tp} p ON p.id=c.product_id
            LEFT JOIN {$tb} b ON b.id=c.batch_id
            WHERE c.code=?");
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        if (! $row) {
            throw new \InvalidArgumentException('码不存在');
        }
        $log = $pdo->prepare("INSERT INTO {$tl} (code,ip,created_at) VALUES (?,?,?)");
        $log->execute([$code, $ip, date('Y-m-d H:i:s')]);

        return $row;
    }

    public static function writeoff(string $code, array $data): array
    {
        $code = trim($code);
        $pdo = Db::pdo();
        $tc = Tenant::quote('trace_code');
        $tw = Tenant::quote('trace_writeoff');
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT * FROM {$tc} WHERE code=? FOR UPDATE");
            $stmt->execute([$code]);
            $row = $stmt->fetch();
            if (! $row) {
                throw new \InvalidArgumentException('码不存在');
            }
            if ((int) $row['status'] === 2) {
                throw new \InvalidArgumentException('已核销');
            }
            $now = date('Y-m-d H:i:s');
            $pdo->prepare("UPDATE {$tc} SET status=2, written_off_at=? WHERE id=?")->execute([$now, $row['id']]);
            $pdo->prepare("INSERT INTO {$tw} (code_id,code,operator,remark,created_at) VALUES (?,?,?,?,?)")
                ->execute([
                    $row['id'],
                    $code,
                    trim((string) ($data['operator'] ?? '')),
                    trim((string) ($data['remark'] ?? '')),
                    $now,
                ]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return self::lookupCode($code);
    }
}
