<?php

namespace App\Controllers;

use App\Models\StreamKey;
use App\Models\StreamLog;
use Core\Routing\Route;
use Core\Utils\Directories;
use Exception;
use PDO;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VideoController extends Controller
{
    private string $baseDir;

    public function __construct()
    {
        $baseDir = PATH_STORAGE . "uploads" . DS . "videos";
        Directories::validAndCreate($baseDir);
        $this->baseDir = realpath($baseDir);
    }

    #[Route('/video/recording', 'GET', 'video.recording')]
    public function recording(Request $request): Response
    {
        return $this->view('video.recording', compact('request'));
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/video/upload', 'POST', 'video.upload')]
    public function upload(Request $request): Response
    {
        try {
            /** @var UploadedFile $file */
            $file = $request->files->get('video');

            if ($file->getError() !== UPLOAD_ERR_OK) {
                $this->fail($file->getErrorMessage() ?? 'Upload error.', 400);
            }

            $allowed = ['webm', 'mp4', 'ogg'];
            $ext = $file->getClientOriginalExtension();
            if (!in_array(strtolower($ext), $allowed)) {
                $this->fail(sprintf('Video format not allowed. %s', $ext), 400);
            }

            $filename = uniqid('video_', true) . '.' . $ext;

            $file->move($this->baseDir, $filename);

            return new JsonResponse([
                'status' => 'success',
                'filename' => $filename,
            ]);
        } catch (Throwable $exception) {
            return new JsonResponse([
                'status' => 'error',
                'error' => $exception->getMessage(),
            ], $exception->getCode() ?: 400);
        }
    }

    /**
     * @param Request $request
     * @param string $path
     * @return Response
     */
    #[Route('/video/stream/{path}', 'GET', 'video.stream')]
    public function stream(Request $request, string $path): Response
    {
        $uploadDir = __DIR__ . '/videos/';

        if (!isset($_GET['file'])) {
            http_response_code(400);
            echo 'Arquivo não especificado.';
            exit;
        }

        $filename = basename($_GET['file']); // evita path traversal
        $filepath = $uploadDir . $filename;

        if (!file_exists($filepath)) {
            http_response_code(404);
            echo 'Arquivo não encontrado.';
            exit;
        }

// Detecta MIME type básico
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $mimeTypes = [
            'webm' => 'video/webm',
            'mp4' => 'video/mp4',
            'ogg' => 'video/ogg',
        ];
        $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';

        header('Content-Type: ' . $contentType);
        header('Content-Length: ' . filesize($filepath));
        header('Accept-Ranges: bytes');

// Suporte básico a Range Requests para streaming
        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
            list(, $range) = explode('=', $range, 2);
            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                exit;
            }
            if ($range == '-') {
                $start = filesize($filepath) - substr($range, 1);
                $end = filesize($filepath) - 1;
            } else {
                $range = explode('-', $range);
                $start = intval($range[0]);
                $end = (isset($range[1]) && is_numeric($range[1])) ? intval($range[1]) : filesize($filepath) - 1;
            }
            if ($start > $end || $end >= filesize($filepath)) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                exit;
            }
            $length = $end - $start + 1;
            $file = fopen($filepath, 'rb');
            fseek($file, $start);
            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes $start-$end/" . filesize($filepath));
            header("Content-Length: $length");
            $bufferSize = 8192;
            while (!feof($file) && ($pos = ftell($file)) <= $end) {
                if ($pos + $bufferSize > $end) {
                    $bufferSize = $end - $pos + 1;
                }
                echo fread($file, $bufferSize);
                flush();
            }
            fclose($file);
            exit;
        } else {
            // Sem Range, envia o arquivo completo
            readfile($filepath);
            exit;
        }
    }

    #[Route('/video/capture', 'GET', 'video.capture')]
    public function capture(Request $request): Response
    {
        return $this->view('video.capture', compact('request'));
    }

    #[Route('/video/watch', 'GET', 'video.capture')]
    public function watch(Request $request): Response
    {
        return $this->view('video.watch', compact('request'));
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/video/live/auth', 'POST', 'video.live.auth')]
    public function liveAuth(Request $request): Response
    {
        try {
            $pdo = new PDO('mysql:host=mysql;dbname=livestream', 'streamer', 'streamerpass');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $name = $request->get('name');

            $streamKey = StreamKey::firstWhere([
                ['stream_key', '=', $name],
                ['active', '=', 1],
            ]);

            if ($streamKey) {
                $streamLog = new StreamLog;
                $streamLog->stream_key = $streamKey->stream_key;
                $streamLog->action_name = 'start';
                $streamLog->source_ip = $request->getClientIp();
                $streamLog->save();

                return new Response('OK');
            }
        } catch (Exception $e) {
            return $this->fail($e->getMessage(), 400);
        }

        return $this->fail('Forbidden', 403);
    }

    #[Route('/video/live/painel', 'GET', 'video.live.painel')]
    public function painel(Request $request): Response
    {
        $chaves = StreamKey::all()->toArray();
        return $this->view('video.live.painel', compact('request', 'chaves'));
    }

    #[Route('/video/live/revogar', 'GET', 'video.live.revogar')]
    public function revogar(Request $request): Response
    {
        $id = $request->get('id', 0);
        $streamKey = StreamKey::find($id);
        $streamKey->active = 0;
        $streamKey->save();
        return $this->success("Revogado", $streamKey);
    }

    #[Route('/video/live/publish', 'GET', 'video.live.publish')]
    public function publish(Request $request): Response
    {
        return $this->view('video.live.publish', compact('request'));
    }

    #[Route('/video/live/watch', 'GET', 'video.live.watch')]
    public function liveWatch(Request $request): Response
    {
        return $this->view('video.live.watch', compact('request'));
    }

    #[Route('/video/live/upload', 'GET', 'video.live.upload')]
    public function liveUpload(Request $request): Response
    {
        $targetDir = '/tmp/record/';
        $targetFile = $targetDir . basename($_FILES['video']['name']);

        if (move_uploaded_file($_FILES['video']['tmp_name'], $targetFile)) {
            echo "Upload feito com sucesso.";
        } else {
            echo "Erro no upload.";
        }

    }
}