<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_id', 'visitor_id', 'session_id', 'event_type', 'path', 'path_hash',
        'route_name', 'page_type', 'content_id', 'locale', 'title', 'landing_path',
        'referrer_url', 'referrer_host', 'source', 'medium', 'campaign', 'channel',
        'target_url', 'device_type', 'browser', 'operating_system', 'country_code',
        'city', 'screen_width', 'screen_height', 'client_language', 'timezone',
        'ip_hash', 'user_agent', 'occurred_at', 'created_at',
    ];

    protected $casts = [
        'content_id' => 'integer',
        'screen_width' => 'integer',
        'screen_height' => 'integer',
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function scopePageViews(Builder $query): Builder
    {
        return $query->where('event_type', 'page_view');
    }
}
