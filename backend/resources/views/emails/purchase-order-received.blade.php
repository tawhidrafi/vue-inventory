<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Purchase order received</title>
</head>
<body style="font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; color: #111827; line-height: 1.6; padding: 24px;">
    <h1 style="font-size: 18px; margin: 0 0 12px;">Purchase order received</h1>

    <p style="margin: 0 0 16px;">
        <strong>{{ $purchaseOrder->number }}</strong>
        @if ($purchaseOrder->supplier)
            from {{ $purchaseOrder->supplier->name }}
        @endif
        has been fully received.
    </p>

    <table cellpadding="8" cellspacing="0" style="border-collapse: collapse; border: 1px solid #e5e7eb; width: 100%;">
        <thead>
            <tr style="background: #f9fafb;">
                <th align="left" style="border-bottom: 1px solid #e5e7eb;">Product</th>
                <th align="right" style="border-bottom: 1px solid #e5e7eb;">Qty</th>
                <th align="right" style="border-bottom: 1px solid #e5e7eb;">Unit cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchaseOrder->items as $item)
                <tr>
                    <td style="border-bottom: 1px solid #e5e7eb;">
                        {{ $item->product?->name ?? 'Deleted product' }}
                    </td>
                    <td align="right" style="border-bottom: 1px solid #e5e7eb;">{{ $item->quantity }}</td>
                    <td align="right" style="border-bottom: 1px solid #e5e7eb;">
                        {{ number_format((float) $item->unit_cost, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" align="right" style="padding-top: 10px;"><strong>Total</strong></td>
                <td align="right" style="padding-top: 10px;">
                    <strong>{{ number_format((float) $purchaseOrder->total, 2) }}</strong>
                </td>
            </tr>
        </tfoot>
    </table>

    <p style="margin: 16px 0 0; color: #6b7280; font-size: 13px;">
        You are receiving this because purchase order alerts are enabled on your account.
    </p>
</body>
</html>
