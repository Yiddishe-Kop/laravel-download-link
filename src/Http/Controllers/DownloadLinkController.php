<?php

namespace Armancodes\DownloadLink\Http\Controllers;

use Armancodes\DownloadLink\Models\DownloadLink;
use Armancodes\DownloadLink\Models\DownloadLinkIpAddress;
use Illuminate\Support\Facades\Storage;

class DownloadLinkController
{
    public function download($link)
    {
        $downloadLink = $this->findDownloadLink($link);

        $this->fileExists($downloadLink);

        $this->isAuthorized($downloadLink);

        $this->isAuthenticated($downloadLink);

        $this->isGuest($downloadLink);

        $this->isExpired($downloadLink);

        $this->ipIsAllowed($downloadLink);

        return response()->download(Storage::disk($downloadLink->disk)->path($downloadLink->file_path), $downloadLink->file_name);
    }

    private function fileExists($downloadLink)
    {
        abort_unless(Storage::disk($downloadLink->disk)->exists($downloadLink->file_path), 404, 'File not found!');
    }

    private function isAuthorized($downloadLink)
    {
        if ($user_id = $downloadLink->user_id) {
          abort_unless(auth()->check() && auth()->id() == $user_id, 401);
        }

        return true;
    }

    private function isAuthenticated($downloadLink)
    {
        if (! $downloadLink->auth_only) {
            return;
        }

        abort_unless(auth()->check(), 401);
    }

    private function isGuest($downloadLink)
    {
        if (! $downloadLink->guest_only) {
            return;
        }

        abort_if(auth()->check(), 403);
    }

    private function isExpired($downloadLink)
    {
        if (! $downloadLink->expire_time) {
            return;
        }

        abort_if(now() > $downloadLink->expire_time, 403, 'Download link is expired!');
    }

    private function ipIsAllowed($downloadLink)
    {
        $downloadLinkIps = DownloadLinkIpAddress::where('download_link_id', $downloadLink->id)->get();

        $allowedIps = $downloadLinkIps->where('allowed', true);

        $limitedIps = $downloadLinkIps->where('allowed', false);

        if ($allowedIps->isNotEmpty()) {
            abort_if($allowedIps->where('ip_address', request()->ip())->isEmpty(), 403);
        }

        if ($limitedIps->isNotEmpty()) {
            abort_if($limitedIps->where('ip_address', request()->ip())->isNotEmpty(), 403);
        }
    }

    private function findDownloadLink($link)
    {
        return DownloadLink::where('link', $link)->firstOrFail();
    }
}
