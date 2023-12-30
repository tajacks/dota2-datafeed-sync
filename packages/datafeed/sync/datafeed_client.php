<?php

class DatafeedClient
{
    private string $host;

    public function __construct(string $host)
    {
        $this->host = $host;
    }

    public function get_latest_patch_summary(): stdClass
    {
        $patches = $this->get_patches_summary();
        usort($patches, function ($a, $b) {
            return $b->patch_timestamp <=> $a->patch_timestamp;
        });
        return $patches[0];
    }

    public function get_patches_summary(): array
    {
        $url = $this->host . '/datafeed/patchnoteslist?language=English';
        $response = $this->make_request($url);
        return $response->patches;
    }

    public function get_items(): array
    {
        $url = $this->host . '/datafeed/itemlist?language=English';
        $response = $this->make_request($url);

        $items = [];
        foreach ($response->result->data->itemabilities as $item) {
            $item_id = $item->id;
            $item_data = $this->make_request("https://www.dota2.com/datafeed/itemdata?language=English&item_id=$item_id")->result->data->items[0];
            $items[] = $item_data;
        }
        return $items;
    }

    public function get_heroes(): array
    {
        $url = $this->host . '/datafeed/herolist?language=English';
        $response = $this->make_request($url);

        $heroes = [];
        foreach ($response->result->data->heroes as $hero) {
            $hero_id = $hero->id;
            $hero_data = $this->make_request("https://www.dota2.com/datafeed/herodata?language=English&hero_id=$hero_id")->result->data->heroes[0];
            $heroes[] = $hero_data;
        }
        return $heroes;
    }

    private function make_request(string $url): stdClass
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            error_log('error when making request to url: ' . $url);
            error_log('Error: ' . curl_error($ch));
            curl_close($ch);
die(1);
        } else {
            curl_close($ch);
            $json = json_decode($response);
            if (! $json) {
                error_log('Error: could not decode json ' . $response);
die(1);
            }
            return $json;
        }
    }
}