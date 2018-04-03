<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Network;

class NetworkController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api')->except(['show', 'index']);
    }
    /**
     * Display a listing of the resource.
     *
     * @request \Illuminate\Http\Request
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->filled('user_id')) {
            $user = $request->input('user_id');
            $network = new Network;

            return response()->json(
                $network->where(['user_id' => $user])->get()->toArray(),
                200
            );
        }

        return response()->json(Network::all()->toArray(), 200);
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
        $network = new Network;

        if ($networkLog = $network->create($request->all())) {
            return response()->json([
                'message' => 'Network log added successfully',
                'network_log_id' => $networkLog->id
            ], 200);
        } else {
            return response()->json([
                'message' => 'An error occurred while adding a network log',
            ], 204);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        $ids = explode(',', $id);
        $logs = Network::find($ids);
        $user_id = \Auth::user()->id;

        $userLogs = $logs->filter(function ($log) use ($user_id) {
            return (int) $log->user_id === $user_id;
        });

        $userLogIds = $userLogs->pluck('id')->toArray();

        if (Network::destroy($userLogIds)) {
            return response()->json(['message' => 'done delete'], 200);
        } else {
            return response()->json(['message' => 'Nothing to delete'], 201);
        }
    }
}
