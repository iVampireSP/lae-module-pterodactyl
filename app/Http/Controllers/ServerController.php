<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\Request;

class ServerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $servers = Server::simplePaginate(10);

        return view('servers.index', compact('servers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view('servers.create');
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
        $request->validate([
            'name' => 'required',
            'fqdn' => 'required',
            'port' => 'required',
            'status' => 'required',
        ]);

        Server::create($request->all());

        return redirect()->route('servers.index')->with('success', '服务器成功添加。');
    }

    /**
     * Display the specified resource.
     *
     * @param  Server $server
     * @return \Illuminate\Http\Response
     */
    public function show(Server $server)
    {
        //
        return view('servers.show', compact('server'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Server $server
     * @return \Illuminate\Http\Response
     */
    public function edit(Server $server)
    {
        //
        return view('servers.edit', compact('server'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Server $server
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Server $server)
    {
        //
        // $request->validate([
        //     'name' => 'required',
        //     'fqdn' => 'required',
        //     'port' => 'required',
        //     'status' => 'required',
        // ]);

        $server->update($request->all());

        return redirect()->route('servers.index')->with('success', '服务器成功更新。');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Server $server
     * @return \Illuminate\Http\Response
     */
    public function destroy(Server $server)
    {
        //
        $server->delete();

        return redirect()->route('servers.index')->with('success', '服务器成功删除。');
    }
}
