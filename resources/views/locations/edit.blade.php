<x-app-layout>
    <h2>编辑 {{ $location->name }}</h2>


    <form method="POST" action="{{ route('locations.update', $location->id) }}">
        @csrf
        @method('PUT')
         {{-- name --}}
        <input type="text" name="name" placeholder="地区名称" value="{{ $location->name }}"/>

        <input type="text" name="location_id" placeholder="翼龙面板里的地区 ID" value="{{ $location->location_id }}"/>

        <input type="text" name="price" placeholder="基础价格(元)" value="{{ $location->price }}"/>

        {{-- submit --}}
        <input type="submit" value="更新" />

    </form>
</x-app-layout>
