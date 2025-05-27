<?php

namespace App\Controllers;

use App\Services\FileStreamService;
use Exception;
use Core\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FileController extends Controller
{
    protected string $baseDir;

    /**
     * @throws Exception
     */
    public function __construct(protected FileStreamService $fileStreamService)
    {
        $this->baseDir = realpath(PATH_STORAGE . "uploads");
        if ($this->baseDir === false) {
            $this->fail('Storage directory not found', 500);
        }
    }

    /**
     * @param Request $request
     * @param string $path
     * @return Response
     * @throws Exception
     */
    #[Route('/files/{path}', 'GET', 'file.serve')]
    public function serve(Request $request, string $path): Response
    {
        $filePath = realpath($this->baseDir . DS . $path);

        if ($filePath === false || !is_file($filePath) || !str_starts_with($filePath, $this->baseDir)) {
            $this->fail('File not found', 404);
        }

        return $this->fileStreamService->stream($filePath, $request);
    }

    /**
     * @param Request $request
     * @param string $path
     * @return Response
     * @throws Exception
     */
    #[Route('/download/{path}', 'GET', 'file.download')]
    public function download(Request $request, string $path): Response
    {
        $filePath = realpath($this->baseDir . DS . $path);

        if ($filePath === false || !is_file($filePath) || !str_starts_with($filePath, $this->baseDir)) {
            $this->fail('File not found', 404);
        }

        return $this->fileStreamService->stream($filePath, $request, true);
    }
}