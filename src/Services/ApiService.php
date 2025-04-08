<?php

namespace namhuunam\XxvnCrawler\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class ApiService
{
    protected $client;
    protected $retries;
    protected $delay;
    protected $timeout;
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('xxvn-crawler.api.base_url');
        $this->delay = config('xxvn-crawler.api.delay', 1);
        $this->timeout = config('xxvn-crawler.api.timeout', 30);
        $this->retries = config('xxvn-crawler.api.retries', 3);
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'http_errors' => false,
        ]);
    }

    /**
     * Get movies list from a page
     * 
     * @param int $page
     * @return array|null
     */
    public function getMoviesPage(int $page)
    {
        return $this->makeRequest("phim-moi-cap-nhat", ['page' => $page]);
    }
    
    /**
     * Get a specific movie by slug
     * 
     * @param string $slug
     * @return array|null
     */
    public function getMovie(string $slug)
    {
        return $this->makeRequest("phim/{$slug}");
    }

    /**
     * Make API request with retry logic
     * 
     * @param string $endpoint
     * @param array $queryParams
     * @return array|null
     */
    protected function makeRequest(string $endpoint, array $queryParams = [])
    {
        $attempt = 0;
        
        while ($attempt < $this->retries) {
            try {
                // Add delay except for first attempt
                if ($attempt > 0) {
                    sleep($this->delay * $attempt); // Exponential backoff
                }
                
                $response = $this->client->get($endpoint, [
                    'query' => $queryParams,
                ]);
                
                $statusCode = $response->getStatusCode();
                
                if ($statusCode === 200) {
                    $data = json_decode($response->getBody(), true);
                    
                    // Always wait for the specified delay
                    sleep($this->delay);
                    
                    if (!empty($data) && isset($data['status']) && $data['status'] === true) {
                        return $data;
                    }
                    
                    // Log API errors
                    if (isset($data['msg'])) {
                        Log::error("API Error: {$data['msg']} for endpoint {$endpoint}");
                    }
                }
                
                Log::warning("API request failed with status {$statusCode} for endpoint {$endpoint}, attempt {$attempt}");
                
            } catch (GuzzleException $e) {
                Log::error("API Exception: " . $e->getMessage() . " for endpoint {$endpoint}, attempt {$attempt}");
            }
            
            $attempt++;
        }
        
        return null;
    }
}