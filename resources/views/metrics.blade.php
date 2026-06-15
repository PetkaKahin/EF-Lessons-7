<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application metrics</title>
    <style>
        :root {
            color-scheme: dark;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #d7dde5;
            background: #0f141b;
        }

        body {
            margin: 0;
            background: #0f141b;
        }

        main {
            width: min(960px, calc(100% - 32px));
            margin: 32px auto;
        }

        h1 {
            margin: 0 0 20px;
            font-size: 28px;
            line-height: 1.2;
            color: #f2f5f8;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .metric,
        .table-wrap {
            border: 1px solid #263241;
            border-radius: 8px;
            background: #151c25;
        }

        .metric {
            padding: 16px;
        }

        .label {
            margin: 0 0 8px;
            color: #8f9dad;
            font-size: 13px;
        }

        .value {
            margin: 0;
            font-size: 26px;
            font-weight: 700;
            color: #f2f5f8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        caption {
            padding: 16px;
            text-align: left;
            font-weight: 700;
        }

        th,
        td {
            padding: 12px 16px;
            border-top: 1px solid #263241;
            text-align: left;
            white-space: nowrap;
        }

        th {
            color: #8f9dad;
            font-weight: 600;
        }

        .empty {
            padding: 16px;
            color: #8f9dad;
        }
    </style>
</head>
<body>
<main>
    <h1>Application metrics</h1>

    <section class="grid" aria-label="Summary">
        <article class="metric">
            <p class="label">Total requests</p>
            <p class="value">{{ $requestsTotal }}</p>
        </article>
        <article class="metric">
            <p class="label">Average response time</p>
            <p class="value">{{ number_format($responseTimeAvg, 1, '.', '') }} ms</p>
        </article>
        <article class="metric">
            <p class="label">Max response time</p>
            <p class="value">{{ number_format($responseTimeMax, 1, '.', '') }} ms</p>
        </article>
        <article class="metric">
            <p class="label">Total response time</p>
            <p class="value">{{ number_format($responseTimeSum, 1, '.', '') }} ms</p>
        </article>
    </section>

    <section class="table-wrap">
        <table>
            <caption>Recent requests</caption>
            <thead>
            <tr>
                <th>Method</th>
                <th>Path</th>
                <th>Status</th>
                <th>Time</th>
                <th>Created</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($recentRequests as $metric)
                <tr>
                    <td>{{ $metric->method }}</td>
                    <td>{{ $metric->path }}</td>
                    <td>{{ $metric->status_code }}</td>
                    <td>{{ number_format($metric->response_time_ms, 1, '.', '') }} ms</td>
                    <td>{{ $metric->created_at?->format('Y-m-d H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="5">No requests recorded yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
