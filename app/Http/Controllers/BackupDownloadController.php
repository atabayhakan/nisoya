<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Yedek .zip dosyasını indirir. Yedekler web'e kapalı storage/app/backups
 * altında olduğu için indirme yalnızca bu admin-korumalı route üzerinden yapılır
 * (route grubu: auth + active.user + admin.role — bkz. routes/web.php).
 *
 * Büyük dosyalar bellek dostu olsun diye Livewire yerine düz GET indirme
 * kullanılır (BinaryFileResponse dosyayı akıtarak gönderir).
 */
class BackupDownloadController extends Controller
{
    public function __invoke(string $name, BackupService $backup): BinaryFileResponse
    {
        $path = $backup->path($name);

        abort_if($path === null, 404, 'Yedek bulunamadı.');

        return response()->download($path);
    }
}
