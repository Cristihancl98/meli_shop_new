<?php

namespace App\Events;

use App\Models\Customer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerSynced
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Customer $customer
    ) {}
}
