<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>
    <style>
        body {
            background-color: white !important;
        }
        @media only screen and (max-width: 600px) {
            .main-body {
                width: 100% !important;
            }

            .footer {
                width: 100% !important;
            }
        }

        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }
    </style>

    <table class="" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="left">
                <table class="" width="100%" cellpadding="0" cellspacing="0">
                    <!-- Email Body -->
                    <tr>
                        <td class="" width="100%" cellpadding="0" cellspacing="0">
                            <table class="main-body" align="left" width="100%" cellpadding="0" cellspacing="0">
                                <!-- Body content -->
                                <tr>
                                    <td class="">
                                        {{ Illuminate\Mail\Markdown::parse($slot) }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="" width="100%" cellpadding="0" cellspacing="0">
                            <table class="main-body" align="left" width="100%" cellpadding="0" cellspacing="0">
                                <!-- Body content -->
                                <tr>
                                    <td class="">
                                        {{ Illuminate\Mail\Markdown::parse($footer) }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
