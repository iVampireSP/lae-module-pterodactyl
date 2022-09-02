<x-app-layout>
    <h1>地区列表</h1>
    <a href="{{ route('locations.create') }}">添加地区</a>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>名称</th>
                <th>服务器数量</th>
                <th>添加时间</th>
                <th>操作</th>
            </tr>
        </thead>


        <tbody>
            @foreach ($locations as $location)
                <tr>
                    <td>{{ $location->id }}</td>
                    <td>{{ $location->name }}</td>
                    <td>{{ $location->servers }}</td>
                    <td>{{ $location->created_at }}</td>
                    <td>
                        <a href="{{ route('locations.edit', $location->id) }}">编辑</a>

                        <form action="{{ route('locations.destroy', $location->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit">删除</button>
                        </form>
                    </td>

                </tr>
            @endforeach
        </tbody>
    </table>
</x-app-layout>
