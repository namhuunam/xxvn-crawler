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
        // Sử dụng URL chính xác mà bạn đã xác nhận hoạt động
        $this->baseUrl = 'https://xxvnapi.com';
        $this->delay = config('xxvn-crawler.api.delay', 1);
        $this->timeout = config('xxvn-crawler.api.timeout', 30);
        $this->retries = config('xxvn-crawler.api.retries', 3);
        
        $this->client = new Client([
            // ĐỪNG thêm base_uri, chúng ta sẽ dùng URL đầy đủ
            'timeout' => $this->timeout,
            'http_errors' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => '*/*'
            ],
            'verify' => false // Bỏ qua xác minh SSL nếu cần
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
        $url = "{$this->baseUrl}/api/phim-moi-cap-nhat";
        echo "Requesting: {$url}?page={$page}\n";
        return $this->makeRequest($url, ['page' => $page]);
    }
    
    /**
     * Get a specific movie by slug
     * 
     * @param string $slug
     * @return array|null
     */
    public function getMovie(string $slug)
    {
        $url = "{$this->baseUrl}/api/phim/{$slug}";
        echo "Requesting movie: {$url}\n";
        return $this->makeRequest($url);
    }

    /**
     * Make API request with retry logic
     * 
     * @param string $url Complete URL
     * @param array $queryParams
     * @return array|null
     */
    protected function makeRequest(string $url, array $queryParams = [])
    {
        $attempt = 0;
        
        while ($attempt < $this->retries) {
            try {
                // Add delay except for first attempt
                if ($attempt > 0) {
                    sleep($this->delay * $attempt); // Exponential backoff
                    echo "Retrying request (attempt {$attempt})...\n";
                }
                
                $fullUrl = $url;
                if (!empty($queryParams)) {
                    $fullUrl .= '?' . http_build_query($queryParams);
                }
                echo "Full request URL: {$fullUrl}\n";
                
                // Sử dụng GET với URL đầy đủ thay vì base_uri + endpoint
                $response = $this->client->request('GET', $fullUrl);
                
                $statusCode = $response->getStatusCode();
                $bodyContent = (string) $response->getBody();
                
                echo "Response status: {$statusCode}\n";
                
                if ($statusCode === 200) {
                    if (empty($bodyContent)) {
                        echo "Empty response body\n";
                        $attempt++;
                        continue;
                    }
                    
                    $data = json_decode($bodyContent, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        echo "JSON decode error: " . json_last_error_msg() . "\n";
                        echo "Response body (first 200 chars): " . substr($bodyContent, 0, 200) . "...\n";
                        $attempt++;
                        continue;
                    }
                    
                    // Always wait for the specified delay
                    sleep($this->delay);
                    
                    if (!empty($data) && isset($data['status']) && $data['status'] === true) {
                        return $data;
                    }
                    
                    // Log API errors
                    if (isset($data['msg'])) {
                        $errorMsg = "API Error: {$data['msg']} for URL {$fullUrl}";
                        echo $errorMsg . "\n";
                        Log::error($errorMsg);
                    } else {
                        echo "Response doesn't contain expected format. Response: " . json_encode($data) . "\n";
                    }
                } else {
                    echo "Non-200 response. Response body: " . substr($bodyContent, 0, 200) . "...\n";
                }
                
                Log::warning("API request failed with status {$statusCode} for URL {$fullUrl}, attempt {$attempt}");
                
            } catch (GuzzleException $e) {
                $errorMsg = "API Exception: " . $e->getMessage() . " for URL {$url}, attempt {$attempt}";
                echo $errorMsg . "\n";
                Log::error($errorMsg);
            }
            
            $attempt++;
        }
        
        return null;
    }
}
