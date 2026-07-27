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

namespace Emeraldion\Magritte\Models;

use Emeraldion\EmeRails\Db;

/**
 * @trait ByShortName
 * @short Edit this model's short description
 * @details Edit this model's detailed description
 */
trait ByShortName
{
    public static function find_by_short_name(string $short_name): ?self
    {
        $conn = Db::get_connection();
        // This ensures we return the right subclass
        $cls = get_called_class();

        $factory = new $cls();
        $ret = $factory->find_one([
            'where_clause' => "`short_name` = '{$conn->escape($short_name)}'"
        ]);

        Db::close_connection($conn);

        return $ret;
    }
}
