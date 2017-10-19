<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <link rel="stylesheet" type="text/css" href="{{ public_path('newsletter/css/frontend/print.css') }}" media="screen" />
        <style>
            * {
                font-family: Arial, Helvetica, sans-serif;
                text-align: justify;
                box-sizing:border-box;
            }
            @page {
                padding: 20px; margin:20px 50px; background: #fff; font-family: Arial, Helvetica, sans-serif; page-break-inside: avoid;
            }
            .bloc{
                margin: 10px 0;
                width: 100%;
                page-break-inside: auto !important;
            }
            .bloc,
            .bloc div,
            .bloc div p,
            .bloc div p a,
            .bloc p
            .bloc a,
            .bloc ul li
            {
                font-size: 16px !important;
                line-height:20px;
                box-sizing:border-box;
            }
            .arret h2{
                font-size: 24px;
            }
            .arret, .analyse{
                width: 100%;
                display: block;
            }
            .arret-content{
                display: block;
                box-sizing:border-box;
                width: 100%;
            }
            .arret-categories{
                display: inline-block;
                width:19%;
                box-sizing:border-box;
                text-align: center;
            }
            .arret-categories a{
                display: block;
                width:100%;
                text-align: center;
                margin-bottom: 3px;
            }

            hr{
                display: block;
                clear: both;
                height: 1px;
                margin: 5px 0;
                visibility: hidden;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <!-- Main content -->
            @yield('content')
            <!-- Fin contenu -->
        </div>
    </body>
</html>