<?php
/**
 *                                _ __  __
 *    ____ ___  ____ _____ ______(_) /_/ /____
 *   / __ `__ \/ __ `/ __ `/ ___/ / __/ __/ _ \
 *  / / / / / / /_/ / /_/ / /  / / /_/ /_/  __/
 * /_/ /_/ /_/\__,_/\__, /_/  /_/\__/\__/\___/
 *                 /____/
 *
 * (c) Claudio Procida 2026
 *
 * @format
 */

namespace Emeraldion\Magritte\Helpers;

abstract class TimeFormat
{
    const FULL = 'D, j F Y';
    const YEAR_DASH_MONTH = 'Y-m';
    const YEAR_DASH_MONTH_DASH_DAY = 'Y-m-d';
    const TIMESTAMP = 'Y-m-d H:i:s';
    const HOUR_COLON_MINUTE = 'H:i';
    const MONTH_YEAR = 'F Y';
    const DATETIME_FIELD_VALUE = 'Y-m-d H:i';
}
