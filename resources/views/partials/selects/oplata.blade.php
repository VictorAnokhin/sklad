@php
  $rows = \Illuminate\Support\Facades\DB::table('conf')
    ->where('type', 'oplata')->where('vision', '1')
    ->where(fn($q) => $q->where('firma', $fid)->orWhere('constanta', '1'))
    ->orderBy('name')->get();
@endphp
<select name="oplata">
  <option value="">{{ ucfirst('oplata') }}</option>
  @foreach($rows as $row)
    <option value="{{ $row->id }}" {{ (string)$row->id === (string)($selected ?? '') ? 'selected' : '' }}>
      {{ $row->name }}
    </option>
  @endforeach
</select>
