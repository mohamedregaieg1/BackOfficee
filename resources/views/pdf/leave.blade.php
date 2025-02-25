<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Day Request</title>
    <style>
        @page {
            size: A4;
            margin: 0px;
        }

        body { 
            font-family: Arial, sans-serif; 
            width: 100%;
            height: 100%;
            padding: 0;
            margin: 0;
        }
        .header-table {
            width: 100%;
            margin-top: 40px;
            margin-bottom: 20px;
        }

        .header-table td {
            vertical-align: middle;
            padding: 10px;
           
        }

        .logo { 
            width: 250px; 
        }

        .date {
            font-size: 17px;
            font-weight: bold;
            text-align: right;
        }

        h1 { 
            color: navy; 
            text-align: center;
            margin-top: 20px;
        }
        table { 
            font-size: 23px;
            width: 100%; 
            margin-top: 20px; 
            border:none;
        }

        th, td { 
            text-align: left; 
            border:none;
        }

        th { 
            border:none;
        }
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('data:image/png;base64,{{ $statusImageBase64 }}');
            background-size: cover;
            background-position: center;
            opacity: 0.1;
            z-index: -1;
        }
        .spacing {
             margin-left: 10px;
            }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td><img src="data:image/png;base64,{{ $companyLogoBase64 }}" class="logo"></td>
            <td class="date">DATE: {{ \Carbon\Carbon::now()->format('d/m/Y') }}</td>
        </tr>
    </table>

    <h1>Leave Day Request</h1>

    <table style="padding:80px 50px 100px 70px;">
        <tr>
            <strong>Employee:</strong><span class="spacing">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</span>
        </tr>
        <tr>
        <strong>Start Date:</strong><span class="spacing" >{{ $leave->start_date }}</span>
        </tr>
        <tr>
            <strong>End Date:</strong>
            <span class="spacing" >{{ $leave->end_date }}</span>
        </tr>
        <tr>
            <strong>Leave Days:</strong>
            @if($leave->reason == 'sick_leave')
                <span class="spacing" >{{ $leave->effective_leave_days }}</span>
            @else
                <span class="spacing" >Leave Days Requested {{ $leave->leave_days_requested }}</span>
            @endif
        </tr>
        <tr>
            <strong>Reason:</strong>
            @if ($leave->reason=='other')
                <span class="spacing" >{{ $leave->other_reason}}</span>
            @else
                <span class="spacing" >{{ $leave->reason}}</span>
            @endif
        </tr>
      
    </table>

</body>
</html>