<?php

namespace Riari\Forum\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Riari\Forum\Models\Thread;
use Riari\Forum\Models\Traits\HasSlug;

class Category extends BaseModel
{
    use SoftDeletes, HasSlug;

    /**
     * Eloquent attributes
     */
    protected $table        = 'forum_categories';
    protected $fillable     = ['category_id', 'title', 'subtitle', 'weight', 'allows_threads'];
    public    $timestamps   = false;

    /**
     * Create a new category model instance.
     *
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->perPage = config('forum.preferences.pagination.categories');
    }

    /**
     * Relationship: Parent category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo('\Riari\Forum\Models\Category', 'category_id')->orderBy('weight');
    }

    /**
     * Relationship: Child categories.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany('\Riari\Forum\Models\Category', 'category_id')->orderBy('weight');
    }

    /**
     * Relationship: Threads.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function threads()
    {
        return $this->hasMany('\Riari\Forum\Models\Thread');
    }

    /**
     * Relationship: Threads (including soft-deleted).
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function threadsWithTrashed()
    {
        return $this->threads()->withTrashed();
    }

    /**
     * Attribute: Route.
     *
     * @return string
     */
    public function getRouteAttribute()
    {
        return $this->buildRoute('forum.category.index');
    }

    /**
     * Attribute: New thread route.
     *
     * @return string
     */
    public function getNewThreadRouteAttribute()
    {
        return $this->buildRoute('forum.thread.create');
    }

    /**
     * Attribute: Paginated threads.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getThreadsPaginatedAttribute()
    {
        return $this->threads()
            ->orderBy('pinned', 'desc')
            ->orderBy('updated_at', 'desc')
            ->paginate(config('forum.preferences.pagination.threads'));
    }

    /**
     * Attribute: Paginated threads (including soft-deleted).
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getThreadsWithTrashedPaginatedAttribute()
    {
        return $this->threadsWithTrashed()
            ->orderBy('pinned', 'desc')
            ->orderBy('updated_at', 'desc')
            ->paginate(config('forum.preferences.pagination.threads'));
    }

    /**
     * Attribute: Pagination links.
     *
     * @return string
     */
    public function getPageLinksAttribute()
    {
        return $this->threadsPaginated->render();
    }

    /**
     * Attribute: Newest thread.
     *
     * @return Thread
     */
    public function getNewestThreadAttribute()
    {
        return $this->threads()->orderBy('created_at', 'desc')->first();
    }

    /**
     * Attribute: Latest active thread.
     *
     * @return Thread
     */
    public function getLatestActiveThreadAttribute()
    {
        return $this->threads()->orderBy('updated_at', 'desc')->first();
    }

    /**
     * Attribute: Threads allowed.
     *
     * @return bool
     */
    public function getThreadsAllowedAttribute()
    {
        return $this->allows_threads;
    }

    /**
     * Attribute: Thread count.
     *
     * @return int
     */
    public function getThreadCountAttribute()
    {
        return $this->rememberAttribute('threadCount', function()
        {
            return $this->threads->count();
        });
    }

    /**
     * Attribute: Post (reply) count.
     *
     * @return int
     */
    public function getPostCountAttribute()
    {
        return $this->rememberAttribute('postCount', function()
        {
            $replyCount = 0;

            $threads = $this->threads()->get(['id']);

            foreach ($threads as $thread) {
                $replyCount += $thread->posts->count() - 1;
            }

            return $replyCount;
        });
    }

    /**
     * Helper: Get route parameters.
     *
     * @return array
     */
    public function getRouteParameters()
    {
        return [
            'category'      => $this->id,
            'category_slug' => $this->slug
        ];
    }
}
