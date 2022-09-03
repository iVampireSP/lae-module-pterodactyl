<x-app-layout>
    <h2>编辑 {{ $location->name }}</h2>


    <form method="POST" action="{{ route('locations.update', $location->id) }}">
        @csrf
        @method('PUT')
         {{-- name --}}
        显示名称：<input type="text" name="name" placeholder="地区名称" value="{{ $location->name }}"/>
        <br />
        翼龙面板里的地区 ID:<input type="text" name="location_id" placeholder="翼龙面板里的地区 ID" value="{{ $location->location_id }}"/>
        <br />
        基础价格：<input type="text" name="price" placeholder="基础价格(元)" value="{{ $location->price }}"/>
        <br />
        {{-- submit --}}
        <input type="submit" value="更新" />

    </form>
</x-app-layout>
