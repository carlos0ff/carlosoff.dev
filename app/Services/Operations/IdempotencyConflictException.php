<?php

namespace App\Services\Operations;

use RuntimeException;

class IdempotencyConflictException extends RuntimeException {}
