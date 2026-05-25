<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Offer Letter</title>
</head>
<body style="font-family: Arial, sans-serif; color: #222; line-height: 1.5;">
    <p>Dear {{ $candidateName }},</p>

    <p>Congratulations! We are delighted to confirm your selection for the position of
       <strong>{{ $positionTitle }}</strong> at <strong>{{ $resortName }}</strong>.</p>

    <p>Please find your offer letter and contract attached to this email. Kindly review the
       documents, sign where indicated, and return them to our HR team at your earliest
       convenience.</p>

    <p>We look forward to having you on the team.</p>

    <p>Warm regards,<br>
       Human Resources Department<br>
       {{ $resortName }}</p>
</body>
</html>
