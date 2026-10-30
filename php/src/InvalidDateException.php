<?php

declare(strict_types=1);

namespace Taqvim;

/** Thrown for dates that do not exist, are out of range, or cannot be parsed. */
class InvalidDateException extends \InvalidArgumentException
{
}
