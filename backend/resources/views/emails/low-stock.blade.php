<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Low stock</title>
</head>
<body style="font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; color: #111827; line-height: 1.6; padding: 24px;">
    <h1 style="font-size: 18px; margin: 0 0 12px;">Low stock alert</h1>

    <p style="margin: 0 0 16px;">
        <strong>{{ $product->name }}</strong>
        <span style="color: #6b7280;">({{ $product->sku }})</span>
        has reached its reorder level in {{ $warehouse->name }}.
    </p>

    <table cellpadding="8" cellspacing="0" style="border-collapse: collapse; border: 1px solid #e5e7eb;">
        <tr>
            <td style="color: #6b7280; border-bottom: 1px solid #e5e7eb;">Warehouse</td>
            <td style="border-bottom: 1px solid #e5e7eb;">{{ $warehouse->name }}</td>
        </tr>
        <tr>
            <td style="color: #6b7280; border-bottom: 1px solid #e5e7eb;">On hand</td>
            <td style="border-bottom: 1px solid #e5e7eb;">{{ $quantity }}</td>
        </tr>
        <tr>
            <td style="color: #6b7280;">Reorder level</td>
            <td>{{ $product->reorder_level }}</td>
        </tr>
    </table>

    <p style="margin: 16px 0 0; color: #6b7280; font-size: 13px;">
        You are receiving this because low stock alerts are enabled on your account.
    </p>
</body>
</html>
