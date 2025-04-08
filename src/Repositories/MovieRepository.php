<?php

namespace namhuunam\XxvnCrawler\Repositories;

use Illuminate\Support\Facades\DB;
use namhuunam\XxvnCrawler\Models\MovieCheck;

class MovieRepository
{
    /**
     * Check if a movie exists by slug
     * 
     * @param string $slug
     * @return bool
     */
    public function movieExistsBySlug(string $slug)
    {
        return MovieCheck::where('slug', $slug)->exists();
    }

    /**
     * Create a new movie record
     * 
     * @param array $movieParams
     * @return int|null Movie ID
     */
    public function createMovie(array $movieParams)
    {
        return DB::table('movies')->insertGetId(array_merge($movieParams, [
            'created_at' => now(),
            'updated_at' => now(),
            'episode_server_count' => 0,
            'episode_data_count' => 0,
            'view_total' => 0,
            'view_day' => 0,
            'view_week' => 0,
            'view_month' => 0,
            'rating_count' => 0,
            'rating_star' => 0.0,
            'is_shown_in_theater' => 0,
            'is_recommended' => 0,
            'is_copyright' => 0,
            'is_sensitive_content' => 0,
        ]));
    }

    /**
     * Find or create an actor
     * 
     * @param array $actorParams
     * @return int Actor ID
     */
    public function findOrCreateActor(array $actorParams)
    {
        $actor = DB::table('actors')->where('slug', $actorParams['slug'])->first();
        
        if ($actor) {
            return $actor->id;
        }
        
        return DB::table('actors')->insertGetId(array_merge($actorParams, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    /**
     * Link movie with actor
     * 
     * @param int $movieId
     * @param int $actorId
     * @return bool
     */
    public function linkMovieActor(int $movieId, int $actorId)
    {
        $exists = DB::table('actor_movie')
            ->where('movie_id', $movieId)
            ->where('actor_id', $actorId)
            ->exists();
            
        if (!$exists) {
            return DB::table('actor_movie')->insert([
                'movie_id' => $movieId,
                'actor_id' => $actorId,
            ]);
        }
        
        return false;
    }

    /**
     * Find or create a category
     * 
     * @param array $categoryParams
     * @return int Category ID
     */
    public function findOrCreateCategory(array $categoryParams)
    {
        $category = DB::table('categories')->where('slug', $categoryParams['slug'])->first();
        
        if ($category) {
            return $category->id;
        }
        
        return DB::table('categories')->insertGetId(array_merge($categoryParams, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    /**
     * Link movie with category
     * 
     * @param int $movieId
     * @param int $categoryId
     * @return bool
     */
    public function linkMovieCategory(int $movieId, int $categoryId)
    {
        $exists = DB::table('category_movie')
            ->where('movie_id', $movieId)
            ->where('category_id', $categoryId)
            ->exists();
            
        if (!$exists) {
            return DB::table('category_movie')->insert([
                'movie_id' => $movieId,
                'category_id' => $categoryId,
            ]);
        }
        
        return false;
    }

    /**
     * Find or create a region
     * 
     * @param array $regionParams
     * @return int Region ID
     */
    public function findOrCreateRegion(array $regionParams)
    {
        $region = DB::table('regions')->where('slug', $regionParams['slug'])->first();
        
        if ($region) {
            return $region->id;
        }
        
        return DB::table('regions')->insertGetId(array_merge($regionParams, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    /**
     * Link movie with region
     * 
     * @param int $movieId
     * @param int $regionId
     * @return bool
     */
    public function linkMovieRegion(int $movieId, int $regionId)
    {
        $exists = DB::table('movie_region')
            ->where('movie_id', $movieId)
            ->where('region_id', $regionId)
            ->exists();
            
        if (!$exists) {
            return DB::table('movie_region')->insert([
                'movie_id' => $movieId,
                'region_id' => $regionId,
            ]);
        }
        
        return false;
    }

    /**
     * Find or create a tag
     * 
     * @param array $tagParams
     * @return int Tag ID
     */
    public function findOrCreateTag(array $tagParams)
    {
        $tag = DB::table('tags')->where('slug', $tagParams['slug'])->first();
        
        if ($tag) {
            return $tag->id;
        }
        
        return DB::table('tags')->insertGetId(array_merge($tagParams, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    /**
     * Link movie with tag
     * 
     * @param int $movieId
     * @param int $tagId
     * @return bool
     */
    public function linkMovieTag(int $movieId, int $tagId)
    {
        $exists = DB::table('movie_tag')
            ->where('movie_id', $movieId)
            ->where('tag_id', $tagId)
            ->exists();
            
        if (!$exists) {
            return DB::table('movie_tag')->insert([
                'movie_id' => $movieId,
                'tag_id' => $tagId,
            ]);
        }
        
        return false;
    }

    /**
     * Create episode
     * 
     * @param array $episodeParams
     * @return int Episode ID
     */
    public function createEpisode(array $episodeParams)
    {
        return DB::table('episodes')->insertGetId(array_merge($episodeParams, [
            'has_report' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }
}