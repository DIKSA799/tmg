<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $subjectLine }}</title>
    </head>
    <body style="margin: 0; padding: 24px; background: #f6f2f2; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; color: #1a1414; line-height: 1.6;">
        <div style="max-width: 36rem; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 24px;">
            <h1 style="margin: 0 0 16px; font-size: 1.15rem;">{{ $subjectLine }}</h1>
            <div style="font-size: 0.95rem; white-space: normal;">{!! nl2br(e($body)) !!}</div>
        </div>
    </body>
</html>
