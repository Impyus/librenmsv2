<?php
/**
 * GroupTest.php
 *
 * Tests for Device Groups
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

use App\Models\Device;
use App\Models\DeviceGroup;
use App\Models\Port;
use App\Models\User;

class GroupTest extends TestCase
{
    function testCreate()
    {
        $deviceone = factory(Device::class)->create();
        $devicetwo = factory(Device::class)->create();

        $portone = factory(Port::class)->make();
        $porttwo = factory(Port::class)->make();
        $deviceone->ports()->save($portone);
        $devicetwo->ports()->save($porttwo);

        $groupone = DeviceGroup::create(['name' => 'test', 'desc' => 'A test rule', 'pattern' => "devices.hostname = '".$deviceone->hostname."'"]);
        $this->assertEquals(1, $groupone->devices()->count());
        $this->assertEquals($deviceone->device_id, $groupone->devices()->first()->device_id);

        $grouptwo = DeviceGroup::create(['name' => 'another test group', 'desc' => 'Second test rule', 'pattern' => "ports.port_id > 0"]);
        $this->assertEquals(2, $grouptwo->devices()->count());

        $groupthree = DeviceGroup::create(['name' => 'third test', 'pattern' => "devices.device_id = '".$devicetwo->device_id."' AND ports.ifIndex = ".$porttwo->ifIndex]);
        $this->assertEquals(1, $groupthree->devices()->count());
        $this->assertEquals($devicetwo->device_id, $groupthree->devices()->first()->device_id);

        // check the web pages
        $user = factory(User::class)->create(['level' => 10]);
        $this->actingAs($user)
            ->visit('/device-groups')
            ->see('Device Groups')
            ->see($groupone->name)// in the menu
            ->see($grouptwo->name)
            ->see($groupthree->name)
            ->visit('/devices/group='.$groupthree->id)
            ->see('<small>'.$groupthree->name.'</small>');
    }

    function testV1Parser()
    {
        $data = [
            ['input'  => '%devices.hostname ~ "Test input" &&',
             'result' => "devices.hostname LIKE('%Test input%')"],

            ['input'  => '%devices.hostname !~ "more.@-@" &&',
             'result' => "devices.hostname NOT LIKE('more.%-%')"],

            ['input'  => '%devices.device_id < "50" && %sensors.sensor_id != "179" &&',
             'result' => "devices.device_id < '50' AND sensors.sensor_id != '179'"],

            ['input'  => '%devices.device_id = "42" || %devices_attribs.attrib_value ~ "@end" || %devices.hostname = "fun time" &&',
             'result' => "devices.device_id = '42' OR devices_attribs.attrib_value LIKE('%end') OR devices.hostname = 'fun time'"],

// Bad input
//            ['input'  => '%devices.hostname = ""fun"" && %devices.hostname = "\'fun\'" &&',
//             'result' => 'devices.hostname = ""fun"" && devices.hostname = "\'fun\'" &&'],
        ];

        $group = DeviceGroup::create(['name' => 'test', 'pattern' => '1']);
        $group_id = $group->id;
        foreach ($data as $item) {
            DB::table('device_groups')->where('id', $group_id)->update(['pattern' => $item['input']]);
            $this->assertEquals($item['result'], DeviceGroup::find($group_id)->pattern);
        }

    }
}

