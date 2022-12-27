<x-app-layout>
    <h1>主机</h1>

    <p>总计: {{ $count }}</p>

    <h1>从面板导入</h1>

    <form action="{{ route('hosts.import') }}" method="POST">
        @csrf

        <input type="text" name="server_id" placeholder="服务器 ID" />

        <button type="submit">导入</button>
    </form>


    <br />

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>名称</th>
                <th>客户</th>
                <th>每 5 分钟扣费</th>
                <th>状态</th>
                <th>创建时间</th>
                <th>更新时间</th>
                <th>操作</th>
            </tr>
        </thead>


        <tbody>
            @foreach ($hosts as $host)
                <tr>
                    <td>{{ $host->id }}</td>
                    <td>{{ $host->name }}</td>
                    <td>{{ $host->user->name }}</td>
                    <td>{{ $host->price }}</td>
                    <td>{{ $host->status }}</td>
                    <td>{{ $host->created_at }}</td>
                    <td>{{ $host->updated_at }}</td>
                    <td>
                        <a target="_blank" href="{{ config('panel.url') }}/server/{{$host->identifier}}">控制台</a>

                        @if ($host->status == 'suspended')
                            <form action="{{ route('hosts.update', $host->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="running" />
                                <button type="submit">取消暂停</button>
                            </form>
                        @else
                            <form action="{{ route('hosts.update', $host->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="suspended" />
                                <button type="submit">暂停</button>
                            </form>
                        @endif

                        @if ($host->status == 'stopped')
                            <form action="{{ route('hosts.update', $host->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="running" />
                                <button type="submit">启动</button>
                            </form>
                        @else
                            <form action="{{ route('hosts.update', $host->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="stopped" />
                                <button type="submit">停止</button>
                            </form>
                        @endif


                        <form action="{{ route('hosts.update', $host->id) }}" method="POST"
                            onsubmit="return confirm('在非必要情况下，不建议手动扣费。要继续吗？')">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="cost" />
                            <button type="submit">扣费</button>
                        </form>
                        <form action="{{ route('hosts.destroy', $host->id) }}" method="POST"
                            onsubmit="return confirm('真的要删除吗？')">
                            @csrf
                            @method('DELETE')
                            <button type="submit">删除</button>
                        </form>
                        <form action="{{ route('hosts.destroy_db', $host->id) }}" method="POST"
                            onsubmit="return confirm('真的要删除吗？此选项将不删除远程服务器，只从数据库中删除。')">
                            @csrf
                            @method('DELETE')
                            <button type="submit">仅从数据库中删除</button>
                        </form>

                    </td>

                </tr>
            @endforeach
        </tbody>
    </table>


    {{ $hosts->links() }}
</x-app-layout>
