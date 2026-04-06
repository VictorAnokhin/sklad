@php
  $rows = \Illuminate\Support\Facades\DB::table('conf')
    ->where('type', 'status')->where('vision', '1')
    ->where(fn($q) => $q->where('firma', $fid)->orWhere('constanta', '1'))
    ->orderBy('name')->get();
@endphp
<select name="status">
  <option value="">Статус</option>
  <option value="999" {{ ($selected ?? '') === '999' ? 'selected' : '' }}>Всі (з архівом)</option>
  @foreach($rows as $row)
    <option value="{{ $row->id }}" style="background:{{ $row->color ?? '' }}"
      {{ (string)$row->id === (string)($selected ?? '') ? 'selected' : '' }}>
      {{ $row->name }}
    </option>
  @endforeach
</select>
