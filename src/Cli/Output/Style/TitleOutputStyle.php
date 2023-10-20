<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Output\Style;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class TitleOutputStyle extends OutputFormatterStyle
{
    public const NAME = 'title';

    public function __construct()
    {
        parent::__construct(
            'white',
            'blue',
            ['bold', 'blink']
        );
    }

}