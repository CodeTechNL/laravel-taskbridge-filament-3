<?php

namespace CodeTechNL\TaskBridgeFilament;

use CodeTechNL\TaskBridgeFilament\Resources\ScheduledJobResource;
use CodeTechNL\TaskBridgeFilament\Resources\ScheduledJobRunResource;
use CodeTechNL\TaskBridgeFilament\Widgets\TaskBridgeWidget;
use Filament\Contracts\Plugin;
use Filament\Panel;

class TaskBridgePlugin implements Plugin
{
    // ── Navigation ────────────────────────────────────────────────────────────

    private string $navigationGroup = 'System';

    private string $navigationLabel = 'Scheduled Jobs';

    private string $navigationIcon = 'heroicon-o-clock';

    private int $navigationSort = 99;

    // ── Page content ──────────────────────────────────────────────────────────

    private string $slug = 'scheduled-jobs';

    private string $heading = 'Scheduled Jobs';

    private ?string $subheading = null;

    // ── Table behaviour ───────────────────────────────────────────────────────

    /**
     * When true, job classes that are already in the database are shown in the
     * Create dropdown with a visual disabled state and blocked by validation.
     * Prevents accidentally registering the same class twice.
     * Default: true.
     */
    private bool $preventDuplicates = true;

    /** Wrap row actions in a dropdown instead of showing them all inline. */
    private bool $groupActions = false;

    /** Records-per-page options shown in the table pagination selector. */
    private array $paginationPageOptions = [25, 50, 100];

    /** Default records per page. Must be a value present in $paginationPageOptions. */
    private int $defaultPaginationPageOption = 25;

    // ── Run log page ──────────────────────────────────────────────────────────

    private bool $registerRunLog = true;

    private string $runLogNavigationLabel = 'Run Logs';

    private string $runLogNavigationIcon = 'heroicon-o-list-bullet';

    private string $runLogSlug = 'scheduled-job-runs';

    private string $runLogHeading = 'Run Logs';

    private array $runLogPaginationPageOptions = [25, 50, 100];

    private int $runLogDefaultPaginationPageOption = 25;

    // ── Features ──────────────────────────────────────────────────────────────

    private bool $registerWidget = true;

    private ?string $policyClass = null;

    // ── Static singleton (set on boot, used by resource static methods) ───────

    private static ?self $instance = null;

    // ── Factory ───────────────────────────────────────────────────────────────

    public static function make(): static
    {
        return new static;
    }

    /**
     * Access the booted plugin instance from anywhere (e.g. static resource methods).
     * Falls back to a fresh instance with defaults when Filament is not available.
     */
    public static function get(): static
    {
        if (static::$instance !== null) {
            return static::$instance;
        }

        try {
            /** @var static $plugin */
            $plugin = filament()->getPlugin('taskbridge');

            return $plugin;
        } catch (\Throwable) {
            return new static;
        }
    }

    // ── Plugin contract ───────────────────────────────────────────────────────

    public function getId(): string
    {
        return 'taskbridge';
    }

    public function register(Panel $panel): void
    {
        $resources = [ScheduledJobResource::class];

        if ($this->registerRunLog) {
            $resources[] = ScheduledJobRunResource::class;
        }

        $panel->resources($resources);

        if ($this->registerWidget) {
            $panel->widgets([TaskBridgeWidget::class]);
        }
    }

    public function boot(Panel $panel): void
    {
        static::$instance = $this;
    }

    // ── Fluent setters ────────────────────────────────────────────────────────

    /** Navigation group label in the sidebar. */
    public function navigationGroup(string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    /** Navigation item label. */
    public function navigationLabel(string $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    /** Navigation item icon (Heroicon name). */
    public function navigationIcon(string $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    /** Navigation item sort order. */
    public function navigationSort(int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    /** URL slug for the resource (e.g. 'scheduler' → /admin/scheduler). */
    public function slug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /** H1 heading shown on the list page. */
    public function heading(string $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    /** Subtitle shown below the heading on the list page. */
    public function subheading(string $subheading): static
    {
        $this->subheading = $subheading;

        return $this;
    }

    /**
     * Control whether already-registered job classes are shown as disabled in
     * the Create dropdown, preventing the same class from being added twice.
     *
     * true  (default) — already-registered classes appear greyed-out and are
     *                   blocked by validation. Safe for production.
     * false           — all classes are shown and selectable regardless of
     *                   their database state. Useful during development or when
     *                   you intentionally want to re-register a class.
     */
    public function preventDuplicates(bool $prevent = true): static
    {
        $this->preventDuplicates = $prevent;

        return $this;
    }

    /**
     * Collapse all row actions into a single dropdown menu.
     * Default: false (all actions shown inline).
     */
    public function groupActions(bool $group = true): static
    {
        $this->groupActions = $group;

        return $this;
    }

    /**
     * Set the available records-per-page options for the table pagination selector.
     * The first value becomes the default unless overridden with defaultPaginationPageOption().
     *
     * Example: ->paginationPageOptions([10, 25, 50])
     */
    public function paginationPageOptions(array $options): static
    {
        $this->paginationPageOptions = $options;

        // Align the default to the first option when the list changes
        if (! in_array($this->defaultPaginationPageOption, $options, true)) {
            $this->defaultPaginationPageOption = (int) reset($options);
        }

        return $this;
    }

    /**
     * Override the default selected page size.
     * Must be a value present in paginationPageOptions().
     */
    public function defaultPaginationPageOption(int $size): static
    {
        $this->defaultPaginationPageOption = $size;

        return $this;
    }

    /** Disable the stats widget from being registered on the panel. */
    public function withoutWidget(): static
    {
        $this->registerWidget = false;

        return $this;
    }

    /** Custom authorization policy class for the resource. */
    public function policy(string $policyClass): static
    {
        $this->policyClass = $policyClass;

        return $this;
    }

    /** Hide the Run Logs page from the panel entirely. */
    public function withoutRunLog(): static
    {
        $this->registerRunLog = false;

        return $this;
    }

    /** Navigation label for the Run Logs page. */
    public function runLogNavigationLabel(string $label): static
    {
        $this->runLogNavigationLabel = $label;

        return $this;
    }

    /** Navigation icon for the Run Logs page (Heroicon name). */
    public function runLogNavigationIcon(string $icon): static
    {
        $this->runLogNavigationIcon = $icon;

        return $this;
    }

    /** URL slug for the Run Logs page. */
    public function runLogSlug(string $slug): static
    {
        $this->runLogSlug = $slug;

        return $this;
    }

    /** H1 heading shown on the Run Logs list page. */
    public function runLogHeading(string $heading): static
    {
        $this->runLogHeading = $heading;

        return $this;
    }

    /** Records-per-page options for the Run Logs table. */
    public function runLogPaginationPageOptions(array $options): static
    {
        $this->runLogPaginationPageOptions = $options;

        if (! in_array($this->runLogDefaultPaginationPageOption, $options, true)) {
            $this->runLogDefaultPaginationPageOption = (int) reset($options);
        }

        return $this;
    }

    /** Default page size for the Run Logs table. */
    public function runLogDefaultPaginationPageOption(int $size): static
    {
        $this->runLogDefaultPaginationPageOption = $size;

        return $this;
    }

    // ── Getters ───────────────────────────────────────────────────────────────

    public function getNavigationGroup(): string
    {
        return $this->navigationGroup;
    }

    public function getNavigationLabel(): string
    {
        return $this->navigationLabel;
    }

    public function getNavigationIcon(): string
    {
        return $this->navigationIcon;
    }

    public function getNavigationSort(): int
    {
        return $this->navigationSort;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getHeading(): string
    {
        return $this->heading;
    }

    public function getSubheading(): ?string
    {
        return $this->subheading;
    }

    public function getPolicyClass(): ?string
    {
        return $this->policyClass;
    }

    public function shouldPreventDuplicates(): bool
    {
        return $this->preventDuplicates;
    }

    public function shouldGroupActions(): bool
    {
        return $this->groupActions;
    }

    public function getPaginationPageOptions(): array
    {
        return $this->paginationPageOptions;
    }

    public function getDefaultPaginationPageOption(): int
    {
        return $this->defaultPaginationPageOption;
    }

    public function shouldRegisterRunLog(): bool
    {
        return $this->registerRunLog;
    }

    public function getRunLogNavigationLabel(): string
    {
        return $this->runLogNavigationLabel;
    }

    public function getRunLogNavigationIcon(): string
    {
        return $this->runLogNavigationIcon;
    }

    public function getRunLogSlug(): string
    {
        return $this->runLogSlug;
    }

    public function getRunLogHeading(): string
    {
        return $this->runLogHeading;
    }

    public function getRunLogPaginationPageOptions(): array
    {
        return $this->runLogPaginationPageOptions;
    }

    public function getRunLogDefaultPaginationPageOption(): int
    {
        return $this->runLogDefaultPaginationPageOption;
    }
}
