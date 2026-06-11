<?php

declare(strict_types=1);

namespace SDF\Storage\Drivers;

use SDF\Storage\Contracts\StorageDriver;

class S3Driver implements StorageDriver
{
    private string $bucket;
    private string $region;
    private string $key;
    private string $secret;
    private string $endpoint;
    private string $version;
    private ?object $client = null;

    public function __construct(array $config)
    {
        $this->bucket = $config['bucket'] ?? '';
        $this->region = $config['region'] ?? 'us-east-1';
        $this->key = $config['key'] ?? '';
        $this->secret = $config['secret'] ?? '';
        $this->endpoint = $config['endpoint'] ?? '';
        $this->version = $config['version'] ?? 'latest';
    }

    private function client(): object
    {
        if ($this->client === null) {
            if (!class_exists(\Aws\S3\S3Client::class)) {
                throw new \RuntimeException(
                    'AWS SDK not found. Install aws/aws-sdk-php via Composer to use S3Driver.'
                );
            }
            $args = [
                'version' => $this->version,
                'region' => $this->region,
                'credentials' => [
                    'key' => $this->key,
                    'secret' => $this->secret,
                ],
            ];
            if ($this->endpoint !== '') {
                $args['endpoint'] = $this->endpoint;
                $args['use_path_style_endpoint'] = true;
            }
            $this->client = new \Aws\S3\S3Client($args);
        }
        return $this->client;
    }

    public function exists(string $path): bool
    {
        return $this->client()->doesObjectExist($this->bucket, $path);
    }

    public function get(string $path): ?string
    {
        try {
            $result = $this->client()->getObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);
            return $result['Body']->getContents();
        } catch (\Aws\Exception\AwsException $e) {
            if ($e->getStatusCode() === 404) {
                return null;
            }
            throw $e;
        }
    }

    public function put(string $path, string $contents): bool
    {
        $this->client()->putObject([
            'Bucket' => $this->bucket,
            'Key' => $path,
            'Body' => $contents,
        ]);
        return true;
    }

    public function stream(string $path, $resource): bool
    {
        $this->client()->putObject([
            'Bucket' => $this->bucket,
            'Key' => $path,
            'Body' => $resource,
        ]);
        if (is_resource($resource)) {
            fclose($resource);
        }
        return true;
    }

    public function delete(string $path): bool
    {
        $this->client()->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $path,
        ]);
        return true;
    }

    public function url(string $path): string
    {
        if ($this->endpoint !== '') {
            return rtrim($this->endpoint, '/') . '/' . $this->bucket . '/' . ltrim($path, '/');
        }
        return "https://{$this->bucket}.s3.{$this->region}.amazonaws.com/{$path}";
    }

    public function size(string $path): int
    {
        try {
            $result = $this->client()->headObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);
            return (int) ($result['ContentLength'] ?? 0);
        } catch (\Aws\Exception\AwsException $e) {
            if ($e->getStatusCode() === 404) {
                return 0;
            }
            throw $e;
        }
    }

    public function mimeType(string $path): string
    {
        try {
            $result = $this->client()->headObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);
            return $result['ContentType'] ?? 'application/octet-stream';
        } catch (\Aws\Exception\AwsException $e) {
            if ($e->getStatusCode() === 404) {
                return 'application/octet-stream';
            }
            throw $e;
        }
    }

    public function files(?string $directory = null): array
    {
        $prefix = $directory !== null ? rtrim($directory, '/') . '/' : '';
        $results = $this->client()->listObjects([
            'Bucket' => $this->bucket,
            'Prefix' => $prefix,
            'Delimiter' => '/',
        ]);
        $files = [];
        if (isset($results['Contents'])) {
            foreach ($results['Contents'] as $obj) {
                $key = $obj['Key'];
                if ($key === $prefix) {
                    continue;
                }
                $files[] = $key;
            }
        }
        return $files;
    }

    public function allFiles(?string $directory = null): array
    {
        $prefix = $directory !== null ? rtrim($directory, '/') . '/' : '';
        $results = $this->client()->listObjects([
            'Bucket' => $this->bucket,
            'Prefix' => $prefix,
        ]);
        $files = [];
        if (isset($results['Contents'])) {
            foreach ($results['Contents'] as $obj) {
                $key = $obj['Key'];
                if ($key === $prefix) {
                    continue;
                }
                $files[] = $key;
            }
        }
        return $files;
    }

    public function directories(?string $directory = null): array
    {
        $prefix = $directory !== null ? rtrim($directory, '/') . '/' : '';
        $results = $this->client()->listObjects([
            'Bucket' => $this->bucket,
            'Prefix' => $prefix,
            'Delimiter' => '/',
        ]);
        $dirs = [];
        if (isset($results['CommonPrefixes'])) {
            foreach ($results['CommonPrefixes'] as $dp) {
                $dirs[] = rtrim($dp['Prefix'], '/');
            }
        }
        return $dirs;
    }

    public function copy(string $from, string $to): bool
    {
        $this->client()->copyObject([
            'Bucket' => $this->bucket,
            'CopySource' => urlencode($this->bucket . '/' . $from),
            'Key' => $to,
        ]);
        return true;
    }

    public function move(string $from, string $to): bool
    {
        $this->copy($from, $to);
        $this->delete($from);
        return true;
    }

    public function makeDirectory(string $path): bool
    {
        $this->client()->putObject([
            'Bucket' => $this->bucket,
            'Key' => rtrim($path, '/') . '/',
            'Body' => '',
        ]);
        return true;
    }

    public function deleteDirectory(string $path): bool
    {
        $prefix = rtrim($path, '/') . '/';
        $results = $this->client()->listObjects([
            'Bucket' => $this->bucket,
            'Prefix' => $prefix,
        ]);
        if (isset($results['Contents'])) {
            $objects = array_map(fn ($o) => ['Key' => $o['Key']], $results['Contents']);
            $this->client()->deleteObjects([
                'Bucket' => $this->bucket,
                'Delete' => ['Objects' => $objects],
            ]);
        }
        return true;
    }
}
