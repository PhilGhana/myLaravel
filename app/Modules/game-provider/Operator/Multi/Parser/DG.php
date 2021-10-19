<?php

namespace GameProvider\Operator\Multi\Parser;

class DG extends ParserBase
{

    const KEY = 'dg';

    const GAME_TYPES = [
        1 => 'bac',
        3 => 'dtx',
        4 => 'rot',
        5 => 'sicbo',
        6 => 'ftan',
        7 => 'bull',
        8 => 'bac',
        11 => 'jinhua',
        12 => 'sicbo',
        14 => 'dish',
        15 => 'seafood',
        51 => 'luck5',
        52 => 'luck10',
    ];

    public function html(): string
    {
        $content  = (array) $this->content;
        $type     = $content['GameId'];
        $gameCode = "{$content['GameType']}_{$type}";
        if ($content['TableId']) {
            $gameCode .= "_{$content['TableId']}";
        }

        return view('report.live', [
            'game'    => self::trans(self::KEY, "Game.$gameCode"),
            'content' => $this->getBetDetail($type, json_decode($content['betDetail'], true)),
            'result'  => $this->getResult($type, json_decode($content['result'], true)),
        ])
        ->render();
    }

    public function getBetDetail($type, $detail)
    {
        if (array_key_exists($type, self::GAME_TYPES)) {
            $type = self::GAME_TYPES[$type];
        }
        if (!is_array($detail)) {
            return (string) $detail;
        }
        $bets = [];
        foreach ($detail as $key => $item) {
            if (substr($key, -1) === 'W') {
                continue;
            }

            $str = self::trans(self::KEY, "betDetail.$type.$key") . ': ';
            if ($key == 'hasBid') {
                $str .= $item == 1 ? 'Y' : 'N';
            } else if (is_array($item)) {
                $list = [];
                foreach ($item as $num => $val) {
                    if (is_array($val)) {
                        $sublist = [];
                        foreach ($val as $nKey => $amount) {
                            $sublist[] = "#{$nKey} -> \${$amount}";
                        }
                        $list[] = "[{$num}] " . implode(', ', $sublist);
                    } else {
                        $list[] = "#{$num} -> \${$val}";
                    }
                }
                $str .= implode('; ', $list);
            } else {
                $str .= "\${$item}";
            }
            $bets[] = $str;
        }
        return implode('<br>', $bets);
    }

    public function getResult($type, $data)
    {
        if (!is_array($data)) {
            return (string) $data;
        }
        $result = $data['result'] ?? null;
        if (!$result) {
            return 'N/A';
        }
        if (!array_key_exists($type, self::GAME_TYPES)) {
            return $result;
        }
        $type = self::GAME_TYPES[$type];
        switch ($type) {
            case 'rot': case 'ftan':
                return $result;
            case 'sicbo':
                return implode(', ', str_split($result));
            case 'seafood':
                return $this->getSeafoodResult($result);
            case 'dish':
                return self::trans(self::KEY, "Result.dish.$result");
            case 'luck5':
                return $this->getOrderResult($result, 'ball_order');
            case 'luck10':
                return $this->getOrderResult($result, 'rank_order');
            case 'bac':
                return $this->getBacResult($result);
            case 'dtx':
                return $this->getDtxResult($result);
            case 'bull':
                return $this->getBullResult($result);
            case 'jinhua':
                return $this->getJinhuaResult($result);
        }
        return $result;
    }

    public function getSeafoodResult(string $result)
    {
        $list = str_split($result);
        foreach ($list as &$key) {
            $key = self::trans(self::KEY, "Result.seafood.$key");
        }
        return implode(', ', $list);
    }

    public function getOrderResult(string $result, string $transKey)
    {
        $list = explode(',', $result);
        foreach ($list as $idx => &$val) {
            $val = self::trans('com', $transKey, [ 'num' => $idx + 1 ]) . ': ' . $val;
        }
        return implode(', ', $list);
    }

    public function getJinhuaResult(string $result)
    {

        $self  = $this;
        $list  = explode(',', $result);
        $items = [
            function ($val) {
                return self::trans(self::KEY, "Result.jinhua.$val");
            },
            function ($val) {
                return self::trans(self::KEY, "Result.jinhua_card.$val");
            },
            function ($val) {
                return self::trans(self::KEY, "Result.jinhua_card.$val");
            },
            function ($val) use (&$self) {
                return $self->getCard($val);
            },
            function ($val) use (&$self) {
                return $self->getCard($val);
            },
            function ($val) {
                return self::trans(self::KEY, "Result.jinhua_card.$val");
            },
        ];
        $trKeys = [
            '',
            'kind_black',
            'kind_red',
            'max_black',
            'max_red',
            'kind_bonus',
        ];

        foreach ($items as $idx => &$func) {
            $func = $func($list[$idx]);
            if ($trKeys[$idx]) {
                $func = self::trans(self::KEY, 'info.' . $trKeys[$idx]) . ": $func";
            }
        }
        return implode('<br>', $items);
    }

    public function getDtxResult(string $result)
    {
        $self  = $this;
        $list  = explode(',', $result);
        $items = [
            function ($val) {
                return self::trans(self::KEY, "Result.dtx.$val");
            },
            function ($val) use (&$self) {
                return self::trans(self::KEY, 'info.point_win') . ': ' . $self->getCard($val);
            },
        ];
        foreach ($items as $idx => &$func) {
            $func = $func($list[$idx]);
        }
        return implode('<br>', $items);
    }

    public function getBacResult(string $result)
    {
        $items = [
            function ($val) {
                return self::trans(self::KEY, "Result.bac_win.$val");
            },
            function ($val) {
                return self::trans(self::KEY, "Result.bac.$val");
            },
            function ($val) {
                return self::trans(self::KEY, 'info.point_win') . ": $val";
            },
        ];
        $list = explode(',', $result);
        foreach ($items as $idx => &$func) {
            $func = $func($list[$idx]);
        }
        return implode('<br>', $items);
    }

    public function getBullResult(string $result)
    {
        $list  = explode('|', $result);
        $bulls = explode(',', $list[0] ?? '');
        $wins  = explode(',', $list[1] ?? '');

        foreach ($bulls as $idx => &$val) {
            $str = '';
            if (!$idx) {
                $str = self::trans('com', 'banker');
            } else {
                $str = self::trans('com', 'player') . $idx;
            }
            $val = $str . ': ' . ($val == 0 ? self::trans('com', 'no_bull') : $val);
        }
        foreach ($wins as $idx => &$val) {
            $val = self::trans('com', 'player') . ($idx + 1) . ': ' . ($val == 0 ? 'Lose' : 'Win');
        }
        return implode('<br>', [ implode(', ', $bulls), implode(', ', $wins) ]);
    }

    public function getCard($num)
    {
        if ($num == 11) {
            return 'J';
        } else if ($num == 12) {
            return 'Q';
        } else if ($num == 13) {
            return 'K';
        } else if ($num == 14 || $num == 1) {
            return 'A';
        } else if ($num == -1) {
            return self::trans('com', 'no_card');
        }
        return $num;
    }

}
