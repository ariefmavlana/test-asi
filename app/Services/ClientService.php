<?php

namespace App\Services;

use Log;
use App\Models\MyClient;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class ClientService
{
    public function create(array $data, $logoFile = null)
    {
        try {
            // Handle logo upload if exists
            if ($logoFile) {
                $filename = $this->uploadLogo($logoFile, $data['slug']);
                $data['client_logo'] = $filename;
            }

            // Create client
            $client = MyClient::create($data);

            // Store in Redis
            $this->storeInRedis($client);

            return $client;
        } catch (\Exception $e) {
            Log::error('Error creating client: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update($slug, array $data, $logoFile = null)
    {
        try {
            $client = MyClient::where('slug', $slug)->firstOrFail();

            // Handle logo upload if exists
            if ($logoFile) {
                $filename = $this->uploadLogo($logoFile, $slug);
                $data['client_logo'] = $filename;

                // Delete old logo if it exists and is not the default
                if ($client->client_logo !== 'no-image.jpg') {
                    Storage::disk('s3')->delete($client->client_logo);
                }
            }

            // Update client
            $client->update($data);

            // Update Redis
            $this->deleteFromRedis($slug);
            $this->storeInRedis($client->fresh());

            return $client;
        } catch (\Exception $e) {
            \Log::error('Error updating client: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete($slug)
    {
        try {
            $client = MyClient::where('slug', $slug)->firstOrFail();
            $client->delete();  // Soft delete

            // Remove from Redis
            $this->deleteFromRedis($slug);

            return true;
        } catch (\Exception $e) {
            \Log::error('Error deleting client: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find($slug)
    {
        // Try to get from Redis first
        $redisKey = "client:{$slug}";
        $cachedClient = Redis::get($redisKey);

        if ($cachedClient) {
            return json_decode($cachedClient, true);
        }

        // If not in Redis, get from database
        $client = MyClient::where('slug', $slug)->first();
        if ($client) {
            $this->storeInRedis($client);
            return $client;
        }

        return null;
    }

    private function uploadLogo($file, $slug)
    {
        $extension = $file->getClientOriginalExtension();
        $filename = "client_logos/{$slug}/" . Str::random(20) . ".{$extension}";

        Storage::disk('s3')->put($filename, file_get_contents($file));

        return $filename;
    }

    private function storeInRedis($client)
    {
        Redis::set(
            "client:{$client->slug}",
            json_encode($client->toArray()),
            'EX',
            86400 // 24 hours
        );
    }

    private function deleteFromRedis($slug)
    {
        Redis::del("client:{$slug}");
    }
}
