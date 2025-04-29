<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->type === 'facture' ? 'INVOICE' : 'QUOTE' }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;

        }

        .email-container {
            max-width: 800px;
            margin: 40px auto;
            background-color: #f4f6f9;
            padding: 30px;
            border-radius: 12px;
            border: 2px solid #000000;
            border: 2px solid #000000;
            overflow: hidden;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.1);
            text-align: left;
            font-size: 16px;
        }

        .email-header {
            background-color: #1e40af;
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 1px;
        }


        h2 {
            color: #e74c3c;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 25px;
        }

        p {
            color: #555;
            font-size: 16px;
            line-height: 1.6;
            margin: 10px 0;
        }

        .info-block {
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 5px solid #007BFF;
            font-size: 15px;
        }

        .info-block strong {
            font-size: 16px;
            color: #333;
        }

        .footer {
            margin-top: 30px;
            font-size: 13px;
            color: #888;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }

        .card {
            background: #f4f6f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            max-width: 800px;
        }

        .icon {
            width: 20px;
            height: 20px;
            vertical-align: middle;
            margin-right: 10px;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="email-header">
            <span>{{ $invoice->type === 'facture' ? 'INVOICE' : 'QUOTE' }}</span>
        </div>

        <div class="email-body">
            <p>Hello {{ $client->name }},</p>
            <p>Thank you for your trust. Please find below your
                {{ $invoice->type === 'facture' ? 'invoice' : 'quote' }}.</p>

            <div class="card">
                <h2><img src="https://img.icons8.com/small/20/000000/details.png" class="icon" alt="Details Icon"> Your
                    {{ $invoice->type === 'facture' ? 'Invoice' : 'Quote' }}:</h2>
                {!! $invoiceContent !!}
            </div>

            <div class="info-block">
                <strong><img src="https://img.icons8.com/small/20/000000/user.png" class="icon" alt="User Icon">
                    Important Information:</strong>
                <p>Dear {{ $client->name }}, please ensure that the details in this document are correct. If you have
                    any questions or need further assistance, feel free to contact us.</p>
            </div>
        </div>

        <div class="footer">
            <p>
                This is an automated message. Please do not reply directly to this email.<br>
                For any inquiries, contact the HR department at <a
                    href='mailto:info@procan-group.com'>info@procan-group.com</a>.<br>
                PROCAN HR System © {{ date('Y') }}
            </p>
        </div>
    </div>
</body>

</html>
