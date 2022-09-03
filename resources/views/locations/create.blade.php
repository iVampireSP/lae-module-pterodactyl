<x-app-layout>
    <h2>添加地区</h2>


    <form method="POST" action="{{ route('locations.store') }}">
        @csrf
        {{-- name --}}
        显示名称：<input type="text" name="name" placeholder="地区名称" />
        <br />

        翼龙面板里的地区 ID：<input type="text" name="location_id" placeholder="翼龙面板里的地区 ID" />

        <br />
        基础价格：<input type="text" name="price" placeholder="基础价格(元)" />

        <br />

        {{-- submit --}}
        <input type="submit" value="添加" />

    </form>
</x-app-layout>
