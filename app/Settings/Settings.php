<?php
/*
 * Copyright (C) 2016 Tony Murray <murraytony@gmail.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
/**
 * Settings.php
 *
 * @package    LibreNMS
 * @author     Tony Murray <murraytony@gmail.com>
 * @copyright  2016 Tony Murray
 * @license    @license http://opensource.org/licenses/GPL-3.0 GNU Public License v3 or later
 */
namespace App\Settings;


use Cache;
use Config;
use DB;
use Illuminate\Contracts\Config\Repository as ConfigContract;

class Settings implements ConfigContract
{
    private $cache_time;
    private $database;

    public function __construct()
    {
        $this->database = new DatabaseRepository(DB::connection(), 'config');
        $this->cache_time = env('CACHE_LIFETIME', 60);
    }

    public function set($key, $value = null)
    {
        if (is_array($value)) {
            $value = self::arrayToPath($value, $key);
            foreach ($value as $k => $v) {
                $this->database->set($k, $v);
            }
        }
        else {
            $this->database->set($key, $value);
        }
        return $value;
    }

    protected static function arrayToPath($array, $prefix = "")
    {
        return self::recursive_keys($array, $prefix);
    }

    private static function recursive_keys(array $array, $prefix = "", array $path = array())
    {
        if ($prefix != "") {
            $prefix = trim($prefix, '.') . '.';
        }
        $result = array();
        foreach ($array as $key => $val) {
            $currentPath = array_merge($path, array($key));
            if (is_array($val)) {
                $result = array_merge($result, self::recursive_keys($val, $prefix, $currentPath));
            }
            else {
                $result[$prefix . join('.', $currentPath)] = $val;
            }
        }
        return $result;
    }

    public function get($key, $default = null)
    {
        // return value from cache or fetch it and return it
        return Cache::remember($key, $this->cache_time, function () use ($key, $default) {
            $value = $this->database->get($key, $default);

            if (is_array($value)) {
                $value = self::pathToArray($value, $key);
                $config = Config::get('config.' . $key, $default);
                if (!is_null($config)) {
                    $value = array_replace_recursive($config, $value);
                }
            }
            elseif (is_null($value)) {
                return Config::get('config.' . $key);
            }

            return $value;
        });
    }

    protected static function pathToArray($data, $prefix = "")
    {
        $tree = array();
        foreach ($data as $key => $value) {
            if (substr($key, 0, strlen($prefix)) == $prefix) {
                $key = substr($key, strlen($prefix));
            }
            $parts = explode('.', trim($key, '.'));

            $temp = &$tree;
            foreach ($parts as $part) {
                $temp = &$temp[$part];
            }
            $temp = $value;
            unset($temp);
        }
        return $tree;
    }

    public function has($key)
    {
        return (Cache::has($key) || Config::has($key) || $this->database->has($key));
    }

    public function forget($key)
    {
        $this->database->forget($key);
        Cache::forget($key);
    }

    public function all()
    {
        // no caching :(
        $config_settings = Config::all()['config'];
        $db_settings = self::pathToArray($this->database->all());
        return array_replace_recursive($config_settings, $db_settings);
    }

    /**
     * Prepend a value onto an array configuration value.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function prepend($key, $value)
    {
        // TODO: Implement prepend() method.S
    }

    /**
     * Push a value onto an array configuration value.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function push($key, $value)
    {
        // TODO: Implement push() method.
    }
}