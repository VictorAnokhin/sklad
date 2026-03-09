{{-- partials/selects/reteil.blade.php --}}
@php
  $rows = \Illuminate\Support\Facades\DB::table('conf')
    ->where('type', 'reteil')->where('vision', '1')
    ->where(fn($q) => $q->where('firma', $fid)->orWhere('constanta', '1'))
    ->orderBy('name')->get();
@endphp
<select name="reteil">
  <option value="">Проекти</option>
  @foreach($rows as $row)
    <option value="{{ $row->id }}" {{ (string)$row->id === (string)($selected ?? '') ? 'selected' : '' }}>
      {{ convert_from_base($row->name) }}
    </option>
  @endforeach
</select>
