{{-- $name, $rows, $amountField, $amountLabel, $fromLabel, $toLabel, $disabled --}}
<table class="table dark-table mb-0" style="table-layout:fixed;">
    <thead>
        <tr>
            <th style="padding:8px 4px;">{{ $fromLabel }}</th>
            <th style="padding:8px 4px;">{{ $toLabel }}</th>
            <th style="padding:8px 4px;">{{ $amountLabel }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach(array_merge($rows, array_fill(0, 3, [])) as $i => $row)
            <tr>
                <td style="padding:6px 4px;"><input type="number" step="any" min="0" class="form-control form-control-sm" name="{{ $name }}[{{ $i }}][from]" value="{{ old("{$name}.{$i}.from", $row['from'] ?? '') }}" @disabled($disabled)></td>
                <td style="padding:6px 4px;"><input type="number" step="any" min="0" class="form-control form-control-sm" name="{{ $name }}[{{ $i }}][to]" value="{{ old("{$name}.{$i}.to", $row['to'] ?? '') }}" @disabled($disabled)></td>
                <td style="padding:6px 4px;"><input type="number" step="any" min="0" class="form-control form-control-sm" name="{{ $name }}[{{ $i }}][{{ $amountField }}]" value="{{ old("{$name}.{$i}.{$amountField}", $row[$amountField] ?? '') }}" @disabled($disabled)></td>
            </tr>
        @endforeach
    </tbody>
</table>
<small style="color:var(--text-muted);font-size:11px;">Deja filas vacías para eliminarlas.</small>
