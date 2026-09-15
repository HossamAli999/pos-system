<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 13px; }
        h2 { margin-bottom: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background-color: #f5f5f5; }
        .totals td { font-weight: bold; }
        .header-table td { border: none; padding: 2px 8px 2px 0; }
    </style>
</head>
<body>
    <h2>{{ $business_name }}</h2>
    <p>{{ __('hr.payslip') }}</p>

    <table class="header-table">
        <tr>
            <td><strong>{{ __('hr.employee') }}:</strong></td>
            <td>{{ $payslip->employee->full_name ?? '' }} ({{ $payslip->employee->employee_code ?? '' }})</td>
            <td><strong>{{ __('hr.pay_period_start') }}:</strong></td>
            <td>{{ $payslip->payroll_run->pay_period_start->format('Y-m-d') }}</td>
        </tr>
        <tr>
            <td><strong>{{ __('hr.pay_date') }}:</strong></td>
            <td>{{ $payslip->payroll_run->pay_date->format('Y-m-d') }}</td>
            <td><strong>{{ __('hr.pay_period_end') }}:</strong></td>
            <td>{{ $payslip->payroll_run->pay_period_end->format('Y-m-d') }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>{{ __('hr.component') }}</th>
                <th>{{ __('hr.component_type') }}</th>
                <th>{{ __('hr.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payslip->lines as $line)
                <tr>
                    <td>{{ $line->component_name }}</td>
                    <td>{{ __('hr.'.$line->type) }}</td>
                    <td>{{ number_format($line->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals">
                <td colspan="2">{{ __('hr.gross_earnings') }}</td>
                <td>{{ number_format($payslip->gross_earnings, 2) }}</td>
            </tr>
            <tr class="totals">
                <td colspan="2">{{ __('hr.total_deductions') }}</td>
                <td>{{ number_format($payslip->total_deductions, 2) }}</td>
            </tr>
            <tr class="totals">
                <td colspan="2">{{ __('hr.net_pay') }}</td>
                <td>{{ number_format($payslip->net_pay, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
