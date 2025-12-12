@props([
'labelEn',
'labelAr',
'valueEn' => '',
'valueAr' => '',
'index' => '',
])

<tr>
    <th class="left-th" rowspan="2">{{ $index }} {{ $labelEn }}:</th>

    {{-- مهم: colspan=2 حتى ما يترك عمود فاضي --}}
    <td class="centred-text" colspan="2">
        <span dir="ltr">{{ $valueEn }}</span>
    </td>

    <th class="rtl-text right-th" dir="rtl" rowspan="2">{{ $index }} {{ $labelAr }}:</th>
</tr>
<tr>
    <td class="centred-text" colspan="2">
        <span dir="rtl">{{ $valueAr ?: $valueEn }}</span>
    </td>
</tr>
