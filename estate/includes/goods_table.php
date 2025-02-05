<div class="mt-6">
    <h3 class="text-lg font-semibold mb-4">Items/Goods List</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Item Description
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Quantity
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Purpose
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Remarks
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($goods as $item): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php echo htmlspecialchars($item['item_name'] ?? 'N/A'); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php echo htmlspecialchars($item['quantity'] ?? 'N/A'); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php echo htmlspecialchars($item['purpose'] ?? 'N/A'); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php echo htmlspecialchars($item['description'] ?? 'N/A'); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Total Items -->
    <div class="mt-4 text-right text-sm text-gray-500">
        Total Items: <?php echo count($goods); ?>
    </div>
</div>

<style>
    /* Responsive table styles */
    @media (max-width: 768px) {
        .overflow-x-auto {
            margin: 0 -1rem;
        }
        
        table {
            font-size: 0.875rem;
        }
        
        th, td {
            padding: 0.5rem !important;
        }
    }
    
    /* Print styles */
    @media print {
        table {
            break-inside: avoid;
        }
        
        th {
            background-color: #f9fafb !important;
            -webkit-print-color-adjust: exact;
        }
    }
</style>