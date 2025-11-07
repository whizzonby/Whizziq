<?php

namespace App\Constants;

enum PlanType: string
{
    case FLAT_RATE = 'flat_rate';
    case USAGE_BASED = 'usage_based';
}
