<?php
/**
 * app/DataTables/DeviceDataTable.php
 *
 * Datatable for devices
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
 * @copyright  2016 Neil Lathwood
 * @author     Neil Lathwood <neil@lathwood.co.uk>
 */

namespace App\DataTables;

use App\Models\Device;
use Yajra\Datatables\Services\DataTable;

class DeviceDataTable extends DataTable
{
    /**
     * Display ajax response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajax()
    {
        return $this->datatables
            ->eloquent($this->query())
            ->editColumn('status_reason', function($device) {
                if ($device->status == 0)
                {
                    return '<span data-toggle="tooltip" title="down" class="badge bg-red">'.$device->status_reason.'</span>';
                }
                else
                {
                    return '<span data-toggle="tooltip" title="up" class="badge bg-light-blue">up</span>';
                }
            })
            ->editColumn('vendor', function($device) {
                return '<img src="'.$device->logo().'" border="0" alt="'.$device->os.'">';
            })
            ->editColumn('hostname', function($device) {
                $hostname = is_null($device) ? trans('devices.text.deleted') : $device->hostname;
                return '<a href="'.url("devices/".$device->device_id).'">'.$hostname.'</a>';
            })
            ->editColumn('resources', function($device) {
                $ports   = $device->ports()->count();
                $sensors = $device->sensors()->count();
                return '<span data-toggle="tooltip" title="'.$ports.' Ports" class="badge bg-light-blue"><i class="fa fa-link"></i>&nbsp; '.$ports.'</span><br />
                        <span data-toggle="tooltip" title="'.$sensors.' Sensors" class="badge bg-light-blue"><i class="fa fa-dashboard"></i>&nbsp; '.$sensors.'</span>';
            })
            ->editColumn('hardware', function($device) {
                return $device->hardware.'<br />'.$device->features;
            })
            ->editColumn('os', function($device) {
                return ucfirst($device->os).'<br />'.$device->version;
            })
            ->editColumn('location', function($device) {
                return $device->formatUptime($device->uptime).'<br />'.$device->location;
            })
            ->make(true);
    }

    /**
     * Get the query object to be processed by datatables.
     *
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        $device = Device::select('devices.*');
        return $this->applyScopes($device);
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\Datatables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
                    ->columns($this->getColumns())
                    ->parameters($this->getBuilderParameters());
    }

    /**
     * Get columns.
     *
     * @return array
     */
    private function getColumns()
    {
        return [
            'status'    => [
                'title' => trans('general.text.status'),
                'data'  => 'status_reason',
                'width' => '40px',
            ],
            'vendor'        => [
                'title' => trans('devices.text.vendor'),
                'width' => '20px',
            ],
            'hostname'  => [
                'title' => trans('devices.label.hostname'),
            ],
            'resources'  => [
                'title'  => '',
                'search' => false,
            ],
            'hardware'  => [
                'title' => trans('devices.text.platform'),
            ],
            'features'    => [
                'visible' => false,
            ],
            'os'        => [
                'title' => trans('devices.text.os'),
            ],
            'version'     => [
                'visible' => false,
            ],
            'location'  => [
                'title' => trans('devices.text.uptime_location'),
            ],
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'devices';
    }

    /**
     * Get Builder Params
     *
     * @return array
     */
    protected function getBuilderParameters()
    {
        return [
            'dom' => "<'row'<'col-sm-3'l><'col-sm-6 text-center'B><'col-sm-3'f>>".
                     "<'row'<'col-sm-12'tr>>".
                     "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            'lengthMenu' => [[25, 50, 100, -1], [25, 50, 100, "All"]],
            'buttons' => [
                'csv', 'excel', 'pdf', 'print', 'reset', 'reload',
            ],
            'autoWidth' => false,
        ];
    }

}
