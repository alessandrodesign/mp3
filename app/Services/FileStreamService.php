<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mime\MimeTypes;

class FileStreamService
{
    /**
     * Gera uma resposta de streaming para o arquivo de áudio com suporte a Range Requests.
     *
     * @param string $filePath
     * @param Request $request
     * @param bool $download
     * @return Response
     */
    public function stream(string $filePath, Request $request, bool $download = false): Response
    {
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

        if ($download) {
            $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"');
        }

        return $response;
    }
}