<?php

namespace GameProvider\Operator\Multi\Parser;

class GS extends ParserBase
{

    const KEY = 'gs';

    public function html(): string
    {
        $content = (array) $this->content;
        $type    = $content['type_id'];
        $play    = $content['play_id'];
        $betContent = json_decode($content['content'], true);
        if (is_array($betContent)) {
            $betContent = implode('<br>', $this->getBetContent($betContent));
        } else {
            $betContent = $content['content'];
        }
        return view('report.lotto_gs', [
            'type'    => self::trans(self::KEY, "type.$type"),
            'play'    => self::trans(self::KEY, "play.$play"),
            'content' => $betContent,
        ])
        ->render();
    }

    public function getBetContent(array $content)
    {
        foreach ($content as &$val) {
            if (is_array($val)) {
                $val = implode(', ', $val);
            }
        }
        return $content;
    }

}
