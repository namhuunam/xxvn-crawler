<?php

namespace namhuunam\XxvnCrawler\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use namhuunam\XxvnCrawler\Repositories\MovieRepository;

class MovieService
{
    protected $apiService;
    protected $imageService;
    protected $movieRepository;

    public function __construct(
        ApiService $apiService, 
        ImageService $imageService
    ) {
        $this->apiService = $apiService;
        $this->imageService = $imageService;
        $this->movieRepository = app('xxvn-crawler.repository');
    }

    /**
     * Get all movie slugs from page range
     * 
     * @param int $fromPage
     * @param int $toPage
     * @return array
     */
    public function getAllMovieSlugs(int $fromPage, int $toPage)
    {
        $slugs = [];
        
        for ($page = $fromPage; $page >= $toPage; $page--) {
            Log::info("Fetching movie slugs from page {$page}");
            
            $response = $this->apiService->getMoviesPage($page);
            
            if (!$response || !isset($response['movies']) || !is_array($response['movies'])) {
                Log::warning("Failed to fetch movies from page {$page} or no movies found");
                continue;
            }
            
            foreach ($response['movies'] as $movie) {
                if (isset($movie['slug']) && !empty($movie['slug'])) {
                    $slugs[] = $movie['slug'];
                }
            }
            
            Log::info("Collected " . count($slugs) . " movie slugs so far");
        }
        
        return $slugs;
    }

    /**
     * Process a single movie by slug
     * 
     * @param string $slug
     * @return bool
     */
    public function processMovie(string $slug)
    {
        // Check if movie already exists
        if ($this->movieRepository->movieExistsBySlug($slug)) {
            Log::info("Movie with slug '{$slug}' already exists, skipping");
            return false;
        }
        
        // Get movie details
        $response = $this->apiService->getMovie($slug);
        
        if (!$response || !isset($response['movie'])) {
            Log::warning("Failed to fetch movie details for slug '{$slug}'");
            return false;
        }
        
        $movieData = $response['movie'];
        Log::info("Processing movie: " . ($movieData['name'] ?? 'Unknown'));
        
        try {
            // Begin transaction
            \DB::beginTransaction();
            
            // 1. Download and process the image
            $localImagePath = null;
            if (isset($movieData['thumb_url']) && !empty($movieData['thumb_url'])) {
                $localImagePath = $this->imageService->processImage($movieData['thumb_url']);
            }
            
            // 2. Create movie record
            $movieId = $this->createMovie($movieData, $localImagePath);
            
            if (!$movieId) {
                throw new \Exception("Failed to create movie record");
            }
            
            // 3. Process actors
            $this->processActors($movieId, $movieData['actors'] ?? []);
            
            // 4. Process categories
            $this->processCategories($movieId, $movieData['categories'] ?? []);
            
            // 5. Process region/country
            $this->processRegion($movieId, $movieData['country'] ?? null);
            
            // 6. Process tags
            $this->processTags($movieId, $movieData);
            
            // 7. Process episodes
            $this->processEpisodes($movieId, $movieData['episodes'] ?? []);
            
            // Commit transaction
            \DB::commit();
            
            Log::info("Successfully processed movie {$movieData['name']} with ID {$movieId}");
            return true;
            
        } catch (\Exception $e) {
            // Rollback transaction on error
            \DB::rollBack();
            Log::error("Error processing movie {$slug}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new movie record
     * 
     * @param array $movieData
     * @param string|null $localImagePath
     * @return int|null Movie ID
     */
    protected function createMovie(array $movieData, ?string $localImagePath)
    {
        $name = $movieData['name'] ?? '';
        $originName = $this->extractOriginName($name);
        
        $movieParams = [
            'name' => $name,
            'origin_name' => $originName,
            'slug' => $movieData['slug'] ?? '',
            'content' => '<p>' . ($movieData['content'] ?? '') . '</p>',
            'thumb_url' => $localImagePath,
            'poster_url' => $localImagePath,
            'type' => $movieData['type'] ?? 'single',
            'status' => $movieData['status'] ?? 'completed',
            'episode_time' => $movieData['time'] ?? null,
            'episode_current' => 'Full',
            'episode_total' => '1',
            'quality' => $movieData['quality'] ?? 'HD',
            'language' => array_key_exists('lang', $movieData) ? $movieData['lang'] : 'Vietsub',
            'publish_year' => date('Y'),
            'update_identity' => $movieData['id'] ?? null,
            'user_id' => 1,
            'user_name' => 'admin',
        ];
        
        return $this->movieRepository->createMovie($movieParams);
    }

    /**
     * Extract origin name from full movie name
     * 
     * @param string $fullName
     * @return string
     */
    protected function extractOriginName(string $fullName)
    {
        // Try to find pattern like "MEYD-967_Title"
        if (preg_match('/^([A-Z0-9\-]+)_/', $fullName, $matches)) {
            return $matches[1];
        }
        
        return $fullName;
    }

    /**
     * Process and link actors
     * 
     * @param int $movieId
     * @param array $actors
     * @return void
     */
    protected function processActors(int $movieId, array $actors)
    {
        foreach ($actors as $actorName) {
            if (empty($actorName)) {
                continue;
            }
            
            $actorSlug = Str::slug($actorName);
            $actorId = $this->movieRepository->findOrCreateActor([
                'name' => $actorName,
                'name_md5' => md5($actorName),
                'slug' => $actorSlug,
                'gender' => 'female',
            ]);
            
            if ($actorId) {
                $this->movieRepository->linkMovieActor($movieId, $actorId);
            }
        }
    }

    /**
     * Process and link categories
     * 
     * @param int $movieId
     * @param array $categories
     * @return void
     */
    protected function processCategories(int $movieId, array $categories)
    {
        foreach ($categories as $category) {
            if (empty($category['name']) || empty($category['slug'])) {
                continue;
            }
            
            $categoryId = $this->movieRepository->findOrCreateCategory([
                'name' => $category['name'],
                'slug' => $category['slug'],
                'user_id' => 1,
                'user_name' => 'admin',
            ]);
            
            if ($categoryId) {
                $this->movieRepository->linkMovieCategory($movieId, $categoryId);
            }
        }
    }

    /**
     * Process and link region
     * 
     * @param int $movieId
     * @param array|null $region
     * @return void
     */
    protected function processRegion(int $movieId, ?array $region)
    {
        if (!$region || empty($region['name']) || empty($region['slug'])) {
            return;
        }
        
        $regionId = $this->movieRepository->findOrCreateRegion([
            'name' => $region['name'],
            'slug' => $region['slug'],
            'user_id' => 1,
            'user_name' => 'admin',
        ]);
        
        if ($regionId) {
            $this->movieRepository->linkMovieRegion($movieId, $regionId);
        }
    }

    /**
     * Process and link tags
     * 
     * @param int $movieId
     * @param array $movieData
     * @return void
     */
    protected function processTags(int $movieId, array $movieData)
    {
        // Add origin name as tag
        $originName = $this->extractOriginName($movieData['name'] ?? '');
        if (!empty($originName)) {
            $tagId = $this->movieRepository->findOrCreateTag([
                'name' => $originName,
                'name_md5' => md5($originName),
                'slug' => Str::slug($originName),
            ]);
            
            if ($tagId) {
                $this->movieRepository->linkMovieTag($movieId, $tagId);
            }
        }
        
        // Add actors as tags
        if (isset($movieData['actors']) && is_array($movieData['actors'])) {
            foreach ($movieData['actors'] as $actorName) {
                if (empty($actorName)) {
                    continue;
                }
                
                $tagId = $this->movieRepository->findOrCreateTag([
                    'name' => $actorName,
                    'name_md5' => md5($actorName),
                    'slug' => Str::slug($actorName),
                ]);
                
                if ($tagId) {
                    $this->movieRepository->linkMovieTag($movieId, $tagId);
                }
            }
        }
    }

    /**
     * Process and link episodes
     * 
     * @param int $movieId
     * @param array $episodes
     * @return void
     */
    protected function processEpisodes(int $movieId, array $episodes)
    {
        foreach ($episodes as $serverData) {
            $serverName = $serverData['server_name'] ?? '';
            $episodes = $serverData['server_data'] ?? [];
            
            foreach ($episodes as $episode) {
                if (empty($episode['name']) || empty($episode['slug']) || empty($episode['link'])) {
                    continue;
                }
                
                $this->movieRepository->createEpisode([
                    'movie_id' => $movieId,
                    'server' => $serverName,
                    'name' => $episode['name'],
                    'slug' => $episode['slug'],
                    'type' => 'embed',
                    'link' => $episode['link'],
                ]);
            }
        }
    }
}
