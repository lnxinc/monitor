<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Docs', href: '/docs/webhooks/monitors' },
];

const baseUrl = typeof window !== 'undefined' ? window.location.origin : 'https://your-domain';
const exampleToken = 'MONITOR_TOKEN_HERE';
const endpoint = `${baseUrl}/api/webhooks/monitors/${exampleToken}`;
</script>

<template>
  <Head title="Webhook: Monitor Events" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Webhook: Monitor Events</h1>
        <p class="text-muted-foreground mt-1">
          Push health signals from external observers directly into MSP Monitor. Each webhook updates history and alerting
          just like an internal probe.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Endpoint</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3 text-sm">
          <div>
            <span class="font-medium">Method:</span> POST
          </div>
          <div>
            <span class="font-medium">Path:</span>
            <code class="bg-muted rounded px-2 py-1">/api/webhooks/monitors/{token}</code>
          </div>
          <div class="text-muted-foreground">
            Each monitor of type <b>Webhook</b> has a unique token. Find it on the monitor detail page and keep it secret.
          </div>
          <div>
            <span class="font-medium">Resolved example URL:</span>
            <code class="block bg-muted rounded p-2 mt-1 break-all">{{ endpoint }}</code>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Request Body</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3 text-sm">
          <ul class="list-disc pl-6 space-y-1">
            <li><b>status</b> <span class="text-muted-foreground">(required)</span>: <code>up</code> or <code>down</code>.</li>
            <li><b>status_code</b>: Optional HTTP or service code (100–599).</li>
            <li><b>response_time_ms</b>: Optional integer latency in milliseconds.</li>
            <li><b>error</b>: Optional string describing the failure when <code>status</code> is <code>down</code>.</li>
            <li><b>meta</b>: Optional object for probe metadata (host, region, etc.).</li>
            <li><b>payload</b>: Optional object for raw diagnostic data.</li>
            <li><b>checked_at</b>: Optional ISO-8601 timestamp for when the observation occurred. Defaults to receipt time.</li>
          </ul>

          <div>
            <div class="text-sm font-medium mb-1">JSON Example</div>
            <pre class="text-xs bg-muted rounded p-3 overflow-auto"><code>{{ `{
  "status": "down",
  "status_code": 503,
  "response_time_ms": 1875,
  "error": "Spectrum DVR endpoint timeout",
  "meta": { "probe": "us-east-1", "port": 7001 },
  "payload": { "attempts": 3, "last_seen": "2025-10-01T18:02:15Z" },
  "checked_at": "2025-10-01T18:05:00Z"
}` }}</code></pre>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Examples</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4 text-sm">
          <div>
            <div class="font-medium mb-1">cURL</div>
            <pre class="text-xs bg-muted rounded p-3 overflow-auto"><code>{{ `curl -X POST ${endpoint} \
  -H "Content-Type: application/json" \
  -d '{
    "status": "down",
    "status_code": 503,
    "response_time_ms": 1875,
    "error": "Spectrum DVR endpoint timeout",
    "meta": {"probe": "us-east-1"},
    "payload": {"attempts": 3}
  }'` }}</code></pre>
          </div>

          <div>
            <div class="font-medium mb-1">HTTPie</div>
            <pre class="text-xs bg-muted rounded p-3 overflow-auto"><code>{{ `http POST ${endpoint} \
  status=up response_time_ms:=320 \
  meta:='{"probe":"la-edge"}'` }}</code></pre>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Responses</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3 text-sm">
          <div>
            <div class="font-medium">202 Accepted</div>
            <pre class="text-xs bg-muted rounded p-3 overflow-auto"><code>{{ `{
  "message": "Monitor state updated.",
  "monitor_id": 12
}` }}</code></pre>
          </div>
          <div>
            <div class="font-medium">404 Not Found</div>
            <pre class="text-xs bg-muted rounded p-3 overflow-auto"><code>{{ `{
  "message": "Monitor not found."
}` }}</code></pre>
          </div>
          <div>
            <div class="font-medium">422 Validation Error</div>
            <pre class="text-xs bg-muted rounded p-3 overflow-auto"><code>{{ `{
  "message": "The given data was invalid.",
  "errors": { "status": ["The status field is required."] }
}` }}</code></pre>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Operational Notes</CardTitle>
        </CardHeader>
        <CardContent class="space-y-2 text-sm text-muted-foreground">
          <p>Webhook monitors do not schedule internal probes; they rely entirely on the events you send.</p>
          <p>Alerts and policies trigger exactly as they do for internal checks when status changes.</p>
          <p>Include <code>checked_at</code> if your probe clock differs from the server or you batch events.</p>
          <p>Rotate tokens by creating a new webhook monitor and disabling the old one when decommissioning agents.</p>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

