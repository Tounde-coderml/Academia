<?php

namespace App\Actions\Auth;

enum PasswordResetResult
{
    case Reset;
    case InvalidOrExpired;
    case Throttled;
}
