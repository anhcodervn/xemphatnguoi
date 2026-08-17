<?php

namespace App\Http\Controllers;

use App\Support\PublicSeoTextFiles;
use Illuminate\Http\Response;

class PublicSeoFileController extends Controller
{
    public function __construct(private readonly PublicSeoTextFiles $textFiles) {}

    public function robots(): Response
    {
        return $this->plainTextResponse($this->textFiles->robots());
    }

    public function ads(): Response
    {
        return $this->plainTextResponse($this->textFiles->ads());
    }

    private function plainTextResponse(string $content): Response
    {
        return response($content)->withHeaders([
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
