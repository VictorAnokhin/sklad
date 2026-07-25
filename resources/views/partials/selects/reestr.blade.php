@php
	  $rows = \Illuminate\Support\Facades\DB::table('conf')
	    ->where('type', 'reestr')->where('status', '1')
    ->orderBy('name')->get();
@endphp
<select name="reestr">
  <option value="">{{ ucfirst('reestr') }}</option>
  @foreach($rows as $row)
    <option value="{{ $row->id }}" {{ (string)$row->id === (string)($selected ?? '') ? 'selected' : '' }}>
      {{ $row->name }}
    </option>
  @endforeach
</select>
