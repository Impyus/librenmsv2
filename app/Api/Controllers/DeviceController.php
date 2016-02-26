<?php

namespace App\Api\Controllers;

use App\User;
use App\Device;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function __construct() {

    }

    /**
     * Display a listing of all authorized devices
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // fetch devices from the database
        if ($request->user()->level >= 10 || $request->user()->level == 5) {
            $devices = Device::all();
        }
        else {
            $devices = User::find($request->user()->user_id)->devices()->get();
        }
        // morph the data as required
        if ($request->query('displayFormat') == 'human') {
        }

        return $devices;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        if ($request->user()->level >= 10 || $request->user()->level == 5) {
            return Device::find($id);
        }
        else {
            $user = User::find($request->user()->user_id);
            return $user->devices()->find($id);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

}
