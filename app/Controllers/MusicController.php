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
    #[Route('/music/playlist', 'GET', 'music.playlist')]
    public function playlist(Request $request): Response
    {
        $musicService = $this->musicService;
        $musics = json_encode(array_map(function ($music) use ($musicService) {
            return [
                'title' => $musicService->removeExtension($music['original']),
                'artist' => 'Alessandro Souza',
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
        $filePath = $this->musicService->get($music);

        if (!$filePath || !file_exists($filePath)) {
            $this->fail('Music not found', 404);
        }

        return $this->audioStreamService->stream($filePath, $request);
    }

    #[Route('/music/proxy', 'GET', 'music.proxy')]
    public function proxy()
    {
        header('Content-Type: application/json');

        $artista = $_GET['artista'] ?? '';
        $musica = $_GET['musica'] ?? '';

        if (!$artista || !$musica) {
            echo json_encode(['erro' => 'Parâmetros inválidos']);
            exit;
        }

        $search = urlencode("$musica $artista site:letras.mus.br");
        $html = file_get_contents("https://www.google.com/search?q=$search");

        if (preg_match('/https:\/\/www\.letras\.mus\.br\/[^\s"]+/', $html, $match)) {
            $url = html_entity_decode($match[0]);
            $letraHtml = @file_get_contents($url);
            if ($letraHtml && preg_match('/<div class="cnt-letra p402_premium">(.+?)<\/div>/s', $letraHtml, $letra)) {
                $text = strip_tags($letra[1]);
                echo json_encode(['letra' => trim($text)]);
                exit;
            }
        }

        echo json_encode(['letra' => null, 'erro' => 'Não encontrado']);
    }
}