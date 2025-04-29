<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Day Request</title>
    <style>
        @page {
            size: A4;
            margin: 20mm;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: #f8f9fa;
            border-bottom: 2px solid #007BFF;
        }

        .header .logo {
            width: 150px;
        }

        .header .date {
            font-size: 12px;
            color: #333333;
            text-align: right;
        }

        h1 {
            text-align: center;
            color: #007BFF;
            margin-top: 20px;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .section-title {
            color: #007BFF;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        ul {
            list-style-type: disc;
            padding-left: 20px;
        }

        li {
            margin-bottom: 5px;
        }

        label {
            font-weight: bold;
        }

        input[type="text"],
        input[type="date"] {
            width: 100%;
            padding: 5px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #f8f9fa;
        }

        input[type="checkbox"] {
            margin-right: 5px;
        }

        .leave-type-container {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }

        .leave-type-container input[type="checkbox"] {
            margin-right: 10px;
        }

        .leave-type-container input[type="text"] {
            margin-left: 10px;
            width: auto;
        }

        .signature {
            margin-top: 20px;
            text-align: center;
        }

        .signature img {
            width: 100px;
            opacity: 0.8;
        }
    </style>
</head>

<body>

    <div class="header">
        <img src="data:image/png;base64,{{ $companyLogoBase64 }}" class="logo">
        <div class="date">Date: {{ \Carbon\Carbon::now()->format('d/m/Y') }}</div>
    </div>

    <h1>Employee Leave Request Form</h1>

    <h2 class="section-title">Employee Information</h2>
    <ul>
        <li><label>Name:</label> <input type="text" value="{{ $leave->user->first_name }} {{ $leave->user->last_name }}"
                readonly></li>
        <li><label>Company:</label> <input type="text" value="{{ $leave->user->company }}" readonly></li>
        @if($leave->user->phone)
            <li>
                <label>Phone Number:</label>
                <input type="text" value="{{ $leave->user->phone }}" readonly>
            </li>
        @endif
        <li><label>Email:</label> <input type="text" value="{{ $leave->user->email }}" readonly></li>
    </ul>

    <h2 class="section-title">Leave Information</h2>
    <ul>
        <li>
            <label>Leave Start Date:</label>
            <input type="text"
                value="{{ \Carbon\Carbon::parse($leave->start_date)->format('Y/m/d') . '-' . (\Carbon\Carbon::parse($leave->start_date)->format('H:i:s') === '08:00:00' ? 'Full Day' : (\Carbon\Carbon::parse($leave->start_date)->format('H:i:s') === '12:00:00' ? '1st Half' : '2nd Half')) }}"
                readonly>
        </li>
        <li>
            <label>Leave End Date:</label>
            <input type="text"
                value="{{ \Carbon\Carbon::parse($leave->end_date)->format('Y/m/d') . '-' . (\Carbon\Carbon::parse($leave->end_date)->format('H:i:s') === '08:00:00' ? 'Full Day' : (\Carbon\Carbon::parse($leave->end_date)->format('H:i:s') === '12:00:00' ? '1st Half' : '2nd Half')) }}"
                readonly>
        </li>
        <li><label>Number of Days:</label> <input type="text" value="{{ $leave->leave_days_requested }}" readonly></li>
        <li>
            <label>Leave Type:</label>
            <div class="leave-type-container">
                <input type="checkbox" disabled {{ $leave->leave_type === 'personal_leave' ? 'checked' : '' }}> Personal
                Leave
                <input type="checkbox" disabled {{ $leave->leave_type === 'sick_leave' ? 'checked' : '' }}> Sick Leave
                <input type="checkbox" disabled {{ $leave->leave_type === 'paternity_leave' || $leave->leave_type === 'maternity_leave' ? 'checked' : '' }}> Paternity/Maternity Leave
                <input type="checkbox" disabled {{ $leave->leave_type === 'other' ? 'checked' : '' }}> Other
                @if ($leave->leave_type === 'other')
                    <input type="text" value="{{ $leave->other_type }}" readonly>
                @endif
            </div>
        </li>
    </ul>

    <div class="signature">
        <img src="data:image/png;base64,{{ $statusImageBase64 }}" alt="Approved Signature">
    </div>

</body>

</html>
