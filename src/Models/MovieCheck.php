<?php

namespace namhuunam\XxvnCrawler\Models;

use Illuminate\Database\Eloquent\Model;

class MovieCheck extends Model
{
    /**
     * Simple temporary model for checking movie existence.
     * This doesn't represent a table, but provides database functionality.
     */
    protected $table = 'movies';
    
    // We'll need to make some database queries, but don't intend to modify 
    // the original model, so we're creating this simple check model.
}