<?php

namespace App\Controllers;

use Exception;
use Core\Routing\Route;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    protected string $baseDir;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->baseDir = realpath(PATH_STORAGE . "uploads");
        if ($this->baseDir === false) {
            throw new Exception('Storage directory not found', 500);
        }
    }

    #[Route('/files/{path}', 'GET', 'file.serve')]
    public function serve(Request $request, string $path): Response
    {
        $filePath = realpath($this->baseDir . DS . $path);

        if ($filePath === false || !is_file($filePath) || !str_starts_with($filePath, $this->baseDir)) {
            return new Response('File not found', 404);
        }

        $fileSize = filesize($filePath);
        $lastModified = filemtime($filePath);
        $etag = md5_file($filePath);
        $mimeTypes = MimeTypes::getDefault();
        $contentType = $mimeTypes->guessMimeType($filePath) ?: 'application/octet-stream';

        $headers = [
            'Content-Type' => $contentType,
            'Content-Length' => $fileSize,
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
            'ETag' => $etag,
            'Cache-Control' => 'public, max-age=31536000',
            'Expires' => gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT',
        ];

        $ifNoneMatch = $request->headers->get('If-None-Match');
        $ifModifiedSince = $request->headers->get('If-Modified-Since');

        if (($ifNoneMatch && $ifNoneMatch === $etag) ||
            ($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModified)) {
            return new Response('', 304, $headers);
        }

        $response = new StreamedResponse(function () use ($filePath) {
            $chunkSize = 1024 * 1024; // 1MB por vez
            $handle = fopen($filePath, 'rb');
            if ($handle === false) {
                return;
            }
            while (!feof($handle)) {
                echo fread($handle, $chunkSize);
                flush();
            }
            fclose($handle);
        }, 200, $headers);

        // Força download (opcional)
        // $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"');

        return $response;
    }
}