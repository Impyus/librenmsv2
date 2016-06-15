<?php
/**
 * DeviceGroupController.php
 *
 * -Description-
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

namespace App\Http\Controllers;

use App\DataTables\DeviceGroupDataTable;
use App\Http\Requests;
use App\Models\DeviceGroup;
use Illuminate\Http\Request;

class DeviceGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param DeviceGroupDataTable $dataTable
     * @return \Illuminate\Http\Response
     */
    public function index(DeviceGroupDataTable $dataTable)
    {
        return $dataTable->render('devices.group-list');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('devices.group-edit');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $group = DeviceGroup::create($request->all());

        return response()->json(['message' => trans('devices.groups.created', ['name' => $group->name])]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return redirect('devices/group='.$id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $group = DeviceGroup::findOrFail($id);
        return view('devices.group-edit')->withGroup($group);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $group = DeviceGroup::find($id);
        $group->update($request->all());

        return response()->json(['message' => trans('devices.groups.updated', ['name' => $group->name])]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $group = DeviceGroup::find($id);
        $group->delete();

        return response()->json(['message' => trans('devices.groups.deleted', ['name' => $group->name])]);
    }
}
