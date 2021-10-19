<?php

namespace App\Validators\Letter;

use App\Rules\StringRegex;
use App\Validators\BaseValidator;
use App\Validators\ExpansionRules\IntegerArray;

class LetterAnnouncementValidator extends BaseValidator
{
    public static function checkAddAnnouncement($data)
    {
        $val = [
            'all'           => 'required|in:1,0',
            'tagId'         => 'required|exists:letter_tag,id',
            'title'         => ['required', 'string', 'max:50', new StringRegex],
            'content'       => ['required', 'string'],
            'members'       => [new IntegerArray],
            'clubs'         => [new IntegerArray, 'exists:club,id'],
            'clubRanks'     => [new IntegerArray, 'exists:club_rank,id'],
            'memberTags'    => [new IntegerArray, 'exists:member_tag,id'],
            'agents'        => [new IntegerArray, 'exists:agent,id'],
            'registerStart' => 'nullable|date_format:"Y-m-d H:i:s"',
            'registerEnd'   => 'nullable|date_format:"Y-m-d H:i:s"',
        ];

        if ($data['franchiseeId'] ?? '' !== 0) {
            $val['franchiseeId'] = 'required|exists:franchisee,id';
        }

        (new static($data, $val))->check();
    }

    public static function checkGetAnnouncementList($data)
    {
        (new static($data, [
            'title'         => ['nullable', 'string', 'max:50', new StringRegex],
            'memberAccount' => ['nullable', 'string', new StringRegex(StringRegex::TYPE_MEMBER_ACCOUNT)],
            // 'tagId'         => 'nullable|exists:letter_tag,id',
            'startTime'     => 'nullable|date_format:Y-m-d H:i:s',
            'endTime'       => 'nullable|date_format:Y-m-d H:i:s',
            'franchisee_id' => 'nullable|integer',
            'page'          => 'nullable|integer',
            'perPage'       => 'nullable|integer',
        ]))->check();
    }
}
