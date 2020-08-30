<?php

namespace Armancodes\DownloadLink\Models;

use Illuminate\Database\Eloquent\Model;

class DownloadLinkUser extends Model
{
    protected $guarded = ['id'];

    protected $table = "download_link_users";

    public $timestamps = false;

    public function downloadLink()
    {
        return $this->belongsTo(DownloadLink::class);
    }
}
