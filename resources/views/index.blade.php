<x-app-layout>
    您将要从哪里开始？


    <h4>收益</h4>
    <div>
        <h3>
            本月收益
        </h3>
        <p>
            直接扣费金额: {{ $module['balance'] }} 元
        </p>
        <p>
            Drops: {{ $module['drops'] }}
        </p>
        <p>本月总计收入 CNY: {{ $module['total'] }} </p>
    </div>

</x-app-layout>
