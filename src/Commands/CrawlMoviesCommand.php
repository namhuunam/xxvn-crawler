<?php

namespace namhuunam\XxvnCrawler\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use namhuunam\XxvnCrawler\Services\MovieService;

class CrawlMoviesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xxvn:crawl 
                            {--fromPage=300 : Start crawling from this page}
                            {--toPage=1 : Crawl to this page}
                            {--limit= : Limit the number of movies to process}
                            {--sleep=1 : Sleep time between movie processing in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawl movies from xxvnapi.com';

    /**
     * MovieService instance.
     *
     * @var MovieService
     */
    protected $movieService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->movieService = app('xxvn-crawler.movie-service');
        
        $fromPage = $this->option('fromPage');
        $toPage = $this->option('toPage');
        $limit = $this->option('limit');
        $sleep = $this->option('sleep');
        
        $this->info("Starting to crawl movies from page {$fromPage} to page {$toPage}");
        
        // Get all movie slugs
        $this->info("Collecting all movie slugs...");
        $slugs = $this->movieService->getAllMovieSlugs($fromPage, $toPage);
        
        $totalSlugs = count($slugs);
        $this->info("Found {$totalSlugs} movies to process");
        
        if ($limit && is_numeric($limit)) {
            $slugs = array_slice($slugs, 0, (int)$limit);
            $this->info("Limited to processing {$limit} movies");
        }
        
        // Process each movie
        $processed = 0;
        $success = 0;
        $failed = 0;
        
        $progressBar = $this->output->createProgressBar(count($slugs));
        $progressBar->start();
        
        foreach ($slugs as $slug) {
            $result = $this->movieService->processMovie($slug);
            
            if ($result) {
                $success++;
            } else {
                $failed++;
            }
            
            $processed++;
            $progressBar->advance();
            
            // Sleep between requests
            if ($sleep > 0 && $processed < count($slugs)) {
                sleep($sleep);
            }
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("Crawling completed:");
        $this->info("- Total processed: {$processed}");
        $this->info("- Successfully added: {$success}");
        $this->info("- Failed: {$failed}");
        
        return 0;
    }
}