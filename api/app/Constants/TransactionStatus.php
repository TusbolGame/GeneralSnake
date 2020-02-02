<?php

namespace App\Constants;

class TransactionStatus {
    const PENDING = 0;
    const SUCCESS = 1;
    const FAIL = 2;
    const PROCESSING = 3;
    const CONFIRMING_EMAIL = 4;
}
