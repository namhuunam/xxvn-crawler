<?php

namespace namhuunam\XxvnCrawler\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class ImageService
{
    /**
     * Download, resize and optimize image
     * 
     * @param string $url URL of original image
     * @return string|null Path to saved image
     */
    public function processImage(string $url)
    {
        try {
            // Generate a unique filename
            $extension = pathinfo($url, PATHINFO_EXTENSION) ?: 'jpg';
            $filename = Str::random(20) . '.' . $extension;
            $savePath = config('xxvn-crawler.images.save_directory', 'movies') . '/' . $filename;
            
            // Download image content
            $imageContent = $this->downloadImage($url);
            if (!$imageContent) {
                return null;
            }
            
            // Process and resize image
            $processedImage = $this->resizeImage($imageContent);
            if (!$processedImage) {
                return null;
            }
            
            // Save image to storage
            $disk = config('xxvn-crawler.images.storage_path', 'public');
            Storage::disk($disk)->put($savePath, $processedImage);
            
            // Optimize the saved image
            $this->optimizeImage(Storage::disk($disk)->path($savePath));
            
            return Storage::url($savePath);
        } catch (Exception $e) {
            Log::error("Image processing error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Download image from URL
     * 
     * @param string $url
     * @return string|null
     */
    protected function downloadImage(string $url)
    {
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 30,
                'http_errors' => false,
            ]);
            
            $response = $client->get($url);
            
            if ($response->getStatusCode() === 200) {
                return (string) $response->getBody();
            }
            
            Log::warning("Failed to download image from {$url}, status code: {$response->getStatusCode()}");
            return null;
        } catch (Exception $e) {
            Log::error("Image download error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Resize image to max dimensions
     * 
     * @param string $imageContent
     * @return string|null
     */
    protected function resizeImage(string $imageContent)
    {
        try {
            $maxDimension = config('xxvn-crawler.images.max_dimensions', 500);
            $quality = config('xxvn-crawler.images.quality', 85);
            
            $image = Image::make($imageContent);
            
            // Resize the image while maintaining aspect ratio
            if ($image->width() > $maxDimension || $image->height() > $maxDimension) {
                $image->resize($maxDimension, $maxDimension, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            
            // Encode image to JPEG with specified quality
            return (string) $image->encode('jpg', $quality);
        } catch (Exception $e) {
            Log::error("Image resize error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Optimize the image using Spatie's image optimizer
     * 
     * @param string $imagePath
     * @return void
     */
    protected function optimizeImage(string $imagePath)
    {
        try {
            $optimizerChain = OptimizerChainFactory::create();
            $optimizerChain->optimize($imagePath);
        } catch (Exception $e) {
            Log::error("Image optimization error: " . $e->getMessage());
        }
    }
}