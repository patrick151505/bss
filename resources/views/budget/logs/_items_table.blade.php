<table class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
    <thead class="bg-gray-100 dark:bg-gray-800">
        <tr>
            <th class="px-3 py-2 text-left font-semibold text-gray-500">Category</th>
            <th class="px-3 py-2 text-left font-semibold text-gray-500">Particulars</th>
            <th class="px-3 py-2 text-right font-semibold text-gray-500">Qty</th>
            <th class="px-3 py-2 text-left font-semibold text-gray-500">Unit</th>
            <th class="px-3 py-2 text-right font-semibold text-gray-500">Unit Cost</th>
            <th class="px-3 py-2 text-right font-semibold text-gray-500">Amount</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse($rows as $row)
        <tr>
            <td class="px-3 py-2 text-gray-500">{{ $row['category'] ?? '—' }}</td>
            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $row['particulars'] ?? '—' }}</td>
            <td class="px-3 py-2 text-right text-gray-500">{{ $row['quantity'] ?? '—' }}</td>
            <td class="px-3 py-2 text-gray-500">{{ $row['unit'] ?? '—' }}</td>
            <td class="px-3 py-2 text-right text-gray-500">
                {{ isset($row['unit_cost']) && $row['unit_cost'] !== null ? '₱' . number_format($row['unit_cost'], 2) : '—' }}
            </td>
            <td class="px-3 py-2 text-right font-medium text-gray-800 dark:text-gray-200">
                ₱{{ number_format($row['amount'] ?? 0, 2) }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="px-3 py-3 text-center text-gray-400 italic">No items</td>
        </tr>
        @endforelse
    </tbody>
</table>
