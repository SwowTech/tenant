<?php

declare(strict_types=1);

namespace App\Service\App;

use App\Library\App\AppGatewaySecret;
use App\Library\App\AppProcessManager;
use App\Library\Tenant\TenantInfo;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

final class AppProxyService
{
    /** @var list<string> hop-by-hop / 重建头，勿原样转发 */
    private const SKIP_HEADERS = [
        'host',
        'connection',
        'expect',
        'keep-alive',
        'proxy-authenticate',
        'proxy-authorization',
        'te',
        'trailer',
        'transfer-encoding',
        'upgrade',
        'content-length',
    ];

    public function __construct(
        private readonly AppProcessManager $processManager,
    ) {}

    public function forward(
        ServerRequestInterface $request,
        string $identifier,
        string $apiSuffix,
        TenantInfo $tenant,
        ?string $publicBase = null,
        bool $viaAppDomain = false,
    ): ResponseInterface {
        $registry = $this->processManager->ensureRunning($identifier);
        $listen = (string) ($registry['listen'] ?? '');
        $apiSuffix = ltrim($apiSuffix, '/');
        $target = 'http://' . $listen . '/' . $apiSuffix;
        if ($request->getUri()->getQuery() !== '') {
            $target .= '?' . $request->getUri()->getQuery();
        }

        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            if (in_array(strtolower($name), self::SKIP_HEADERS, true)) {
                continue;
            }
            $headers[$name] = $values;
        }
        $originalHost = $request->getHeaderLine('host');
        if ($originalHost !== '') {
            $headers['X-Forwarded-Host'] = [$originalHost];
        }
        $headers['X-Tenant-Id'] = [(string) $tenant->id];
        $headers['X-Tenant-Prefix'] = [$tenant->tablePrefix];
        $headers['X-App-Identifier'] = [$identifier];
        $headers['X-App-Gateway-Secret'] = [AppGatewaySecret::value()];
        if ($publicBase !== null && $publicBase !== '') {
            $headers['X-App-Public-Base'] = [rtrim($publicBase, '/')];
        }
        if ($viaAppDomain) {
            $headers['X-App-Root'] = ['1'];
        }

        $stream = $request->getBody();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        $body = (string) $stream;
        $uploaded = $request->getUploadedFiles();
        $isMultipart = str_contains(strtolower($request->getHeaderLine('Content-Type')), 'multipart/form-data');

        $client = new Client([
            'http_errors' => false,
            'timeout' => 120.0,
            'connect_timeout' => 5.0,
            'allow_redirects' => false,
            'force_ip_resolve' => 'v4',
            'version' => 1.0,
        ]);

        try {
            // Hyperf/Swow 会先解析 multipart，body 常已空；需从 UploadedFiles 重建
            if ($isMultipart && ($body === '' || $uploaded !== [])) {
                unset($headers['Content-Type'], $headers['content-type']);
                $multipart = $this->buildMultipart($request);
                $upstream = $client->request($request->getMethod(), $target, [
                    'headers' => $headers,
                    'multipart' => $multipart,
                ]);
            } else {
                $bodyLen = strlen($body);
                if ($bodyLen > 0) {
                    $headers['Content-Length'] = [(string) $bodyLen];
                }
                $options = ['headers' => $headers];
                if ($bodyLen > 0) {
                    $options['body'] = $body;
                }
                $upstream = $client->request($request->getMethod(), $target, $options);
            }
        } catch (GuzzleException $e) {
            return $this->plain(502, 'upstream error: ' . $e->getMessage());
        }

        // 独立域名访问时不改写 Location 前缀
        $sitePath = $viaAppDomain ? '' : ('/' . str_replace('\\', '/', $identifier));
        $response = new \Hyperf\HttpMessage\Base\Response();
        $response = $response->withStatus($upstream->getStatusCode());
        foreach ($upstream->getHeaders() as $name => $values) {
            if (in_array(strtolower($name), ['transfer-encoding', 'connection', 'content-length'], true)) {
                continue;
            }
            if (strtolower($name) === 'location' && $sitePath !== '') {
                $values = array_map(static function (string $loc) use ($sitePath): string {
                    if ($loc === '' || str_starts_with($loc, 'http://') || str_starts_with($loc, 'https://')) {
                        return $loc;
                    }
                    if (str_starts_with($loc, $sitePath . '/') || $loc === $sitePath) {
                        return $loc;
                    }
                    if (str_starts_with($loc, '/')) {
                        return $sitePath . $loc;
                    }

                    return $loc;
                }, $values);
            }
            $response = $response->withHeader($name, $values);
        }
        $upstreamBody = (string) $upstream->getBody();
        $response = $response->withHeader('Content-Length', (string) strlen($upstreamBody));
        $response = $response->withBody(new SwooleStream($upstreamBody));

        return $response;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildMultipart(ServerRequestInterface $request): array
    {
        $parts = [];
        $parsed = $request->getParsedBody();
        if (is_array($parsed)) {
            $this->appendFormFields($parts, $parsed);
        }
        $this->appendUploadedFiles($parts, $request->getUploadedFiles());

        return $parts;
    }

    /**
     * @param list<array<string, mixed>> $parts
     * @param array<string, mixed> $data
     */
    private function appendFormFields(array &$parts, array $data, string $prefix = ''): void
    {
        foreach ($data as $key => $value) {
            $name = $prefix === '' ? (string) $key : $prefix . '[' . $key . ']';
            if (is_array($value)) {
                $this->appendFormFields($parts, $value, $name);
                continue;
            }
            $parts[] = [
                'name' => $name,
                'contents' => (string) $value,
            ];
        }
    }

    /**
     * @param list<array<string, mixed>> $parts
     * @param array<string, mixed> $files
     */
    private function appendUploadedFiles(array &$parts, array $files, string $prefix = ''): void
    {
        foreach ($files as $key => $file) {
            $name = $prefix === '' ? (string) $key : $prefix . '[' . $key . ']';
            if (is_array($file)) {
                $this->appendUploadedFiles($parts, $file, $name);
                continue;
            }
            if (! $file instanceof UploadedFileInterface) {
                continue;
            }
            if ($file->getError() !== UPLOAD_ERR_OK) {
                continue;
            }
            $stream = $file->getStream();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }
            $parts[] = [
                'name' => $name,
                'contents' => (string) $stream,
                'filename' => $file->getClientFilename() ?: 'upload.bin',
                'headers' => [
                    'Content-Type' => $file->getClientMediaType() ?: 'application/octet-stream',
                ],
            ];
        }
    }

    private function plain(int $status, string $text): ResponseInterface
    {
        $response = new \Hyperf\HttpMessage\Base\Response();
        $response = $response->withStatus($status);
        $response = $response->withHeader('Content-Type', 'text/plain; charset=utf-8');
        $response = $response->withBody(new SwooleStream($text));

        return $response;
    }
}
