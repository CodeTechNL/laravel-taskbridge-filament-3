@php
    $status   = $output['status'] ?? 'info';
    $message  = $output['message'] ?? null;
    $metadata = $output['metadata'] ?? [];

    $badgeColor = match ($status) {
        'success' => 'bg-green-100 text-green-800 dark:bg-green-500/20 dark:text-green-400',
        'error'   => 'bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-400',
        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-500/20 dark:text-yellow-400',
        default   => 'bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-400',
    };
@endphp

<div class="space-y-4">
    <div class="flex items-center gap-3">
        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold {{ $badgeColor }}">
            {{ ucfirst($status) }}
        </span>
        @if ($message)
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $message }}</span>
        @endif
    </div>

    @if ($metadata)
        <div class="rounded-lg border border-gray-200 dark:border-white/10 overflow-hidden">
            @foreach ($metadata as $key => $value)
                <div class="flex gap-4 px-4 py-2 {{ !$loop->last ? 'border-b border-gray-100 dark:border-white/5' : '' }} {{ $loop->even ? 'bg-gray-50 dark:bg-white/[0.02]' : '' }}">
                    <span class="shrink-0 w-40 text-xs font-medium text-gray-500 dark:text-gray-400 pt-0.5">
                        {{ $key }}
                    </span>
                    <span class="text-sm text-gray-900 dark:text-white break-all">
                        @if (is_array($value))
                            <pre class="text-xs font-mono whitespace-pre-wrap">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                        @else
                            {{ $value }}
                        @endif
                    </span>
                </div>
            @endforeach
        </div>
    @endif
</div>
