<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <title>Ops, something went wrong...</title>

        <!-- Fonts -->
        <link rel="dns-prefetch" href="//fonts.gstatic.com">
        <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

        <link href="{{ mix('css/app.css') }}" rel="stylesheet">

        <style type="text/css">
            .font-nunito {
                font-family: Nunito, sans-serif;
            }
        </style>
    </head>
    <body class="container py-6">
        <section class="row">
            <div class="col-lg-6 col-md-8 col-10 mx-auto text-center">
                <div class="mb-4">
                    @icon
                </div>
                
                <div class="mb-4">
                    <h1 style="font-size: 9rem" class="m-0 font-nunito">
                        @yield('code')
                    </h1>
                    <h1 class="font-nunito text-muted">OOPS...</h1>
                </div>

                <div class="mb-5">
                    <p>This issue has been reported and it will be fixed soon. If this problem persists, please let us know at <a href="mailto:{{pianolit()->email()}}">{{pianolit()->email()}}</a>.</p>
                </div>
                
                @button([
                    'href' => url()->previous(),
                    'label' => 'GO BACK',
                    'styles' => [
                        'shadow' => true,
                        'size' => 'wide',
                        'theme' => 'primary'
                    ]
                ])
            </div>
        </section>
    </body>
</html>
