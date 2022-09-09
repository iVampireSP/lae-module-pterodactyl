<x-app-layout>
    <h2>编辑 {{ $location->name }}</h2>


    <form method="POST" action="{{ route('locations.update', $location->id) }}">
        @csrf
        @method('PUT')
         {{-- name --}}
        显示名称：<input type="text" name="name" placeholder="地区名称" value="{{ $location->name }}"/>
        <br />
        翼龙面板里的地区 ID:<input type="text" name="location_id" placeholder="翼龙面板里的地区 ID" value="{{ $location->location_id }}"/>

        <p>以下价格单位都是每 5 分钟要消耗 Drops</p>
        <br />
        基础价格：<input type="text" name="price" placeholder="基础价格" value="{{ $location->price }}" />

        <br />
        每 CPU 价格(CPU / 100 为 1 核心)<input type="text" name="cpu_price" value="{{ $location->cpu_price }}" />

        <br />
        每内存价格(MB)<input type="text" name="memory_price" value="{{ number_format($location->memory_price, 8) }}" />

        <br />
        每硬盘价格(Disk / 1024 为 1 GB)<input type="text" name="disk_price" value="{{ $location->disk_price }}" />

        <br />
        每一个备份的价格(个)<input type="text" name="backup_price" value="{{ $location->backup_price }}" />

        <br />
        每一个端口的价格(个)<input type="text" name="allocation_price" value="{{ $location->allocation_price }}" />

        <br />
        每一个数据库的价格(个)<input type="text" name="database_price" value="{{ $location->database_price }}" />
        <br />
        {{-- submit --}}
        <input type="submit" value="更新" />

    </form>
</x-app-layout>
