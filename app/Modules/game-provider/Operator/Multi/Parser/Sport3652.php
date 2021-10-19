<?php

namespace GameProvider\Operator\Multi\Parser;

class Sport3652 extends ParserBase
{


    public function html(): string
    {
        return str_replace('font', 'span', trim($this->content, '"'));
    }

}
