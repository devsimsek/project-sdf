<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Welcome to {{ $app_title ?? "sdf" }} {{ $app_version ?? "v" . SDF_VERSION }}</title>
</head>
<body>
You set all necessary things if you can see this page without any errors. To begin developing your application visit sdf
<a href="{{ SDF_SRC_LATEST }}/wiki">docs</a>.
You are ready to go!
<br>
Powered By sdf v{{ SDF_VERSION }}.
<br>
<small><i>To Check Benchmarking Open Your Browser's Developer Console</i></small>
@If (USE_FUSE)
<br>
<small><i>View Engine: Fuse!</i></small>
@Else
<small><i>View Engine: plain php and html</i></small>
@endIf

</body>
</html>
