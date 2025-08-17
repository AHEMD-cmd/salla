<?php

namespace App\Http\Controllers;

use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function download()
    {
        $basePath = base_path();
        dd($basePath);
        $tempFile = storage_path('app/temp_' . uniqid() . '.zip');
        
        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::CREATE) !== true) {
            abort(500, 'Could not create zip file');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $path = $item->getPathname();
            if ($path === $tempFile) {
                continue; // Skip the temporary zip file itself
            }
            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $path);
            if ($item->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($path, $relativePath);
            }
        }

        $zip->close();

        return response()->download($tempFile, 'project.zip')->deleteFileAfterSend(true);
    }
}