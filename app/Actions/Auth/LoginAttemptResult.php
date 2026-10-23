<?php

namespace App\Actions\Auth;

enum LoginAttemptResult
{
    case Authenticated;
    case Rejected;
    case Throttled;
}
