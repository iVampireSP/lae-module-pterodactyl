<x-app-layout>
    您将要从哪里开始？


    <h4>收益</h4>
    <div>
        <table>
            <thead>
                <th>年 / 月</th>

                @for ($i = 1; $i < 13; $i++)
                    <th>{{ $i }} 月</th>
                @endfor
            </thead>
            <tbody>

                @foreach ($years as $year => $months)
                    <tr>
                        <td>{{ $year }}</td>
                        @for ($i = 1; $i < 13; $i++)
                            <td>{{ $months[$i]['should_balance'] ?? 0 }} 元</td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</x-app-layout>
