<?php namespace Riari\Forum\Models;

use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Riari\Forum\Libraries\AccessControl;

use Config;
use Str;

class Thread extends BaseModel {

	use SoftDeletingTrait;

	protected $table      = 'forum_threads';
	public    $timestamps = true;
	protected $dates      = ['deleted_at'];
	protected $appends    = ['lastPage', 'lastPost', 'lastPostURL', 'URL', 'replyURL', 'deleteURL'];
	protected $guarded    = ['id'];

	public function category()
	{
		return $this->belongsTo('\Riari\Forum\Models\Category', 'parent_category');
	}

	public function author()
	{
		return $this->belongsTo(Config::get('forum::integration.user_model'), 'author_id');
	}

	public function posts()
	{
		return $this->hasMany('\Riari\Forum\Models\Post', 'parent_thread')->orderBy('created_at', 'desc');
	}

	public function getLastPageAttribute()
	{
		return $this->posts()->paginate(Config::get('forum::integration.posts_per_thread'))->getLastPage();
	}

	public function getLastPostAttribute()
	{
		return $this->posts->first();
	}

	public function getLastPostURLAttribute()
	{
		return $this->URL . '?page=' . $this->lastPage . '#post-' . $this->lastPost->id;
	}

	public function getLastPostTimeAttribute()
	{
		return $this->lastPost->created_at;
	}

	private function getURLComponents()
	{
		$components = array(
			'categoryID'		=> $this->category->id,
			'categoryAlias'	=> Str::slug($this->category->title, '-'),
			'threadID'			=> $this->id,
			'threadAlias'		=> Str::slug($this->title, '-')
		);

		return $components;
	}

	public function getURLAttribute()
	{
		return route('forum.get.view.thread', $this->getURLComponents());
	}

	public function getReplyURLAttribute()
	{
		return route('forum.get.reply.thread', $this->getURLComponents());
	}

	public function getDeleteURLAttribute()
	{
		return route('forum.get.delete.thread', $this->getURLComponents());
	}

	public function getCanPostAttribute()
	{
		return AccessControl::check($this, 'reply_to_thread', FALSE);
	}

	public function getCanDeleteAttribute()
	{
		return AccessControl::check($this, 'delete_threads', FALSE);
	}

}