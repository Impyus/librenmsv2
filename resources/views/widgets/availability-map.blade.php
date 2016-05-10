@extends('layouts.widget')

@if ($action == 'settings')

<?php
    if (!isset($widget_settings))
    {
        $widget_settings = new stdClass();
        $widget_settings->tile_width = 10;
        $widget_settings->mode       = 0;
    }
?>
    @section('settings')
        <div class="form-group">
            {{ Form::label('tile_width', trans('widgets.label.tile_width'), array('class' => 'col-sm-3')) }}
            <div class="col-sm-9">
                {{ Form::text('tile_width', $widget_settings->tile_width, array('class' => 'form-control')) }}
            </div>
        </div>
        <div class="form-group">
            {{ Form::label('mode', trans('widgets.label.show'), array('class' => 'col-sm-3')) }}
            <div class="col-sm-9">
                {{ Form::select('mode', array('0' => trans('widgets.text.only_devices'),
                                                '1' => trans('widgets.text.only_services'),
                                                '2' => trans('widgets.text.both_devices_services')
                                ), $widget_settings->mode, array('class' => 'form-control')) }}
            </div>
        </div>
    @endsection
@else
    @section('content')
        <center><span data-toggle="tooltip" title="" class="badge bg-green" data-original-title="{{ $count['up'] }} Devices up">{{ $count['up'] }}</span> <span data-toggle="tooltip" title="" class="badge bg-yellow" data-original-title="{{ $count['warn'] }} Recently rebooted">{{ $count['warn'] }}</span> <span data-toggle="tooltip" title="" class="badge bg-red" data-original-title="{{ $count['down'] }} Devices down">{{ $count['down'] }}</span></h5></center>

        <?php $count = ['warn' => 0, 'up' => 0, 'down' => 0]; ?>

        @foreach ($devices as $device)

            @if ($device->status == 1)
                @if ($device->uptime < $uptime && $device->uptime != '0')
                    <?php $btn = "warning"; ?>
                @else
                    <?php $btn = "success"; ?>
                @endif
            @else
                <?php $btn = "danger"; ?>
            @endif
            <a href="{{ url("devices/".$device->device_id) }}" role="button" class="btn btn-{{ $btn }} btn-xs" title="{{ $device->hostname }} {{ $device->formatUptime($device->uptime) }}" style="min-height:{{ $widget_settings->tile_width or 10 }}px; min-width:{{ $widget_settings->tile_width or 10 }}px; border-radius:0px; margin:0px; padding:0px;"></a>
        @endforeach
    @endsection
@endif

@section('scripts')
@endsection
