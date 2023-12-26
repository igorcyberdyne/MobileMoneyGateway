<?php

namespace Ekolotech\MobileMoney\Gateway\Api\Dto;
class DisburseRequestBody
{
    public function __construct(
        public readonly int $amount,
        public readonly string $number,
        public readonly string $reference,
    )
    {
    }

}