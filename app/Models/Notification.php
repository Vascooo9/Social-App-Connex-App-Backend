<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Notification — notifikasi untuk user.
 * Type: like | follow | comment | repost
 */
class Notification extends Model
{
    protected $fillable = [
        'user_id', 'actor_id', 'type', 'post_id', 'comment_id', 'is_read',
    ];

    protected $casts = ['is_read' => 'boolean'];

    /** User penerima notifikasi */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** User yang memicu notifikasi */
    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** Postingan terkait notifikasi */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /** Komentar terkait notifikasi */
    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }
}
