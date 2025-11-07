<?php

namespace App\Exceptions;

use Exception;

class SubscriptionCreationNotAllowedException extends Exception
{
    public function render()
    {
        return view('checkout.already-subscribed');
    }
}
