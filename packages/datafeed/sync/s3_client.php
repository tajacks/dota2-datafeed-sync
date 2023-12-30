<?php


class S3Client
{
    private Aws\S3\S3Client $client;

    private string $bucket;

    public function __construct($config, $bucket)
    {
        $this->client = new Aws\S3\S3Client($config);
        $this->bucket = $bucket;
    }

    public function get_latest_patch_summary()
    {
        $key = 'datafeed/patches_summary.json';
        if (! $this->client->doesObjectExist($this->bucket, $key)) {
            return null;
        }
        $patches = json_decode($this->get($key));
        usort($patches, function ($a, $b) {
            return $b->patch_timestamp <=> $a->patch_timestamp;
        });
        return $patches[0];
    }

    public function put_items($items_string)
    {
        $key = 'datafeed/items.json';
        $this->put($key, $items_string);
    }

    public function put_heroes($heroes_string)
    {
        $key = 'datafeed/heroes.json';
        $this->put($key, $heroes_string);
    }

    public function put_patches($patches_string)
    {
        $key = 'datafeed/patches.json';
        $this->put($key, $patches_string);
    }

    public function put_patches_summary($patches_summary_string)
    {
        $key = 'datafeed/patches_summary.json';
        $this->put($key, $patches_summary_string);
    }

    private function get($key)
    {
        try {
            return $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $key
            ])['Body']->getContents();
        } catch (Exception $e) {
            error_log('Error: Unable to get object ' . $key);
            error_log($e->getMessage());
            die(2);
        }
    }

    private function put($key, $body)
    {
        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => $body,
                'ACL' => 'public-read',
                'ContentType' => 'application/json'
            ]);
        } catch (Exception $e) {
            error_log('Error: Unable to put object ' . $key);
            error_log($e->getMessage());
            die(2);
        }
    }
}