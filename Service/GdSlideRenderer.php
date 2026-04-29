<?php

namespace JavidFazaeli\SocialPoster\Service;

class GdSlideRenderer
{
    private string $boldFont;
    private string $regularFont;
    private int $canvasSize = 1080;
    private int $margin     = 80;

    // Runtime palette — overridden per render by config
    private array $colorBg      = [0x1E, 0x1E, 0x1E];
    private array $colorText    = [0xF5, 0xF5, 0xF5];
    private array $colorAccent  = [0x8D, 0xB4, 0x8E];
    private array $colorMuted   = [0xAA, 0xAA, 0xAA];
    private array $colorDivider = [0x40, 0x60, 0x42];

    // Runtime sizes — overridden per render by config
    private int $titleSize   = 58;
    private int $headingSize = 46;
    private int $bulletSize  = 28;

    public function __construct()
    {
        $liberation = '/usr/share/fonts/truetype/liberation/LiberationSans';
        $dejavu     = '/usr/share/fonts/truetype/dejavu/DejaVuSans';

        if (is_readable($liberation . '-Bold.ttf')) {
            $this->boldFont    = $liberation . '-Bold.ttf';
            $this->regularFont = $liberation . '-Regular.ttf';
        } else {
            $this->boldFont    = $dejavu . '-Bold.ttf';
            $this->regularFont = $dejavu . '.ttf';
        }
    }

    public function renderToBase64(array $slide, array $config = []): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new \RuntimeException('PHP GD extension is not available.');
        }

        // Apply config — override defaults for this render
        $this->colorBg     = $this->parseHexColor((string) ($config['bg_color'] ?? ''), [0x1E, 0x1E, 0x1E]);
        $this->colorAccent = $this->parseHexColor((string) ($config['accent_color'] ?? ''), [0x8D, 0xB4, 0x8E]);
        $this->colorText   = $this->parseHexColor((string) ($config['text_color'] ?? ''), [0xF5, 0xF5, 0xF5]);
        $this->colorDivider = array_map(fn(int $c) => (int) max(0, $c * 0.45), $this->colorAccent);
        $this->titleSize   = $this->clampSize((int) ($config['title_size'] ?? 0), 58, 24, 120);
        $this->headingSize = $this->clampSize((int) ($config['heading_size'] ?? 0), 46, 18, 96);
        $this->bulletSize  = $this->clampSize((int) ($config['bullet_size'] ?? 0), 28, 14, 72);

        $image = imagecreatetruecolor($this->canvasSize, $this->canvasSize);
        if ($image === false) {
            throw new \RuntimeException('Failed to create GD image canvas.');
        }

        imagealphablending($image, true);
        imagesavealpha($image, false);

        $this->drawBackground($image);

        $slideNumber = (int) ($slide['slide_number'] ?? 0);
        $type        = strtolower(trim((string) ($slide['type'] ?? '')));
        $isCover     = $slideNumber === 1 || $type === 'cover';

        if ($isCover) {
            $this->drawCoverSlide($image, $slide);
        } else {
            $this->drawContentSlide($image, $slide, $slideNumber);
        }

        ob_start();
        imagepng($image, null, 6);
        $data = ob_get_clean();
        imagedestroy($image);

        return base64_encode((string) $data);
    }

    private function drawBackground($image): void
    {
        $bg  = $this->alloc($image, $this->colorBg);
        imagefilledrectangle($image, 0, 0, $this->canvasSize - 1, $this->canvasSize - 1, $bg);

        $bar = $this->alloc($image, $this->colorAccent);
        imagefilledrectangle($image, 0, 0, $this->canvasSize - 1, 6, $bar);
    }

    private function drawCoverSlide($image, array $slide): void
    {
        $textColor   = $this->alloc($image, $this->colorText);
        $accentColor = $this->alloc($image, $this->colorAccent);

        $title    = trim((string) ($slide['title'] ?? 'Slide'));
        $subtitle = trim((string) ($slide['subtitle'] ?? ''));

        $maxW = $this->canvasSize - ($this->margin * 2);

        $titleLines  = $this->wrapText($this->boldFont, $this->titleSize, $title, $maxW);
        $titleLineH  = $this->lineHeight($this->titleSize);
        $titleBlockH = count($titleLines) * $titleLineH;

        $subSize    = max(14, (int) ($this->titleSize * 0.55));
        $subLines   = $subtitle !== ''
            ? $this->wrapText($this->regularFont, $subSize, $subtitle, $maxW)
            : [];
        $subLineH   = $this->lineHeight($subSize);
        $subBlockH  = count($subLines) * $subLineH;

        $gap    = $subLines ? 36 : 0;
        $totalH = $titleBlockH + $gap + $subBlockH;

        $ascender = (int) ($this->titleSize * 0.8);
        $startY   = (int) (($this->canvasSize - $totalH) / 2) + $ascender;

        foreach ($titleLines as $i => $line) {
            $y = $startY + ($i * $titleLineH);
            $x = $this->centerX($this->boldFont, $this->titleSize, $line);
            imagettftext($image, $this->titleSize, 0, $x, $y, $textColor, $this->boldFont, $line);
        }

        if ($subLines) {
            $subAscender = (int) ($subSize * 0.8);
            $subStartY   = $startY + $titleBlockH + $gap - $titleLineH + $subAscender + $titleLineH;
            foreach ($subLines as $i => $line) {
                $y = $subStartY + ($i * $subLineH);
                $x = $this->centerX($this->regularFont, $subSize, $line);
                imagettftext($image, $subSize, 0, $x, $y, $accentColor, $this->regularFont, $line);
            }
        }
    }

    private function drawContentSlide($image, array $slide, int $slideNumber): void
    {
        $textColor   = $this->alloc($image, $this->colorText);
        $accentColor = $this->alloc($image, $this->colorAccent);
        $mutedColor  = $this->alloc($image, $this->colorMuted);
        $divColor    = $this->alloc($image, $this->colorDivider);

        $heading = trim((string) ($slide['heading'] ?? $slide['title'] ?? ''));
        if ($heading === '') {
            $heading = 'Slide ' . $slideNumber;
        }

        $bullets = array_values(array_filter(
            array_map('trim', (array) ($slide['bullets'] ?? []))
        ));

        $maxW = $this->canvasSize - ($this->margin * 2);

        // Slide number badge — top right
        $numSize = 20;
        $numText = (string) $slideNumber;
        $numBox  = imagettfbbox($numSize, 0, $this->regularFont, $numText);
        $numW    = abs($numBox[4] - $numBox[0]);
        $numX    = $this->canvasSize - $this->margin - $numW;
        $numY    = $this->margin + 20;
        imagettftext($image, $numSize, 0, $numX, $numY, $mutedColor, $this->regularFont, $numText);

        // Heading
        $headingLines = $this->wrapText($this->boldFont, $this->headingSize, $heading, $maxW);
        $headingLineH = $this->lineHeight($this->headingSize);
        $headAscender = (int) ($this->headingSize * 0.8);

        $y = $this->margin + 80 + $headAscender;
        foreach ($headingLines as $line) {
            $x = $this->centerX($this->boldFont, $this->headingSize, $line);
            imagettftext($image, $this->headingSize, 0, $x, $y, $accentColor, $this->boldFont, $line);
            $y += $headingLineH;
        }

        // Divider
        $y += 18;
        imagefilledrectangle($image, $this->margin, $y, $this->canvasSize - $this->margin, $y + 2, $divColor);
        $y += 28;

        // Bullets
        if ($bullets) {
            $bulletLineH = $this->lineHeight($this->bulletSize);
            $bulletMaxW  = $maxW - 36;
            $bulletAsc   = (int) ($this->bulletSize * 0.8);

            foreach (array_slice($bullets, 0, 5) as $bullet) {
                $bLines = $this->wrapText($this->regularFont, $this->bulletSize, $bullet, $bulletMaxW);

                $dotY = $y + (int) ($bulletAsc * 0.55);
                imagefilledellipse($image, $this->margin + 8, $dotY, 9, 9, $accentColor);

                foreach ($bLines as $bLine) {
                    imagettftext($image, $this->bulletSize, 0, $this->margin + 28, $y + $bulletAsc, $textColor, $this->regularFont, $bLine);
                    $y += $bulletLineH;
                }
                $y += 10;

                if ($y > ($this->canvasSize - $this->margin)) {
                    break;
                }
            }
        }
    }

    private function parseHexColor(string $hex, array $fallback): array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3 && ctype_xdigit($hex)) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (strlen($hex) === 6 && ctype_xdigit($hex)) {
            return [
                hexdec(substr($hex, 0, 2)),
                hexdec(substr($hex, 2, 2)),
                hexdec(substr($hex, 4, 2)),
            ];
        }

        return $fallback;
    }

    private function clampSize(int $value, int $default, int $min, int $max): int
    {
        if ($value < $min || $value > $max) {
            return $default;
        }
        return $value;
    }

    private function alloc($image, array $rgb)
    {
        return imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
    }

    private function centerX(string $font, int $size, string $text): int
    {
        $box = imagettfbbox($size, 0, $font, $text);
        $w   = abs($box[4] - $box[0]);
        return (int) (($this->canvasSize - $w) / 2);
    }

    private function wrapText(string $font, int $size, string $text, int $maxWidth): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $line  = '';

        foreach ($words as $word) {
            $test = $line === '' ? $word : $line . ' ' . $word;
            $box  = imagettfbbox($size, 0, $font, $test);
            $w    = abs($box[4] - $box[0]);
            if ($w > $maxWidth && $line !== '') {
                $lines[] = $line;
                $line    = $word;
            } else {
                $line = $test;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines ?: [''];
    }

    private function lineHeight(int $fontSize): int
    {
        return (int) ($fontSize * 1.38);
    }
}
