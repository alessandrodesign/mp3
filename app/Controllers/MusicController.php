<?php

namespace App\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\AudioStreamService;
use App\Services\MusicService;
use Core\Routing\Route;
use Exception;

class MusicController extends Controller
{
    private string $tokenName = 'X-Player-Token';
    private string $token;

    public function __construct(
        protected MusicService       $musicService,
        protected AudioStreamService $audioStreamService
    )
    {
        $this->token = hash_hmac('sha256', 'music_stream', SECRET_KEY);
    }

    /**
     * @param Request $request
     * @param string|null $music
     * @return Response
     * @throws Exception
     */
    #[Route('/player/{music}', 'GET', 'music.player')]
    public function player(Request $request, ?string $music = null): Response
    {
        $mimeType = $this->musicService->get($music, true);
        return $this->view('music.player', compact('request', 'music', 'mimeType'));
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route('/playlist', 'GET', 'music.playlist')]
    public function playlist(Request $request): Response
    {
        $musicService = $this->musicService;
        $musics = json_encode(array_map(function ($music) use ($musicService) {
            return [
                'title' => $musicService->removeExtension($music['original']),
                'artist' => 'Artist B',
                'src' => '/music/listen/' . $music['encrypted'],
                'cover' => 'https://picsum.photos/300/300/?blur',
                'lyrics' => ''
            ];
        }, $this->musicService->list()), JSON_PRETTY_PRINT);

        $token = $this->token;
        $tokenName = $this->tokenName;

        return $this->view('music.playlist', compact('musics', 'token', 'tokenName'));
    }

    /**
     * @param Request $request
     * @param string|null $music
     * @return Response
     * @throws Exception
     */
    #[Route('/music/listen/{music}', 'GET', 'music.listen')]
    public function listen(Request $request, ?string $music = null): Response
    {
        $playerToken = $request->query->get('token');

        if ($playerToken !== $this->token) {
            $this->fail('Unauthorized access', 403);
        }

        $filePath = $this->musicService->get($music);

        if (!$filePath || !file_exists($filePath)) {
            $this->fail('Music not found', 404);
        }

        return $this->audioStreamService->stream($filePath, $request);
    }
}