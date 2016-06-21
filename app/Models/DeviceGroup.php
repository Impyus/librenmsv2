<?php
/**
 * DeviceGroup.php
 *
 * Dynamic groups of devices
 *
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
 *
 * @package    LibreNMS
 * @link       http://librenms.org
 * @copyright  2016 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\DeviceGroup
 *
 * @property integer $id
 * @property string $name
 * @property string $desc
 * @property string $pattern
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Device[] $devices
 * @method static \Illuminate\Database\Query\Builder|\App\Models\DeviceGroup whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\DeviceGroup whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\DeviceGroup whereDesc($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\DeviceGroup wherePattern($value)
 * @mixin \Eloquent
 */
class DeviceGroup extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'device_groups';
    /**
     * The primary key column name.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    /**
     * Virtual attributes
     *
     * @var string
     */
    protected $appends = ['deviceCount'];

    /**
     * The attributes that can be mass assigned.
     *
     * @var array
     */
    protected $fillable = ['name', 'desc', 'pattern'];

    /**
     * Fetch the device counts for groups
     * Use DeviceGroups::with('deviceCountRelation') to eager load
     *
     * @return int
     */
    public function getDeviceCountAttribute()
    {
        // if relation is not loaded already, let's do it first
        if (!$this->relationLoaded('deviceCountRelation')) {
            $this->load('deviceCountRelation');
        }

        $related = $this->getRelation('deviceCountRelation')->first();

        // then return the count directly
        return ($related) ? (int) $related->count : 0;
    }

    /**
     * Set the pattern attribute
     * Update the relationships when set
     *
     * @param $pattern
     */
    public function setPatternAttribute($pattern)
    {
        $this->attributes['pattern'] = $pattern;

        // we need an id to add relationships
        if (is_null($this->id)) {
            $this->save();
        }

        // update the relationships (deletes and adds as needed)
        $this->devices()->sync($this->getDeviceIdsRaw($pattern));
    }

    /**
     * Relationship to App\Models\Device
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function devices()
    {
        return $this->belongsToMany('App\Models\Device', 'device_group_device', 'device_group_id', 'device_id');
    }

    // ---- Accessors/Mutators ----

    /**
     * Get an array of the device ids from this group by re-querying the database with
     * either the specified pattern or the saved pattern of this group
     *
     * @param null $pattern Optional, will use the pattern from this group if not specified
     * @return array
     */
    public function getDeviceIdsRaw($pattern = null)
    {
        if (is_null($pattern)) {
            $pattern = $this->pattern;
        }

        $tables = $this->getTablesFromPattern($pattern);

        $query = null;
        if (count($tables) == 1) {
            $query = DB::table($tables[0])->select('device_id');
        } else {
            $query = DB::table('devices')->select('devices.device_id')->distinct();

            foreach ($tables as $table) {
                if ($table == 'devices') {
                    // skip devices table as we used that as the base.
                    continue;
                }

                $query = $query->join($table, 'devices.device_id', '=', $table.'.device_id');
            }
        }

        // match the device ids
        return $query->whereRaw($pattern)->pluck('device_id');
    }

    /**
     * Extract an array of tables in a pattern
     *
     * @param string $pattern
     * @return array
     */
    private function getTablesFromPattern($pattern)
    {
        preg_match_all('/[A-Za-z_]+(?=\.[A-Za-z_]+ )/', $pattern, $tables);
        $tables = array_keys(array_flip($tables[0])); // unique tables only
        if (is_null($tables)) {
            return [];
        }
        return $tables;
    }

    /**
     * Check if the stored pattern is v1
     * Convert it to v2 for display
     * Currently, it will only be updated in the database if the user saves the rule in the ui
     *
     * @param $pattern
     * @return string
     */
    public function getPatternAttribute($pattern)
    {
        // If this is a v1 pattern, convert it to v2 style
        if (starts_with($pattern, '%')) {
            $pattern = $this->convertV1Pattern($pattern);

            $this->pattern = $pattern; //TODO: does not save, only updates this instance
        }

        return $pattern;
    }

    // ---- Define Relationships ----

    /**
     * Convert a v1 device group pattern to v2 style
     *
     * @param $pattern
     * @return array
     */
    private function convertV1Pattern($pattern)
    {
        $pattern = rtrim($pattern, ' &&');
        $pattern = rtrim($pattern, ' ||');

        $ops = ['=', '!=', '<', '<=', '>', '>='];
        $parts = str_getcsv($pattern, ' ');
        $out = "";

        $count = count($parts);
        for ($i = 0; $i < $count; $i++) {
            $cur = $parts[$i];

            if (starts_with($cur, '%')) {
                // table and column
                $out .= substr($cur, 1).' ';
            } elseif (substr($cur, -1) == '~') {
                // like operator
                $content = $parts[++$i]; // grab the content so we can format it

                if (str_contains($content, '@')) {
                    // contains wildcard
                    $content = str_replace('@', '%', $content);
                } else {
                    // assume substring
                    $content = '%'.$content.'%';
                }

                if (starts_with($cur, '!')) {
                    // prepend NOT
                    $out .= 'NOT ';
                }

                $out .= "LIKE('".$content."') ";

            } elseif ($cur == '&&') {
                $out .= 'AND ';
            } elseif ($cur == '||') {
                $out .= 'OR ';
            } elseif (in_array($cur, $ops)) {
                // passthrough operators
                $out .= $cur.' ';
            } else {
                // user supplied input
                $out .= "'".trim($cur, '"\'')."' "; // TODO: remove trim, only needed with invalid input
            }
        }
        return rtrim($out);
    }

    /**
     * Relationship allows us to eager load device counts
     * DeviceGroups::with('deviceCountRelation')
     *
     * @return mixed
     */
    public function deviceCountRelation()
    {
        return $this->devices()->selectRaw('`device_group_device`.`device_group_id`, count(*) as count')->groupBy('pivot_device_group_id');
    }
}
