<?php

namespace JavidFazaeli\SocialPoster\ControlPanel\Routes;

trait LoadsStyle
{
    private function loadStyle(): void
    {
        $path = PATH_THIRD . 'socialposter/views/css/style.css';
        if (is_file($path)) {
            ee()->cp->add_to_head('<style>' . file_get_contents($path) . '</style>');
        }
    }
}
