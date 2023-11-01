<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Output\Style;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class TitleGrayOutputStyle extends OutputFormatterStyle
{
    public const NAME = 'title-gray';

    public function __construct()
    {
        parent::__construct(
            'white',
            'gray',
            ['bold', 'blink']
        );
    }

}