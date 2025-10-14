<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ইউনিক কোচিং - টাকা প্রদানের রশিদ (Bootstrap 5)</title>

    <link href="https://fonts.maateen.me/solaiman-lipi/font.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Base font applied to the body */
        body {
            font-family: 'SolaimanLipi', sans-serif;
            padding: 0 6.35mm;
            font-size: 1rem;
            /* Adjusted base size */
            color: #333;
            background-color: #f8f9fa;
        }
    </style>
</head>

<body>
    <div class="d-flex flex-column mx-auto">
        <div style="height: 35mm; width: 100%; background: red;">
        </div>

        <div class="row">
            <div class="col-8">
                <table>
                    <tr>
                        <td style="width: 50px; background: yellow;">নাম:</td>
                        <td>{{ $student->name }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-4">
                <table>
                    <tr>
                        <td style="width: 50px; background: yellow;">শ্রেণি:</td>
                        <td>{{ $student->class->name }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-8 mt-1">
                <table>
                    <tr>
                        <td style="width: 50px; background: yellow;">আইডি:</td>
                        <td>{{ $student->student_unique_id }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-4 mt-1">
                <table>
                    <tr>
                        <td style="width: 50px; background: yellow;">ব্যাচ:</td>
                        <td>{{ $student->batch->name }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <h5 class="fs-6 text-center">মাসিক বেতন</h5>

        <table>
            <tr>
                @foreach ()
            </tr>
        </table>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
