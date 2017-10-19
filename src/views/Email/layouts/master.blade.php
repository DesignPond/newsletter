<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <!-- Define Charset -->
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <!-- Responsive Meta Tag -->
        <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;">
        <title>{{ $campagne->newsletter->titre }}</title><!-- Responsive Styles and Valid Styles -->
        <link rel="stylesheet" href="<?php echo asset('newsletter/css/frontend/newsletter.css'); ?>">

        <style type="text/css">
            #StyleNewsletter h2, #StyleNewsletterCreate h2{  color: {{ $campagne->newsletter->color }};  }
            #StyleNewsletter .contentForm h3, #StyleNewsletter .contentForm h4{  color: {{ $campagne->newsletter->color }};  }
        </style>
    </head>

    <body>
        <div id="StyleNewsletter">
            <!-- Main table -->
            <table border="0" width="600" cellpadding="0" cellspacing="0" bgcolor="#ffffff">
                <!-- Main content wrapper -->

                <!-- See in browser -->
                <tr>
                    <td width="560" align="center" valign="top">
                        <table border="0" width="560" cellpadding="0" cellspacing="0" class="resetTable">
                            <tr><td height="15"></td></tr><!-- space -->
                            <tr>
                                <td align="center" class="linkGrey">
                                    Si cet email ne s'affiche pas correctement, vous pouvez le voir directement dans
                                    <a class="linkGrey" href="{{ url('/campagne/'.$campagne->id) }}">votre navigateur</a>.
                                </td>
                            </tr>
                            <tr><td height="15"></td></tr><!-- space -->
                        </table>
                    </td>
                </tr>
                <!-- End see in browser -->

                <!-- Logos -->
                @include('newsletter::Email.send.logos')
                <!-- Header -->
                @include('newsletter::Email.send.header')

                @if(config('newsletter.pdf'))
                    @include('newsletter::Email.send.link')
                @endif

                <tr>
                    <td id="sortable" class="newsletterborder" style="display:block;" width="600" align="center" valign="top">

                        <!-- Main content -->
                        @yield('content')
                        <!-- Fin contenu -->
                    </td>
                </tr>
                <tr>
                    <td width="560" align="center" valign="top">
                        <!-- See in browser -->
                        <table border="0" width="600" cellpadding="0" cellspacing="0" class="tableReset">
                            <tr><td height="15"></td></tr><!-- space -->
                            <tr>
                                <td align="center" class="linkGrey">Si vous ne désirez plus recevoir cette newsletter, vous pouvez vous désinscrire à tout moment en
                                    <a href="[[UNSUB_LINK_EN]]"></a><a class="linkGrey" href="{{ url('/unsubscribe/'.$campagne->newsletter->id) }}">cliquant ici</a>.
                                </td>
                            </tr>
                            <tr><td height="15"></td></tr><!-- space bottom -->
                        </table>
                        <!-- End see in browser -->
                    </td>
                </tr>
                <!-- End main content wrapper -->

            </table>
            <!-- End main table -->
        </div>

    </body>
</html>