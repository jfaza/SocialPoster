<?php

namespace JavidFazaeli\SocialPoster\Service;

class ImageStorage
{
    public function saveBase64Png(string $base64, string $nameSeed, ?array $resize = null): string
    {
        $directory = $this->uploadDirectory();
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Could not create SocialPoster upload directory.');
        }

        $binary = base64_decode($base64, true);
        if ($binary === false || $binary === '') {
            throw new \RuntimeException('Generated image data was not valid base64.');
        }

        if (is_array($resize)) {
            $binary = $this->resizePngBinary($binary, (int) ($resize['width'] ?? 0), (int) ($resize['height'] ?? 0));
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

    private function resizePngBinary(string $binary, int $targetWidth, int $targetHeight): string
    {
        if ($targetWidth < 1 || $targetHeight < 1 || ! function_exists('imagecreatefromstring')) {
            return $binary;
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return $binary;
        }

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($canvas === false) {
            imagedestroy($source);
            return $binary;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        if ($sourceWidth < 1 || $sourceHeight < 1) {
            imagedestroy($source);
            imagedestroy($canvas);
            return $binary;
        }

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        ob_start();
        imagepng($canvas);
        $resized = (string) ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        return $resized !== '' ? $resized : $binary;
    }

    public function uploadDirectory(): string
    {
        return $this->publicPath('images/uploads/socialposter');
    }

    public function publicPath(string $relativePath): string
    {
        return $this->siteRootPath() . ltrim(str_replace('\\', '/', $relativePath), '/');
    }

    public function siteRootPath(): string
    {
        $candidates = [];

        $basePath = ee()->config->item('base_path');
        if (is_scalar($basePath) && trim((string) $basePath) !== '') {
            $candidates[] = (string) $basePath;
        }

        if (defined('SYSPATH')) {
            $candidates[] = dirname(rtrim(str_replace('\\', '/', SYSPATH), '/'));
        }

        if (defined('FCPATH')) {
            $candidates[] = FCPATH;
        }

        foreach ($candidates as $candidate) {
            $path = rtrim(str_replace('\\', '/', trim((string) $candidate)), '/') . '/';
            if ($path !== '/' && is_dir($path) && is_dir($path . 'images')) {
                return $path;
            }
        }

        return defined('FCPATH') ? rtrim(str_replace('\\', '/', FCPATH), '/') . '/' : '';
    }
}
