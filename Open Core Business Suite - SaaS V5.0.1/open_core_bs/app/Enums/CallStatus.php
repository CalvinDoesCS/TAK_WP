<?php

namespace App\Enums;

enum CallStatus: string
{
    case INITIATED = 'initiated';

    case ONGOING = 'ongoing';

    case MISSED = 'missed';

    case COMPLETED = 'completed';

    case DECLINED = 'declined';

    case FAILED = 'failed';

    case CANCELLED = 'cancelled';
}
