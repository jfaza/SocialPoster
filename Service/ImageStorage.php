<?php

namespace JavidFazaeli\SocialPoster\Service;

class ImageStorage
{
    public function saveBase64Png(string $base64, string $nameSeed): string
    {
        $directory = FCPATH . 'images/uploads/socialposter';
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Could not create SocialPoster upload directory.');
        }

        $binary = base64_decode($base64, true);
        if ($binary === false || $binary === '') {
            throw new \RuntimeException('Generated image data was not valid base64.');
        }

        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $nameSeed), '-'));
        $slug = $slug !== '' ? substr($slug, 0, 60) : 'social-post';
        $filename = $slug . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.png';
        $path = $directory . '/' . $filename;

        if (file_put_contents($path, $binary) === false) {
            throw new \RuntimeException('Could not write generated image file.');
        }

        return 'images/uploads/socialposter/' . $filename;
    }
}
