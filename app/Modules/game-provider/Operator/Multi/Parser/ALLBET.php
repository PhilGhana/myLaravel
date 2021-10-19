<?php

namespace GameProvider\Operator\Multi\Parser;

class ALLBET extends ParserBase
{

    const KEY = 'allbet';

    const GAME_MAP = [
        '101' => 'bac',
        '102' => 'bac',
        '103' => 'bac',
        '104' => 'bac',
        '201' => 'hilo',
        '301' => 'dtx',
        '401' => 'rou',
        '501' => 'boding',
        '801' => 'bull',
        '901' => 'jinhua',
    ];

    public function html(): string
    {
        $content  = (array) $this->content;
        $gameType = $content['gameType'];
        $betType  = $content['betType'];

        return view('report.live', [
            'game'    => self::trans(self::KEY, "gameType.$gameType"),
            'content' => self::trans(self::KEY, "betType.$betType"),
            'result'  => implode('<br>', $this->getResult($gameType, $content['gameResult'])),
        ])
        ->render();
    }

    public function getResult(string $type, string $input)
    {
        $matches = [];
        preg_match_all('/{[0-9, -]+}/', $input, $matches);

        $matches = array_map(function ($str) {
            return explode(',', preg_replace('/[{} ]/', '', $str));
        }, $matches[0]);
        switch (self::GAME_MAP[$type] ?? null) {
            case 'bac':
                return $this->getMultiResult($matches, [
                    self::trans('com', 'player'),
                    self::trans('com', 'banker'),
                ]);
            case 'hilo': case 'rou':
                return $this->getSingleResult($matches);
            case 'dtx':
                return $this->getMultiResult($matches, [
                    self::trans('com', 'dragon'),
                    self::trans('com', 'tiger'),
                ]);
            case 'boding':
                return $this->getMultiResult($matches, [
                    self::trans('com', 'banker'),
                    self::trans('com', 'player') . '1',
                    self::trans('com', 'player') . '2',
                    self::trans('com', 'player') . '3',
                    self::trans('com', 'player') . '4',
                    self::trans('com', 'player') . '5',
                ]);
            case 'bull':
                return $this->getMultiResult($matches, [
                    'No1',
                    self::trans('com', 'banker'),
                    self::trans('com', 'player') . '1',
                    self::trans('com', 'player') . '2',
                    self::trans('com', 'player') . '3',
                ]);
            case 'jinhua':
                return $this->getMultiResult($matches, [
                    self::trans('com', 'dragon'),
                    self::trans('com', 'phoenix'),
                ]);
        }
        return [];
    }

    protected function getSingleResult(array $list)
    {
        return [ implode(', ', $list[0]) ];
    }

    protected function getMultiResult(array $list, array $orders)
    {
        $results = [];
        foreach ($orders as $idx => $str) {
            if (!isset($list[$idx])) {
                continue;
            }
            $results[] = $str . ': ' . $this->getCardsStr($list[$idx]);
        }
        return $results;
    }

    protected function getCardsStr(array $cards, string $split = ', ')
    {
        foreach ($cards as $idx => $card) {
            $cards[$idx] = $this->getCard($card);
        }
        return implode($split, $cards);
    }

    protected function getCard($num)
    {
        $num = (int) $num;
        if ($num == -1) {
            return 'N/A';
        }
        if ($num < 101) {
            return $num;
        }
        $suit = (int) floor($num / 100);
        $rank = $num % 100;
        if ($rank == 1) {
            $rank = 'A';
        } else if ($rank == 11) {
            $rank = 'J';
        } else if ($rank == 12) {
            $rank = 'Q';
        } else if ($rank == 13) {
            $rank = 'K';
        }
        return self::trans('com', "card_suit.$suit") . '-' . $rank;
    }

}
