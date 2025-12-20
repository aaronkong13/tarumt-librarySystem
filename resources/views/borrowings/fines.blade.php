{{--
    This file is deprecated. Please use the new Fine Management module.
    Redirecting to the new fines page.
--}}
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="refresh" content="0;url={{ route('fines.index') }}">
    </head>
    <body>
        <p>
            This page has moved. If you are not redirected automatically,
            <a href="{{ route('fines.index') }}">click here to go to the new fines page</a>.
        </p>
    </body>
</html>
