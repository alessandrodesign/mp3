<?php

namespace App\Controllers;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller
{
    protected function view(string $view, array $data = []): Response
    {
        $view = str_replace('.', DS, $view);
        $viewPath = PATH_VIEWS . "$view.php";

        if (!is_readable($viewPath) || !file_exists($viewPath) || !is_file($viewPath)) {
            $this->fail('File not found: ' . $viewPath, 404);
        }

        extract($data);
        ob_start();
        include $viewPath;
        $content = ob_get_clean();
        $response = new Response();
        $response->setContent($content);
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'text/html');
        return $response;
    }

    /**
     * @throws Exception
     */
    protected function fail(string $message = "", int $code = Response::HTTP_BAD_REQUEST)
    {
        throw new Exception($message, $code);
    }

    protected function success(string $message = "", mixed $data = null, int $code = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse([
            'message' => $message,
            'statusCode' => $code,
            'status' => 'success',
            'data' => $data
        ], $code);
    }
}