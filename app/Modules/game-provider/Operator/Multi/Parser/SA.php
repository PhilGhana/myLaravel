<?php

namespace GameProvider\Operator\Multi\Parser;

class SA extends ParserBase
{

    const KEY = 'sa';

    const TYPE_RESULT_MAP = [
        'bac'   => 'BaccaratResult',
        'dtx'   => 'DragonTigerResult',
        'sicbo' => 'SicboResult',
        'ftan'  => 'FantanResult',
        'rot'   => 'RouletteResult',
        'moneywheel' => 'MoneyWheelResult',
    ];

    public function html(): string
    {
        $content   = (array) $this->content;
        $gameType  = $content['GameType'];
        $betType   = $content['BetType'];
        $resultKey = self::TYPE_RESULT_MAP[$gameType];
        return view('report.live', [
            'game'    => self::trans(self::KEY, "GameType.$gameType"),
            'content' => self::trans(self::KEY, "BetType.$gameType.$betType"),
            'result'  => implode('<br>', $this->getResult($gameType, $content['GameResult'][$resultKey])),
        ])
        ->render();
    }

    protected function getResult(string $type, array $result)
    {
        $list = [];
        switch ($type) {
            case 'bac':
                $list = $this->getBacResult($result);
                break;
            case 'dtx':
                $list = $this->getDtxResult($result);
                break;
            case 'sicbo':
                $list = $this->getSicboResult($result);
                break;
            case 'ftan': case 'rot':
                $list = $this->getPointResult($result);
                break;
            case 'moneywheel':
                $list = $this->getMoneyWheelDetail($result);
                break;
        }
        if (isset($result['ResultDetail'])) {
            $detail = implode(', ', $this->getResultDetails($type, $result['ResultDetail']));
            $list[] = self::trans('com', 'result_detail') . ': ' . $detail;
        }

        return $list;
    }

    protected function getBacResult(array $data)
    {
        $self = $this;
        $mapFunc  = function ($key) use ($data, &$self) {
            $item = $data[$key] ?? null;
            if (!$item) {
                return '';
            }
            return $self->cardTrans($item);
        };

        $pKeys = array_map($mapFunc, [ 'PlayerCard1', 'PlayerCard2', 'PlayerCard3' ]);
        $bKeys = array_map($mapFunc, [ 'BankerCard1', 'BankerCard2', 'BankerCard3' ]);

        return [
            self::trans('com', 'player') . ': ' . implode(', ', $pKeys),
            self::trans('com', 'banker') . ': ' . implode(', ', $bKeys),
        ];
    }

    protected function getDtxResult(array $data)
    {
        return [
            self::trans('com', 'dragon') . ': ' . $this->cardTrans($data['DragonCard']),
            self::trans('com', 'tiger')  . ': ' . $this->cardTrans($data['TigerCard']),
        ];
    }

    protected function getSicboResult(array $data)
    {
        return [
            $data['Dice1'] . ', ' . $data['Dice2'] . ', ' . $data['Dice3'] . ' = ' . $data['TotalPoint'],
        ];
    }

    protected function getPointResult(array $data)
    {
        return [ $data['Point'] ];
    }

    protected function getMoneyWheelDetail(array $data)
    {
        return [
            'Main: ' . self::trans(self::KEY, 'Result.moneywheel.MWRMain.' . $data['Main']),
            'Side: ' . self::trans(self::KEY, 'Result.moneywheel.MWRMain.' . $data['Side']),
        ];
    }

    protected function getResultDetails(string $type, array $details)
    {
        $result = [];
        foreach ($details as $key => $val) {
            $text = '';
            if (is_bool($val)) {
                $val = (bool) $val;
                if (!$val) {
                    continue;
                }
                $text = $key;
            } else {
                $text = $key . '_' . $val;
            }
            $result[] = self::trans(self::KEY, "Result.$type.$text");
        }
        return $result;
    }

    protected function cardTrans(array $card)
    {
        $rank = $card['Rank'];
        if ($rank == 1) {
            $rank = 'A';
        } else if ($rank == 11) {
            $rank = 'J';
        } else if ($rank == 12) {
            $rank = 'Q';
        } else if ($rank == 13) {
            $rank = 'K';
        }
        return self::trans('com', "card_suit.{$card['Suit']}") . $rank;
    }
}
