<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Docs', href: '/docs/webhooks/incidents' },
];

const baseUrl = typeof window !== 'undefined' ? window.location.origin : 'https://your-domain';
const exampleSecret = 'DEVICE_SECRET_HERE';
const endpoint = `${baseUrl}/api/devices/${exampleSecret}/incidents`;
</script>

<template>
    <Head title="Webhook: Device Incidents" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Webhook: Add Incidents</h1>
                <p class="mt-1 text-muted-foreground">Report incidents from a device or external system and automatically update device status.</p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Endpoint</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div>
                        <div class="text-sm"><span class="font-medium">Method:</span> POST</div>
                        <div class="text-sm">
                            <span class="font-medium">Path:</span>
                            <code class="rounded bg-muted px-2 py-1">/api/devices/{secret}/incidents</code>
                        </div>
                    </div>

                    <div class="text-sm text-muted-foreground">
                        Authenticate using the device secret in the URL path. You can find a device's secret on the device page.
                    </div>

                    <div class="text-sm">
                        <span class="font-medium">Resolved example URL:</span>
                        <code class="mt-1 block rounded bg-muted p-2 break-all">{{ endpoint }}</code>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Request Body</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <ul class="list-disc pl-6 text-sm">
                        <li><b>severity</b>: required; one of <code>low|medium|high|critical</code></li>
                        <li><b>title</b>: required; string, max 255</li>
                        <li><b>description</b>: optional; string</li>
                        <li><b>metadata</b>: optional; object map of extra fields</li>
                        <li><b>device_status</b>: optional; one of <code>online|offline|warning|critical</code> (default: <code>warning</code>)</li>
                    </ul>

                    <div>
                        <div class="mb-1 text-sm font-medium">JSON Example</div>
                        <pre class="overflow-auto rounded bg-muted p-3 text-xs"><code>{{ `{
  "severity": "high",
  "title": "Disk space low",
  "description": "Only 5% left on /",
  "device_status": "warning",
  "metadata": { "free_pct": 5, "mount": "/" }
}` }}</code></pre>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Examples</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div>
                        <div class="mb-1 text-sm font-medium">cURL</div>
                        <pre class="overflow-auto rounded bg-muted p-3 text-xs"><code>{{ `curl -X POST ${endpoint} \
  -H "Content-Type: application/json" \
  -d '{
    "severity": "high",
    "title": "Disk space low",
    "description": "Only 5% left on /",
    "device_status": "warning",
    "metadata": {"free_pct": 5}
  }'` }}</code></pre>
                    </div>

                    <div>
                        <div class="mb-1 text-sm font-medium">HTTPie</div>
                        <pre class="overflow-auto rounded bg-muted p-3 text-xs"><code>{{ `http POST ${endpoint} \
  severity=high title="Disk space low" \
  description="Only 5% left on /" device_status=warning \
  metadata:='{"free_pct":5}'` }}</code></pre>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Responses</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div>
                        <div class="text-sm font-medium">201 Created</div>
                        <pre class="overflow-auto rounded bg-muted p-3 text-xs"><code>{{ `{
  "message": "Incident created and device status updated.",
  "device": { "id": 7, "status": "warning", "last_seen_at": "2025-09-13T12:00:05Z" },
  "incident": { "id": 42, "status": "open", "severity": "high", "occurred_at": "2025-09-13T12:00:00Z" }
}` }}</code></pre>
                    </div>

                    <div>
                        <div class="text-sm font-medium">404 Not Found</div>
                        <pre class="overflow-auto rounded bg-muted p-3 text-xs"><code>{{ `{
  "message": "Device not found."
}` }}</code></pre>
                    </div>

                    <div>
                        <div class="text-sm font-medium">422 Validation Error</div>
                        <pre class="overflow-auto rounded bg-muted p-3 text-xs"><code>{{ `{
  "message": "The given data was invalid.",
  "errors": { "severity": ["The severity field is required."], ... }
}` }}</code></pre>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Notes</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 text-sm text-muted-foreground">
                    <p>Find the device secret on the device detail page. Keep it confidential.</p>
                    <p>
                        On each webhook call, the device status is updated to the provided <code>device_status</code> (default: <code>warning</code>).
                    </p>
                    <p><b>occurred_at</b> is set by the server to the time the webhook is received; any client-provided value is ignored.</p>
                    <p>When an incident is resolved in the app, the device returns to <code>online</code> if no other open incidents exist.</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
