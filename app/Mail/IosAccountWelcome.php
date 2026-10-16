<?php

namespace App\Mail;

class IosAccountWelcome extends AccountWelcome
{
    public function build()
    {
        return parent::build()->with(['showExploreButton' => false]);
    }
}
