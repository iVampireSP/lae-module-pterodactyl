<x-app-layout>
    <h2>添加地区</h2>


    <form method="POST" action="{{ route('locations.store') }}">
        @csrf
        {{-- name --}}
        显示名称：<input type="text" name="name" placeholder="地区名称" />
        <br />
        <br />
        <br />

        描述
        <textarea name="description" rows="10" cols="50"></textarea>

        <br />

        翼龙面板里的地区 ID：<input type="text" name="location_id" placeholder="翼龙面板里的地区 ID" />

        <p>以下价格单位都是为 元，并且是每月价格。</p>
        <br />
        基础价格：<input type="text" name="price" placeholder="基础价格(元)" value="1" />

        <br />
        每 CPU 价格(CPU / 100 为 1 核心)<input type="text" name="cpu_price" value="1.5" />

        <br />
        每内存价格(GB)<input type="text" name="memory_price" value="2" />

        <br />
        每硬盘价格(GB)<input type="text" name="disk_price" value="0.5" />

        <br />
        每一个备份的价格(个)<input type="text" name="backup_price" value="0.1" />

        <br />
        每一个端口的价格(个)<input type="text" name="allocation_price" value="0.1" />

        <br />
        每一个数据库的价格(个)<input type="text" name="database_price" value="0.1" />

        {{-- submit --}}
        <input type="submit" value="添加" />

    </form>
</x-app-layout>
