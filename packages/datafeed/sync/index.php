<?php

require 's3_client.php';
require 'datafeed_client.php';

function main()
{
    echo 'Starting data feed sync';

    # all environment variables must be set
    $secrets = get_configurables();
    if ($secrets == null) {
        die(1);
    }

    # Datafeed client configured for the dota2.com
    $datafeedClient = new DatafeedClient('https://www.dota2.com');

    # S3 client configured for the bucket we want to sync to
    $s3Client = new S3Client([
        'version' => 'latest',
        'region' => 'us-east-1',
        'endpoint' => 'https://nyc3.digitaloceanspaces.com',
        'use_path_style_endpoint' => false,
        'credentials' => [
            'key' => $secrets['S3_ACCESS_KEY'],
            'secret' => $secrets['S3_SECRET_KEY']
        ],
    ], $secrets['S3_BUCKET']);

    # Compare the latest patch timestamp from the datafeed to the latest patch timestamp in the S3 bucket
    # If the datafeed is newer, sync the datafeed to the S3 bucket
    $bucket_patch = $s3Client->get_latest_patch_summary();
    $latest_patch = $datafeedClient->get_latest_patch_summary();

    if ($bucket_patch != null && $latest_patch->patch_number == $bucket_patch->patch_number) {
        echo 'No new patches found, exiting';
        return;
    }

    echo 'New patch found, syncing datafeed to S3';
    echo 'Latest patch: ' . $latest_patch->patch_number;
    echo 'Bucket patch: ' . $bucket_patch->patch_number;

    echo 'Syncing patch summary';
    $s3Client->put_patches_summary(json_encode($datafeedClient->get_patches_summary()));
    echo 'Syncing items';
    $s3Client->put_items(json_encode($datafeedClient->get_items()));
    echo 'Syncing heroes';
    $s3Client->put_heroes(json_encode($datafeedClient->get_heroes()));
}

function get_configurables(): ?array
{
    $keys = [
        'S3_BUCKET',
        'S3_ACCESS_KEY',
        'S3_SECRET_KEY'
    ];

    $response = [];

    foreach ($keys as $key) {
        $val = getenv($key);
        if ($val == null) {
            error_log("unset required environment variable $key");
            return null;
        }
        $response[$key] = $val;
    }
    return $response;
}