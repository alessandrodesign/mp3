<?php
namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\MimeTypes;

class AudioStreamService
{
    /**
     * Gera uma resposta de streaming para o arquivo de áudio com suporte a Range Requests.
     *
     * @param string $filePath
     * @param Request $request
     * @return Response
     */
    public function stream(string $filePath, Request $request): Response
    {
        $fileSize = filesize($filePath);
        $start = 0;
        $end = $fileSize - 1;
        $status = 200;

        $rangeHeader = $request->headers->get('Range');
        if ($rangeHeader && preg_match('/bytes=(\d+)-(\d*)/', $rangeHeader, $matches)) {
            $start = (int)$matches[1];
            if (!empty($matches[2])) {
                $end = (int)$matches[2];
            }

            if ($start > $end || $start >= $fileSize) {
                return new Response('', 416, [
                    'Content-Range' => "bytes */$fileSize"
                ]);
            }

            $status = 206;
        }

        $length = $end - $start + 1;

        $response = new StreamedResponse(function () use ($filePath, $start, $end) {
            $handle = fopen($filePath, 'rb');
            fseek($handle, $start);

            $bufferSize = 8192;
            $remaining = $end - $start + 1;

            while (!feof($handle) && $remaining > 0) {
                $readLength = min($bufferSize, $remaining);
                echo fread($handle, $readLength);
                flush();
                $remaining -= $readLength;
            }

            fclose($handle);
        }, $status);

        $mimeTypes = MimeTypes::getDefault();
        $contentType = $mimeTypes->guessMimeType($filePath) ?: 'audio/*';

        $headers = [
            'Content-Type' => $contentType,
            'Accept-Ranges' => 'bytes',
            'Content-Length' => $length,
            'Cache-Control' => 'public, max-age=86400',
            'Expires' => gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT',
        ];

        if ($status === 206) {
            $headers['Content-Range'] = "bytes $start-$end/$fileSize";
        }

        $response->headers->add($headers);

        return $response;
    }
}