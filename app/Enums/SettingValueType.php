<?php

declare(strict_types=1);

namespace App\Enums;

enum SettingValueType: string
{
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Text = 'text';
    case Date = 'date';
}
